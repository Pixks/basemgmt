<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Auth\FrontendAuth;
use BaseMgmt\Auth\SessionManager;
use WP_REST_Request;

defined('ABSPATH') || exit;

/**
 * Authentication endpoints.
 *
 * POST /bm/v1/auth/login   – verify camp + staff + code → set session cookie
 * POST /bm/v1/auth/logout  – destroy session
 * GET  /bm/v1/auth/status  – returns current session info
 */
final class AuthController extends BaseController {

	private const LOGIN_LIMIT_ATTEMPTS = 10;
	private const LOGIN_LIMIT_WINDOW   = 900; // 15 min.

	public function register_routes(): void {
		register_rest_route(self::NAMESPACE, '/auth/login', [
			'methods'             => 'POST',
			'callback'            => [$this, 'login'],
			'permission_callback' => '__return_true',
			'args'                => [
				'camp_id'       => ['required' => true,  'sanitize_callback' => 'absint'],
				'staff_id'      => ['required' => true,  'sanitize_callback' => 'absint'],
				'security_code' => ['required' => true,  'sanitize_callback' => 'sanitize_text_field'],
				'nonce'         => ['required' => true,  'sanitize_callback' => 'sanitize_text_field'],
			],
		]);

		register_rest_route(self::NAMESPACE, '/auth/logout', [
			'methods'             => 'POST',
			'callback'            => [$this, 'logout'],
			'permission_callback' => '__return_true',
		]);

		register_rest_route(self::NAMESPACE, '/auth/status', [
			'methods'             => 'GET',
			'callback'            => [$this, 'status'],
			'permission_callback' => '__return_true',
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function login(WP_REST_Request $request): mixed {
		// Verify nonce to prevent CSRF.
		$nonce = $request->get_param('nonce') ?? '';
		if ( ! wp_verify_nonce($nonce, 'bm_login') ) {
			return $this->error('bm_invalid_nonce', __('Nieprawidłowy token bezpieczeństwa. Odśwież stronę.', 'basemgmt'), 403);
		}

		$camp_id  = (int) $request->get_param('camp_id');
		$staff_id = (int) $request->get_param('staff_id');
		$code     = (string) $request->get_param('security_code');
		$rate_key = $this->login_rate_limit_key($camp_id, $staff_id);

		if ( $this->is_rate_limited($rate_key) ) {
			return $this->error('bm_rate_limited', __('Zbyt wiele prób logowania. Spróbuj ponownie później.', 'basemgmt'), 429);
		}

		// Validate code format before touching the DB.
		if ( ! preg_match('/^\d{6}$/', $code) ) {
			$this->bump_rate_limit($rate_key);
			return $this->ok(['success' => false, 'message' => __('Nieprawidłowe dane logowania.', 'basemgmt')], 401);
		}

		$result = FrontendAuth::attempt($camp_id, $staff_id, $code);

		if ( ! $result['success'] ) {
			$this->bump_rate_limit($rate_key);
			$data = ['success' => false, 'message' => $result['message']];
			if ( isset($result['locked_until']) ) {
				$data['locked_until'] = $result['locked_until'];
			}
			return $this->ok($data, 401);
		}

		delete_transient($rate_key);

		return $this->ok([
			'success'      => true,
			'camp_id'      => $result['camp_id'],
			'staff_id'     => $result['staff_id'],
			'display_name' => $result['display_name'],
		]);
	}

	public function logout(WP_REST_Request $request): mixed {
		// SEC-04: Weryfikuj nonce dla spójnej ochrony CSRF.
		// X-WP-Nonce (REST) lub parametr 'nonce' (formularz) są akceptowane.
		$nonce = (string) ($request->get_header('X-WP-Nonce') ?? $request->get_param('nonce') ?? '');
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return $this->error('bm_invalid_nonce', __('Nieprawidłowy token bezpieczeństwa. Odśwież stronę.', 'basemgmt'), 403);
		}

		SessionManager::destroy();
		return $this->ok(['success' => true]);
	}

	public function status(WP_REST_Request $request): mixed {
		$session = SessionManager::current();
		if ( ! $session ) {
			return $this->ok(['authenticated' => false]);
		}

		return $this->ok([
			'authenticated' => true,
			'camp_id'       => (int) $session->camp_id,
			'staff_id'      => (int) $session->staff_id,
			'expires_at'    => $session->expires_at,
		]);
	}

	private function login_rate_limit_key(int $camp_id, int $staff_id): string {
		$ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
		return 'bm_login_rl_' . md5($ip . '|' . $camp_id . '|' . $staff_id);
	}

	private function is_rate_limited(string $key): bool {
		return (int) get_transient($key) >= self::LOGIN_LIMIT_ATTEMPTS;
	}

	private function bump_rate_limit(string $key): void {
		$attempts = (int) get_transient($key);
		set_transient($key, $attempts + 1, self::LOGIN_LIMIT_WINDOW);
	}
}
