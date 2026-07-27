<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Core\EmailService;

defined('ABSPATH') || exit;

/**
 * Global plugin settings page (email templates, notifications, etc.)
 */
final class SettingsPage {

	public function render(): void {
		Capabilities::require_admin();
		include BASEMGMT_DIR . 'templates/admin/settings/index.php';
	}

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_settings');

		EmailService::save_settings($_POST);

		AdminMenu::set_notice(__('Ustawienia zapisane.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-settings'));
		exit;
	}

	public function handle_send_test(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_send_test_email');

		$to = sanitize_email($_POST['test_email'] ?? get_option('admin_email'));
		$ok = EmailService::send(
			$to,
			EmailService::subject(__('Test powiadomień email', 'basemgmt')),
			'reservation_created',
			[
				'reservation'   => [
					'res_date'   => gmdate('Y-m-d'),
					'start_time' => '10:00',
					'end_time'   => '12:00',
					'purpose'    => __('Testowa rezerwacja', 'basemgmt'),
				],
				'resource_name' => __('Boisko (TEST)', 'basemgmt'),
				'camp_name'     => __('Obóz Testowy', 'basemgmt'),
				'is_admin'      => false,
				'subject'       => '',
			]
		);

		$msg = $ok
			? sprintf(__('Testowy email wysłany na %s.', 'basemgmt'), $to)
			: __('Wysyłka nie powiodła się – sprawdź konfigurację serwera pocztowego.', 'basemgmt');
		AdminMenu::set_notice($msg, $ok ? 'success' : 'error');
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-settings'));
		exit;
	}
}
