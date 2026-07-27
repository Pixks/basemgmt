<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Schedule\ScheduleRepository;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Schedule endpoints – all require a valid camp session.
 *
 * GET  /bm/v1/panel/schedule?date=YYYY-MM-DD         – plan(s) for a date
 * GET  /bm/v1/panel/schedule/dates                   – dates with plans in camp's period
 */
final class ScheduleController extends BaseController {

	public function register_routes(): void {
		$auth = fn(WP_REST_Request $r) => $this->require_session($r);

		register_rest_route(self::NAMESPACE, '/panel/schedule', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_schedule'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/schedule/dates', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_dates'],
			'permission_callback' => $auth,
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function get_schedule(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');
		$date    = sanitize_text_field($request->get_param('date') ?? gmdate('Y-m-d'));

		if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ) {
			$date = gmdate('Y-m-d');
		}

		$plans = ScheduleRepository::get_for_camp_date($camp_id, $date);

		return new WP_REST_Response([
			'date'  => $date,
			'plans' => array_map([$this, 'format_plan'], $plans),
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

		$dates = ScheduleRepository::get_dates_for_camp($camp_id, $from, $to);

		return new WP_REST_Response(['dates' => $dates]);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function format_plan(object $plan): array {
		$items = is_array($plan->items ?? null) ? $plan->items : [];
		return [
			'id'        => (int) $plan->id,
			'plan_date' => $plan->plan_date,
			'title'     => $plan->title,
			'is_global' => (bool) $plan->is_global,
			'items'     => array_map([$this, 'format_item'], $items),
		];
	}

	private function format_item(object $item): array {
		$cat_labels = ScheduleRepository::CATEGORIES;
		return [
			'id'               => (int) $item->id,
			'time_from'        => $item->time_from,
			'time_to'          => $item->time_to,
			'title'            => $item->title,
			'description'      => $item->description,
			'category'         => $item->category,
			'category_label'   => $cat_labels[$item->category] ?? $item->category,
			'item_status'      => $item->item_status,
			'is_mandatory'     => (bool) $item->is_mandatory,
			'is_new_today'     => (bool) $item->is_new_today,
			'is_updated_today' => (bool) $item->is_updated_today,
			'sort_order'       => (int) $item->sort_order,
		];
	}
}
