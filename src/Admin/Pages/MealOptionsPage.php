<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Menu\MealOptionRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for managing predefined meal diets and serving locations.
 */
final class MealOptionsPage {

	public function render(): void {
		Capabilities::require_admin();

		$tab = sanitize_key($_GET['tab'] ?? 'diets');

		$diets     = MealOptionRepository::get_all_diets();
		$locations = MealOptionRepository::get_all_locations();

		include BASEMGMT_DIR . 'templates/admin/menu/options.php';
	}

	// ── Diet handlers ─────────────────────────────────────────────────────────

	public function handle_save_diet(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_meal_diet');

		$id   = (int) ($_POST['diet_id'] ?? 0);
		$name = sanitize_text_field($_POST['diet_name'] ?? '');

		if ( $name ) {
			MealOptionRepository::save_diet([
				'id'         => $id,
				'name'       => $name,
				'sort_order' => (int) ($_POST['sort_order'] ?? 0),
			]);
			AdminMenu::set_notice(__('Dieta zapisana.', 'basemgmt'));
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-meal-options&tab=diets'));
		exit;
	}

	public function handle_delete_diet(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer('bm_delete_diet_' . $id);
		if ( $id ) {
			MealOptionRepository::delete_diet($id);
			AdminMenu::set_notice(__('Dieta usunięta.', 'basemgmt'));
		}
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-meal-options&tab=diets'));
		exit;
	}

	// ── Location handlers ─────────────────────────────────────────────────────

	public function handle_save_location(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_meal_location');

		$id   = (int) ($_POST['location_id'] ?? 0);
		$name = sanitize_text_field($_POST['location_name'] ?? '');

		if ( $name ) {
			MealOptionRepository::save_location([
				'id'         => $id,
				'name'       => $name,
				'sort_order' => (int) ($_POST['sort_order'] ?? 0),
			]);
			AdminMenu::set_notice(__('Miejsce wydawania zapisane.', 'basemgmt'));
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-meal-options&tab=locations'));
		exit;
	}

	public function handle_delete_location(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer('bm_delete_location_' . $id);
		if ( $id ) {
			MealOptionRepository::delete_location($id);
			AdminMenu::set_notice(__('Miejsce usunięte.', 'basemgmt'));
		}
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-meal-options&tab=locations'));
		exit;
	}
}
