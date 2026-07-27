<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Modules\Menu\MealRepository;
use BaseMgmt\Modules\Camps\CampRepository;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Meal Menu REST endpoints – all require a valid camp session.
 *
 * GET /bm/v1/panel/menu?date=YYYY-MM-DD          – menu for a specific date
 * GET /bm/v1/panel/menu/dates                    – dates with published menu in camp's period
 * GET /bm/v1/panel/menu/week?from=YYYY-MM-DD     – 7-day menu from a start date
 */
final class MenuController extends BaseController {

	public function register_routes(): void {
		$auth = fn(WP_REST_Request $r) => $this->require_session($r);

		register_rest_route(self::NAMESPACE, '/panel/menu', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_menu'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/menu/dates', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_dates'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/menu/week', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_week'],
			'permission_callback' => $auth,
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function get_menu(WP_REST_Request $request): WP_REST_Response {
		$date = sanitize_text_field($request->get_param('date') ?? gmdate('Y-m-d'));
		if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ) {
			$date = gmdate('Y-m-d');
		}

		$day = MealRepository::get_day_for_frontend($date);

		return new WP_REST_Response([
			'date'  => $date,
			'day'   => $day ? $this->format_day($day) : null,
		]);
	}

	public function get_dates(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');
		$camp    = CampRepository::get($camp_id);

		if ( ! $camp ) {
			return $this->error('not_found', __('Obóz nie znaleziony.', 'basemgmt'), 404);
		}

		$today = gmdate('Y-m-d');
		$from  = max($camp->start_date, $today);
		$to    = $camp->end_date;

		$dates = MealRepository::get_dates_in_range($from, $to);

		return new WP_REST_Response(['dates' => $dates]);
	}

	public function get_week(WP_REST_Request $request): WP_REST_Response {
		$from = sanitize_text_field($request->get_param('from') ?? gmdate('Y-m-d'));
		if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ) {
			$from = gmdate('Y-m-d');
		}

		$to   = gmdate('Y-m-d', strtotime($from . ' +6 days'));
		$days = [];

		foreach ( MealRepository::get_dates_in_range($from, $to) as $date ) {
			$day = MealRepository::get_day_for_frontend($date);
			if ( $day ) {
				$days[] = $this->format_day($day);
			}
		}

		return new WP_REST_Response(['from' => $from, 'to' => $to, 'days' => $days]);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function format_day(array $day): array {
		return [
			'id'        => (int) $day['id'],
			'meal_date' => $day['meal_date'],
			'notes'     => $day['notes'],
			'items'     => array_map([$this, 'format_item'], $day['items']),
		];
	}

	private function format_item(object $item): array {
		return [
			'id'               => (int) $item->id,
			'meal_type'        => $item->meal_type,
			'meal_type_label'  => MealRepository::MEAL_TYPES[$item->meal_type] ?? $item->meal_type,
			'time_from'        => $item->time_from,
			'title'            => $item->title,
			'description'      => $item->description,
			'location'         => $item->location,
			'diet_info'        => $item->diet_info,
			'allergens'        => $item->allergens,
			'sort_order'       => (int) $item->sort_order,
			'is_new_today'     => (bool) $item->is_new_today,
			'is_updated_today' => (bool) $item->is_updated_today,
		];
	}
}
