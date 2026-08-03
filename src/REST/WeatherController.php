<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Modules\Weather\WeatherAlertRepository;
use BaseMgmt\Modules\Weather\WeatherService;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Weather endpoints.
 *
 * GET /bm/v1/public/weather          – weather data + active alerts (no auth required)
 * POST /bm/v1/admin/weather/refresh  – force refresh (admin only)
 */
final class WeatherController extends BaseController {

	public function register_routes(): void {
		$auth = fn(WP_REST_Request $r) => $this->require_session($r);

		// Weather data is only available to authenticated camp staff.
		register_rest_route(self::NAMESPACE, '/panel/weather', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_weather'],
			'permission_callback' => $auth,
		]);

		// Admin-only force refresh.
		register_rest_route(self::NAMESPACE, '/admin/weather/refresh', [
			'methods'             => 'POST',
			'callback'            => [$this, 'force_refresh'],
			'permission_callback' => fn() => current_user_can('manage_basemgmt'),
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function get_weather(WP_REST_Request $request): WP_REST_Response {
		$service = new WeatherService();
		$weather = $service->get_weather();
		$alerts  = WeatherAlertRepository::get_active();

		$formatted_alerts = array_map(static function (object $a): array {
			return [
				'id'          => (int) $a->id,
				'title'       => $a->title,
				'message'     => $a->message,
				'type'        => $a->type,
				'is_urgent'   => (bool) $a->is_urgent,
				'valid_until' => $a->valid_until,
			];
		}, $alerts);

		return new WP_REST_Response([
			'weather'   => $weather,
			'alerts'    => $formatted_alerts,
			'location'  => WeatherService::get_settings()['location_name'] ?? '',
			'configured'=> WeatherService::is_configured(),
		]);
	}

	public function force_refresh(WP_REST_Request $request): WP_REST_Response {
		check_ajax_referer('wp_rest');

		$service = new WeatherService();
		$service->refresh();
		WeatherService::clear_cache();
		$weather = $service->get_weather(true);

		return new WP_REST_Response([
			'success' => $weather !== null,
			'weather' => $weather,
		]);
	}
}
