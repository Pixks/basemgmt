<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Modules\Camps\DailyCountRepository;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Reports endpoints – all require a valid camp session.
 *
 * GET  /bm/v1/panel/reports/today    – today's report for own camp (prefill data)
 * POST /bm/v1/panel/reports/save     – save draft report
 * POST /bm/v1/panel/reports/submit   – submit (finalise) today's report
 * GET  /bm/v1/panel/reports/history  – own camp history (last 30)
 */
final class ReportsController extends BaseController {

	public function register_routes(): void {
		$auth = fn(WP_REST_Request $r) => $this->require_session($r);

		register_rest_route(self::NAMESPACE, '/panel/reports/today', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_today'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/reports/save', [
			'methods'             => 'POST',
			'callback'            => [$this, 'save_draft'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/reports/submit', [
			'methods'             => 'POST',
			'callback'            => [$this, 'submit_report'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/reports/history', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_history'],
			'permission_callback' => $auth,
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function get_today(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');
		$today   = gmdate('Y-m-d');

		// Today's record if it exists, else yesterday's data for prefill.
		$today_row     = DailyCountRepository::get_by_date($camp_id, $today);
		$yesterday_row = DailyCountRepository::get_latest($camp_id);

		$prefill = null;
		if ( $yesterday_row && $yesterday_row->count_date !== $today ) {
			$prefill = [
				'participants' => (int) $yesterday_row->participants,
				'staff'        => (int) $yesterday_row->staff,
				'workers'      => (int) $yesterday_row->workers,
			];
		}

		return new WP_REST_Response([
			'today'   => $today_row ? $this->format_report($today_row) : null,
			'prefill' => $prefill,
			'date'    => $today,
		]);
	}

	public function save_draft(WP_REST_Request $request): WP_REST_Response {
		$camp_id  = (int) $request->get_param('_camp_id');
		$staff_id = (int) $request->get_param('_staff_id');
		$today    = gmdate('Y-m-d');

		$existing = DailyCountRepository::get_by_date($camp_id, $today);
		if ( $existing && $existing->status === DailyCountRepository::STATUS_SUBMITTED ) {
			return $this->error('report_already_submitted', __('Meldunek został już wysłany.', 'basemgmt'), 409);
		}

		$ok = DailyCountRepository::upsert(
			$camp_id,
			$today,
			(int) $request->get_param('participants'),
			(int) $request->get_param('staff'),
			(int) $request->get_param('workers'),
			sanitize_textarea_field($request->get_param('notes') ?? ''),
			$staff_id,
			DailyCountRepository::STATUS_DRAFT
		);

		if ( ! $ok ) {
			return $this->error('save_failed', __('Błąd zapisu. Spróbuj ponownie.', 'basemgmt'), 500);
		}

		$row = DailyCountRepository::get_by_date($camp_id, $today);
		return new WP_REST_Response(['report' => $this->format_report($row)], 200);
	}

	public function submit_report(WP_REST_Request $request): WP_REST_Response {
		$camp_id  = (int) $request->get_param('_camp_id');
		$staff_id = (int) $request->get_param('_staff_id');
		$today    = gmdate('Y-m-d');

		// Ensure we have data before submitting (save first if needed).
		$existing = DailyCountRepository::get_by_date($camp_id, $today);

		if ( ! $existing ) {
			// Save fresh data then submit.
			DailyCountRepository::upsert(
				$camp_id,
				$today,
				(int) $request->get_param('participants'),
				(int) $request->get_param('staff'),
				(int) $request->get_param('workers'),
				sanitize_textarea_field($request->get_param('notes') ?? ''),
				$staff_id,
				DailyCountRepository::STATUS_DRAFT
			);
		} elseif ( $existing->status === DailyCountRepository::STATUS_SUBMITTED ) {
			return $this->error('report_already_submitted', __('Meldunek został już wysłany.', 'basemgmt'), 409);
		} else {
			// Update numbers before submitting.
			DailyCountRepository::upsert(
				$camp_id,
				$today,
				(int) $request->get_param('participants'),
				(int) $request->get_param('staff'),
				(int) $request->get_param('workers'),
				sanitize_textarea_field($request->get_param('notes') ?? ''),
				$staff_id,
				DailyCountRepository::STATUS_DRAFT
			);
		}

		$ok = DailyCountRepository::submit($camp_id, $today, $staff_id);
		if ( ! $ok ) {
			return $this->error('submit_failed', __('Błąd wysyłki. Spróbuj ponownie.', 'basemgmt'), 500);
		}

		$row = DailyCountRepository::get_by_date($camp_id, $today);
		return new WP_REST_Response(['report' => $this->format_report($row)], 200);
	}

	public function get_history(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');
		$limit   = min((int) ($request->get_param('limit') ?? 30), 90);
		$rows    = DailyCountRepository::get_history($camp_id, $limit);

		return new WP_REST_Response([
			'reports' => array_map([$this, 'format_report'], $rows),
		]);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function format_report(object $row): array {
		return [
			'id'           => (int) $row->id,
			'count_date'   => $row->count_date,
			'participants' => (int) $row->participants,
			'staff'        => (int) $row->staff,
			'workers'      => (int) $row->workers,
			'notes'        => $row->notes ?? '',
			'status'       => $row->status,
			'submitted_at' => $row->submitted_at ?? null,
			'submitted_by' => (int) ($row->submitted_by ?? 0),
			'updated_at'   => $row->updated_at,
		];
	}
}
