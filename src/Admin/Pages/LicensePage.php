<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\License\LicenseManager;

defined('ABSPATH') || exit;

/**
 * Admin page for managing the CampLink license.
 * Allows entering the license key, activating / deactivating,
 * and viewing the current license status.
 */
final class LicensePage {

	public function render(): void {
		if ( ! current_user_can('manage_options') ) {
			wp_die(esc_html__('Brak uprawnień.', 'basemgmt'), esc_html__('Błąd', 'basemgmt'), ['response' => 403]);
		}

		$manager = LicenseManager::instance();
		$client  = $manager->client();
		$status  = $manager->get_status();

		include BASEMGMT_DIR . 'templates/admin/license/index.php';
	}

	// ── Form handlers ─────────────────────────────────────────────────────────

	public function handle_activate(): void {
		if ( ! current_user_can('manage_options') ) {
			wp_die(esc_html__('Brak uprawnień.', 'basemgmt'), esc_html__('Błąd', 'basemgmt'), ['response' => 403]);
		}
		check_admin_referer('bm_activate_license');

		$client = LicenseManager::instance()->client();

		$api_url     = sanitize_text_field(wp_unslash($_POST['license_api_url'] ?? ''));
		$license_key = sanitize_text_field(wp_unslash($_POST['license_key'] ?? ''));

		if ( '' === $api_url || '' === $license_key ) {
			AdminMenu::set_notice(__('Podaj URL serwera licencji oraz klucz licencji.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-license'));
			exit;
		}

		$client->save_api_base($api_url);
		$client->save_license_key($license_key);

		$response = $client->activate();

		if ( ! empty($response['success']) ) {
			AdminMenu::set_notice(__('Licencja aktywowana pomyślnie.', 'basemgmt'));
		} else {
			$msg = $response['error']['message'] ?? __('Aktywacja nie powiodła się.', 'basemgmt');
			AdminMenu::set_notice(esc_html($msg), 'error');
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-license'));
		exit;
	}

	public function handle_deactivate(): void {
		if ( ! current_user_can('manage_options') ) {
			wp_die(esc_html__('Brak uprawnień.', 'basemgmt'), esc_html__('Błąd', 'basemgmt'), ['response' => 403]);
		}
		check_admin_referer('bm_deactivate_license');

		$response = LicenseManager::instance()->client()->deactivate();

		if ( ! empty($response['success']) ) {
			AdminMenu::set_notice(__('Licencja dezaktywowana.', 'basemgmt'));
		} else {
			$msg = $response['error']['message'] ?? __('Dezaktywacja nie powiodła się.', 'basemgmt');
			AdminMenu::set_notice(esc_html($msg), 'error');
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-license'));
		exit;
	}
}
