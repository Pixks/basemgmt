<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Camps\DailyCountRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for Meldunki (Daily Reports) module.
 */
final class ReportsPage {

	public function render(): void {
		Capabilities::require_admin();

		$action  = sanitize_key($_GET['bm_action'] ?? '');
		$date    = sanitize_text_field($_GET['date']    ?? gmdate('Y-m-d'));
		$camp_id = (int) ($_GET['camp_id']              ?? 0);
		$status  = sanitize_key($_GET['status']         ?? '');

		// Validate date format.
		if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ) {
			$date = gmdate('Y-m-d');
		}

		if ( $action === 'view_day' ) {
			$this->render_day_view($date);
			return;
		}

		$camps   = CampRepository::get_all(['status' => 'active']);
		$reports = DailyCountRepository::get_admin_list($date, $camp_id, $status);
		$missing = DailyCountRepository::get_missing_camps_for_date($date);
		$totals  = DailyCountRepository::daily_totals($date);

		include BASEMGMT_DIR . 'templates/admin/reports/list.php';
	}

	private function render_day_view(string $date): void {
		$reports  = DailyCountRepository::get_all_for_date($date);
		$missing  = DailyCountRepository::get_missing_camps_for_date($date);
		$totals   = DailyCountRepository::daily_totals($date);

		include BASEMGMT_DIR . 'templates/admin/reports/day.php';
	}

	/** Handle admin edit/correction of a report. */
	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_report');

		$camp_id  = (int) ($_POST['camp_id']      ?? 0);
		$date     = sanitize_text_field($_POST['count_date'] ?? '');
		$redirect = admin_url('admin.php?page=basemgmt-reports');

		if ( ! $camp_id || ! $date ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane.', 'basemgmt'), 'error');
			wp_safe_redirect($redirect);
			exit;
		}

		$ok = DailyCountRepository::upsert(
			$camp_id,
			$date,
			(int) ($_POST['participants'] ?? 0),
			(int) ($_POST['staff']        ?? 0),
			(int) ($_POST['workers']      ?? 0),
			sanitize_textarea_field($_POST['notes'] ?? ''),
			null,
			sanitize_key($_POST['status'] ?? DailyCountRepository::STATUS_DRAFT)
		);

		if ( $ok ) {
			AdminMenu::set_notice(__('Meldunek zaktualizowany.', 'basemgmt'));
		} else {
			AdminMenu::set_notice(__('Błąd zapisu.', 'basemgmt'), 'error');
		}

		wp_safe_redirect($redirect . '&date=' . $date);
		exit;
	}
}
