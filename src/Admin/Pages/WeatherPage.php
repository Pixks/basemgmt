<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Cron\Scheduler;
use BaseMgmt\Modules\Weather\ImgwAlertsSync;
use BaseMgmt\Modules\Weather\WeatherAlertRepository;
use BaseMgmt\Modules\Weather\WeatherService;

defined('ABSPATH') || exit;

/**
 * Admin page for Pogoda (Weather) module.
 */
final class WeatherPage {

	public function render(): void {
		Capabilities::require_admin();

		$action = sanitize_key($_GET['bm_action'] ?? '');

		match ($action) {
			'edit_alert'   => $this->render_alert_form((int) ($_GET['id'] ?? 0)),
			'new_alert'    => $this->render_alert_form(0),
			default        => $this->render_main(),
		};
	}

	private function render_main(): void {
		$settings      = WeatherService::get_settings();
		$imgw_settings = ImgwAlertsSync::get_settings();
		$imgw_last_sync= ImgwAlertsSync::get_last_sync();
		$imgw_last_log = ImgwAlertsSync::get_last_log();
		$service       = new WeatherService();
		$weather       = $service->get_weather();
		$alerts        = WeatherAlertRepository::get_all();
		$voivodeships  = ImgwAlertsSync::voivodeships();
		$all_counties  = ImgwAlertsSync::COUNTIES;

		include BASEMGMT_DIR . 'templates/admin/weather/index.php';
	}

	private function render_alert_form(int $id): void {
		$alert = $id > 0 ? WeatherAlertRepository::get_by_id($id) : null;

		include BASEMGMT_DIR . 'templates/admin/weather/alert_edit.php';
	}

	// ── Form handlers ─────────────────────────────────────────────────────────

	public function handle_save_settings(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_weather_settings');

		WeatherService::save_settings([
			'latitude'      => sanitize_text_field($_POST['latitude']      ?? ''),
			'longitude'     => sanitize_text_field($_POST['longitude']     ?? ''),
			'location_name' => sanitize_text_field($_POST['location_name'] ?? ''),
			'timezone'      => sanitize_text_field($_POST['timezone']      ?? 'Europe/Warsaw'),
		]);

		// Save IMGW settings.
		ImgwAlertsSync::save_settings($_POST);

		// Reschedule IMGW cron based on new settings.
		Scheduler::reschedule_imgw_sync();

		// Clear weather cache so new location is fetched.
		WeatherService::clear_cache();

		AdminMenu::set_notice(__('Ustawienia pogody zapisane.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-weather'));
		exit;
	}

	public function handle_save_alert(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_weather_alert');

		$id          = (int) ($_POST['alert_id']   ?? 0);
		$valid_from  = sanitize_text_field($_POST['valid_from']  ?? '');
		$valid_until = sanitize_text_field($_POST['valid_until'] ?? '');

		$data = [
			'title'       => sanitize_text_field($_POST['title']    ?? ''),
			'message'     => sanitize_textarea_field($_POST['message'] ?? ''),
			'type'        => sanitize_key($_POST['type']             ?? WeatherAlertRepository::TYPE_INFO),
			'source'      => WeatherAlertRepository::SOURCE_MANUAL,
			'is_active'   => (int) isset($_POST['is_active']),
			'is_urgent'   => (int) isset($_POST['is_urgent']),
			'valid_from'  => $valid_from  ?: null,
			'valid_until' => $valid_until ?: null,
		];

		if ( $id > 0 ) {
			WeatherAlertRepository::update($id, $data);
			AdminMenu::set_notice(__('Komunikat zaktualizowany.', 'basemgmt'));
		} else {
			WeatherAlertRepository::create($data);
			AdminMenu::set_notice(__('Komunikat dodany.', 'basemgmt'));
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-weather'));
		exit;
	}

	public function handle_delete_alert(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_delete_alert_' . (int) ($_GET['id'] ?? 0));

		WeatherAlertRepository::delete((int) ($_GET['id'] ?? 0));
		AdminMenu::set_notice(__('Komunikat usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-weather'));
		exit;
	}

	public function handle_refresh_weather(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_refresh_weather');

		WeatherService::clear_cache();
		$service = new WeatherService();
		$data    = $service->get_weather(true);

		if ( $data ) {
			AdminMenu::set_notice(__('Dane pogodowe odświeżone.', 'basemgmt'));
		} else {
			AdminMenu::set_notice(__('Nie udało się pobrać danych pogodowych. Sprawdź ustawienia lokalizacji.', 'basemgmt'), 'warning');
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-weather'));
		exit;
	}

	public function handle_sync_imgw(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_sync_imgw');

		$sync = new ImgwAlertsSync();
		$log  = $sync->sync();

		if ( $log['error'] ) {
			AdminMenu::set_notice('IMGW: ' . $log['error'], 'error');
		} else {
			AdminMenu::set_notice(
				sprintf(
					__('Synchronizacja IMGW: pobrano %d, dodano %d, zaktualizowano %d.', 'basemgmt'),
					$log['fetched'],
					$log['inserted'],
					$log['updated']
				)
			);
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-weather'));
		exit;
	}
}
