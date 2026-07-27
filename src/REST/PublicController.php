<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Auth\FrontendAuth;
use WP_REST_Request;

defined('ABSPATH') || exit;

/**
 * Public (unauthenticated) endpoints used by the access screen.
 *
 * GET /bm/v1/public/camps          – list of active camps (id + name only)
 * GET /bm/v1/public/camps/{id}/staff – active staff for a camp (id + display_name only)
 */
final class PublicController extends BaseController {

	public function register_routes(): void {
		register_rest_route(self::NAMESPACE, '/public/camps', [
			'methods'             => 'GET',
			'callback'            => [$this, 'list_camps'],
			'permission_callback' => '__return_true',
		]);

		register_rest_route(self::NAMESPACE, '/public/camps/(?P<id>\d+)/staff', [
			'methods'             => 'GET',
			'callback'            => [$this, 'list_staff'],
			'permission_callback' => '__return_true',
			'args'                => [
				'id' => ['required' => true, 'sanitize_callback' => 'absint'],
			],
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function list_camps(WP_REST_Request $request): mixed {
		return $this->ok(FrontendAuth::get_active_camps());
	}

	public function list_staff(WP_REST_Request $request): mixed {
		$camp_id = (int) $request->get_param('id');
		return $this->ok(FrontendAuth::get_active_staff_for_camp($camp_id));
	}
}
