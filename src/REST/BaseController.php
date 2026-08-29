<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Auth\SessionManager;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Base class for plugin REST controllers.
 * Provides shared helpers: session auth, JSON success/error responses.
 */
abstract class BaseController {

	protected const NAMESPACE = 'bm/v1';

	abstract public function register_routes(): void;

	// ── Session auth helper ───────────────────────────────────────────────────

	/**
	 * Permission callback for endpoints that require a valid camp session.
	 * Also populates $request['_session'] and $request['_camp_id'] for handlers.
	 */
	protected function require_session(WP_REST_Request $request): bool|\WP_Error {
		$session = SessionManager::current();
		if ( ! $session ) {
			return new \WP_Error('bm_unauthorized', __('Wymagane zalogowanie.', 'basemgmt'), ['status' => 401]);
		}

		// Store on request for use by handler – avoids re-querying DB.
		$request->set_param('_session',  $session);
		$request->set_param('_camp_id',  (int) $session->camp_id);
		$request->set_param('_staff_id', (int) $session->staff_id);

		return true;
	}

	/**
	 * Nonce check for state-changing panel actions.
	 */
	protected function require_panel_nonce(WP_REST_Request $request): bool|\WP_Error {
		$nonce = (string) ($request->get_param('nonce') ?? '');
		if ( ! wp_verify_nonce($nonce, 'bm_panel') ) {
			return new \WP_Error('bm_invalid_nonce', __('Nieprawidłowy token. Odśwież stronę.', 'basemgmt'), ['status' => 403]);
		}

		return true;
	}

	// ── Response helpers ──────────────────────────────────────────────────────

	protected function ok(array $data = [], int $status = 200): WP_REST_Response {
		return new WP_REST_Response($data, $status);
	}

	protected function error(string $code, string $message, int $status = 400): \WP_Error {
		return new \WP_Error($code, $message, ['status' => $status]);
	}
}
