<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Camps\DailyCountRepository;
use BaseMgmt\Core\PdfSettings;
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
		$this->render_list();
	}

	// ── List view (entry point in admin sidebar) ──────────────────────────────

	public function render_list(): void {
		Capabilities::require_admin();

		$today = gmdate('Y-m-d');
		include BASEMGMT_DIR . 'templates/admin/pdf/index.php';
	}

	public function handle_render(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_render_pdf');

		$type = sanitize_key($_REQUEST['type'] ?? 'camps');
		$date = sanitize_text_field($_REQUEST['date'] ?? gmdate('Y-m-d'));

		if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ) {
			$date = gmdate('Y-m-d');
		}

		match ($type) {
			'schedule' => $this->render_schedule($date),
			'menu'     => $this->render_menu($date),
			default    => $this->render_camps($date),
		};

		exit;
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
		$title        = __('Stany osobowe obozów', 'basemgmt');
		$content      = $this->capture(BASEMGMT_DIR . 'templates/admin/pdf/camps.php', compact('date', 'summary', 'camps', 'report_totals', 'missing_camps', 'camp_counts', 'generated_at'));
		$this->render_document($title, $date, $content);
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
		$title        = __('Plan dnia', 'basemgmt');
		$content      = $this->capture(BASEMGMT_DIR . 'templates/admin/pdf/schedule.php', compact('date', 'plan_data', 'generated_at'));
		$this->render_document($title, $date, $content);
	}

	private function render_menu(string $date): void {
		$day_data     = MealRepository::get_day_for_frontend($date);
		$generated_at = current_time('d.m.Y H:i');
		$title        = __('Jadłospis', 'basemgmt');
		$content      = $this->capture(BASEMGMT_DIR . 'templates/admin/pdf/menu.php', compact('date', 'day_data', 'generated_at'));
		$this->render_document($title, $date, $content);
	}

	private function render_document(string $title, string $date, string $content): void {
		$settings = PdfSettings::get_settings();
		$formatted_date = date_i18n('d.m.Y', strtotime($date));
		include BASEMGMT_DIR . 'templates/admin/pdf/base.php';
	}

	private function capture(string $file, array $vars): string {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract($vars, EXTR_SKIP);
		ob_start();
		include $file;
		return (string) ob_get_clean();
	}
}
