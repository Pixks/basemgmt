<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Admin page for managing accommodation types and their nightly rates.
 */
final class OrgAccommodationsPage {

	public function render(): void {
		Capabilities::require_admin();
		$action = sanitize_key($_GET['action'] ?? '');
		$id     = (int) ($_GET['id'] ?? 0);

		if ( in_array($action, ['new', 'edit'], true) ) {
			$item = $id ? $this->get_one($id) : null;
			include BASEMGMT_DIR . 'templates/admin/org/accommodations/edit.php';
		} else {
			$items = $this->get_all();
			include BASEMGMT_DIR . 'templates/admin/org/accommodations/index.php';
		}
	}

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_accommodation_type');
		global $wpdb;

		$id   = (int) ($_POST['accom_id'] ?? 0);
		$name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));

		if ( empty($name) ) {
			AdminMenu::set_notice(__('Nazwa jest wymagana.', 'basemgmt'), 'error');
			$this->redirect($id ? "basemgmt-org-accommodations&action=edit&id={$id}" : 'basemgmt-org-accommodations&action=new');
			return;
		}

		$data = [
			'name'          => $name,
			'description'   => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
			'rate_per_night'=> round((float) str_replace(',', '.', $_POST['rate_per_night'] ?? '0'), 2),
			'default_vat'   => round((float) str_replace(',', '.', $_POST['default_vat'] ?? '0'), 2),
			'sort_order'    => (int) ($_POST['sort_order'] ?? 0),
		];

		if ( $id > 0 ) {
			$wpdb->update(Schema::table('accommodation_types'), $data, ['id' => $id]);
		} else {
			$wpdb->insert(Schema::table('accommodation_types'), $data);
		}

		AdminMenu::set_notice(__('Typ noclegu zapisany.', 'basemgmt'));
		$this->redirect('basemgmt-org-accommodations');
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_accommodation_type_{$id}");

		if ( $id > 0 ) {
			global $wpdb;
			$wpdb->delete(Schema::table('accommodation_types'), ['id' => $id]);
		}

		AdminMenu::set_notice(__('Typ noclegu usunięty.', 'basemgmt'));
		$this->redirect('basemgmt-org-accommodations');
	}

	public static function get_all(): array {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM " . Schema::table('accommodation_types') . " ORDER BY sort_order ASC, id ASC"
		) ?: [];
	}

	private function get_one(int $id): ?object {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . Schema::table('accommodation_types') . " WHERE id = %d",
			$id
		)) ?: null;
	}

	private function redirect(string $page): void {
		wp_safe_redirect(admin_url('admin.php?page=' . $page));
		exit;
	}
}
