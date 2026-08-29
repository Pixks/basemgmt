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
		$known_servers = \BaseMgmt\License\LicenseClient::known_servers();

		// Extra fields from the licensemanager API.
		$plan            = $manager->get_plan();
		$active_channel  = $manager->get_active_channel();
		$allowed_channels = $manager->get_allowed_channels();
		$updates_allowed = $manager->updates_allowed();
		$support_active  = $manager->support_active();

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
		$channel     = sanitize_key($_POST['update_channel'] ?? 'stable');

		// If "custom" server was selected, use the custom URL field instead.
		$server_preset = sanitize_key($_POST['license_server_preset'] ?? 'custom');
		if ( 'custom' !== $server_preset ) {
			$presets = \BaseMgmt\License\LicenseClient::known_servers();
			$api_url = $presets[$server_preset] ?? $api_url;
		}

		if ( '' === $api_url || '' === $license_key ) {
			AdminMenu::set_notice(__('Podaj URL serwera licencji oraz klucz licencji.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-license'));
			exit;
		}

		$client->save_api_base($api_url);
		$client->save_license_key($license_key);
		$client->save_update_channel($channel);

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

	/**
	 * Forces a fresh check of the license status (clears transient cache).
	 */
	public function handle_refresh(): void {
		if ( ! current_user_can('manage_options') ) {
			wp_die(esc_html__('Brak uprawnień.', 'basemgmt'), esc_html__('Błąd', 'basemgmt'), ['response' => 403]);
		}
		check_admin_referer('bm_refresh_license');

		$status = LicenseManager::instance()->get_status(true);

		if ( ! empty($status['success']) ) {
			AdminMenu::set_notice(__('Status licencji odświeżony pomyślnie.', 'basemgmt'));
		} else {
			$msg = sanitize_text_field($status['error']['message'] ?? __('Nie udało się odświeżyć statusu.', 'basemgmt'));
			AdminMenu::set_notice(esc_html($msg), 'error');
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-license'));
		exit;
	}
}
