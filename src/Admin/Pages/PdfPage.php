<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Camps\DailyCountRepository;
use BaseMgmt\Modules\Menu\MealRepository;
use BaseMgmt\Modules\Schedule\ScheduleRepository;

defined('ABSPATH') || exit;

/**
 * Generates print-friendly HTML pages that can be saved as PDF by the browser.
 *
 * Accessed via: admin.php?page=basemgmt-pdf&type=X&date=Y-m-d
 *   type=camps     – current camp status summary
 *   type=schedule  – today's day schedule for a given date
 *   type=menu      – meal menu for a given date
 */
final class PdfPage {

	public function render(): void {
		Capabilities::require_admin();

		$type = sanitize_key($_GET['type'] ?? 'camps');
		$date = sanitize_text_field($_GET['date'] ?? gmdate('Y-m-d'));

		match ($type) {
			'schedule' => $this->render_schedule($date),
			'menu'     => $this->render_menu($date),
			default    => $this->render_camps($date),
		};
	}

	// ── List view (entry point in admin sidebar) ──────────────────────────────

	public function render_list(): void {
		Capabilities::require_admin();
		$today = gmdate('Y-m-d');
		include BASEMGMT_DIR . 'templates/admin/pdf/index.php';
	}

	// ── PDF type handlers ─────────────────────────────────────────────────────

	private function render_camps(string $date): void {
		$summary       = CampRepository::active_summary();
		$camps         = CampRepository::get_all(['status' => 'active']);
		$report_totals = DailyCountRepository::daily_totals($date);
		$missing_camps = DailyCountRepository::get_missing_camps_for_date($date);

		// Per-camp counts for the given date.
		$camp_counts = [];
		foreach ( $camps as $camp ) {
			$camp_counts[$camp->id] = DailyCountRepository::get_by_date((int) $camp->id, $date);
		}

		$generated_at = current_time('d.m.Y H:i');
		include BASEMGMT_DIR . 'templates/admin/pdf/camps.php';
	}

	private function render_schedule(string $date): void {
		$plans = ScheduleRepository::get_all_headers(['date' => $date, 'status' => 'active']);

		$plan_data = [];
		foreach ( $plans as $plan ) {
			$plan_data[] = [
				'header' => $plan,
				'items'  => ScheduleRepository::get_items((int) $plan->id),
			];
		}

		$generated_at = current_time('d.m.Y H:i');
		include BASEMGMT_DIR . 'templates/admin/pdf/schedule.php';
	}

	private function render_menu(string $date): void {
		$day_data     = MealRepository::get_day_for_frontend($date);
		$generated_at = current_time('d.m.Y H:i');
		include BASEMGMT_DIR . 'templates/admin/pdf/menu.php';
	}
}
