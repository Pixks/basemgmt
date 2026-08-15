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

		// Validate code format before touching the DB.
		if ( ! preg_match('/^\d{6}$/', $code) ) {
			return $this->ok(['success' => false, 'message' => __('Nieprawidłowe dane logowania.', 'basemgmt')], 401);
		}

		$result = FrontendAuth::attempt($camp_id, $staff_id, $code);

		if ( ! $result['success'] ) {
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
			'expires_at'    => $session->expires_at,
		]);
	}
}
