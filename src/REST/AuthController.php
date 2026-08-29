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
		$rate_key = $this->login_rate_limit_key();

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
			// SEC-05: Zamiast dokładnego timestampu zwracamy tylko liczbę sekund do wygaśnięcia
			// (zaokrągloną do pełnych minut), aby nie ułatwiać ataków czasowych.
			'expires_in'    => max( 0, (int) ceil( ( strtotime( $session->expires_at ) - time() ) / 60 ) * 60 ),
		]);
	}

	private function login_rate_limit_key(): string {
		$ip = $this->get_client_ip();
		return 'bm_login_rl_' . md5($ip);
	}

	private function is_rate_limited(string $key): bool {
		$state = get_transient($key);
		if ( ! is_array($state) ) {
			return false;
		}

		$attempts = (int) ($state['attempts'] ?? 0);
		return $attempts >= self::LOGIN_LIMIT_ATTEMPTS;
	}

	private function bump_rate_limit(string $key): void {
		$now   = time();
		$state = get_transient($key);

		if ( ! is_array($state) ) {
			set_transient($key, ['attempts' => 1, 'window_started' => $now], self::LOGIN_LIMIT_WINDOW);
			return;
		}

		$window_started = (int) ($state['window_started'] ?? $now);
		$attempts       = (int) ($state['attempts'] ?? 0) + 1;
		$remaining_ttl  = max(1, self::LOGIN_LIMIT_WINDOW - max(0, $now - $window_started));

		set_transient(
			$key,
			[
				'attempts'       => $attempts,
				'window_started' => $window_started,
			],
			$remaining_ttl
		);
	}

	/**
	 * Uses REMOTE_ADDR by default; deployments behind trusted proxies can
	 * override via 'bm_auth_client_ip' filter after validating headers.
	 */
	private function get_client_ip(): string {
		$ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
		return (string) apply_filters('bm_auth_client_ip', $ip);
	}
}
