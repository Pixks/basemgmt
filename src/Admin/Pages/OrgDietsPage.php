<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Admin page for managing diet definitions with per-meal-slot costs.
 * Lives under Organizacja → Diety in the admin menu.
 */
final class OrgDietsPage {

	/** Ordered list of meal slots shown in the edit form. */
	public static function meal_slots(): array {
		return [
			'sniadanie'         => __('Śniadanie', 'basemgmt'),
			'drugie_sniadanie'  => __('Drugie śniadanie', 'basemgmt'),
			'obiad'             => __('Obiad', 'basemgmt'),
			'podwieczorek'      => __('Podwieczorek', 'basemgmt'),
			'kolacja'           => __('Kolacja', 'basemgmt'),
		];
	}

	// ── Render ────────────────────────────────────────────────────────────────

	public function render(): void {
		Capabilities::require_admin();
		$action = sanitize_key($_GET['action'] ?? '');
		$id     = (int) ($_GET['id'] ?? 0);

		if ( in_array($action, ['new', 'edit'], true) ) {
			$diet       = $id ? $this->get_one($id) : null;
			$diet_costs = $id ? self::get_costs($id) : [];
			include BASEMGMT_DIR . 'templates/admin/org/diets/edit.php';
		} else {
			$diets = $this->get_all();
			include BASEMGMT_DIR . 'templates/admin/org/diets/list.php';
		}
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_diet');
		global $wpdb;

		$id   = (int) ($_POST['diet_id'] ?? 0);
		$name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));

		if ( empty($name) ) {
			AdminMenu::set_notice(__('Nazwa diety jest wymagana.', 'basemgmt'), 'error');
			$this->redirect($id ? "basemgmt-org-diets&action=edit&id={$id}" : 'basemgmt-org-diets&action=new');
			return;
		}

		$data = [
			'name'       => $name,
			'diet_info'  => sanitize_textarea_field(wp_unslash($_POST['diet_info'] ?? '')),
			'sort_order' => (int) ($_POST['sort_order'] ?? 0),
		];

		$diet_table = Schema::table('meal_diets');
		if ( $id > 0 ) {
			$wpdb->update($diet_table, $data, ['id' => $id]);
		} else {
			$wpdb->insert($diet_table, $data);
			$id = (int) $wpdb->insert_id;
		}

		// Save per-meal-slot default costs.
		$costs_table = Schema::table('meal_diet_costs');
		foreach ( self::meal_slots() as $slot_key => $_ ) {
			$netto = round((float) str_replace(',', '.', $_POST['slot_price'][$slot_key] ?? '0'), 2);
			$vat   = round((float) str_replace(',', '.', $_POST['slot_vat'][$slot_key] ?? '0'), 2);
			$existing = $wpdb->get_var($wpdb->prepare(
				"SELECT id FROM {$costs_table} WHERE diet_id = %d AND meal_slot = %s", $id, $slot_key
			));
			if ( $existing ) {
				$wpdb->update($costs_table, ['cost_netto' => $netto, 'vat_rate' => $vat], ['id' => $existing]);
			} else {
				$wpdb->insert($costs_table, ['diet_id' => $id, 'meal_slot' => $slot_key, 'cost_netto' => $netto, 'vat_rate' => $vat]);
			}
		}

		AdminMenu::set_notice(__('Dieta zapisana.', 'basemgmt'));
		$this->redirect("basemgmt-org-diets");
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_diet_{$id}");
		global $wpdb;

		$wpdb->delete(Schema::table('meal_diet_costs'), ['diet_id' => $id]);
		$wpdb->delete(Schema::table('meal_diets'), ['id' => $id]);

		AdminMenu::set_notice(__('Dieta usunięta.', 'basemgmt'));
		$this->redirect('basemgmt-org-diets');
	}

	// ── Static helpers (used by other pages) ──────────────────────────────────

	public static function get_all(): array {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM " . Schema::table('meal_diets') . " ORDER BY sort_order ASC, name ASC"
		) ?: [];
	}

	public static function get_costs(int $diet_id): array {
		global $wpdb;
		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT meal_slot, cost_netto, vat_rate FROM " . Schema::table('meal_diet_costs') . " WHERE diet_id = %d",
			$diet_id
		));
		// Return as [slot_key => stdClass]
		$map = [];
		foreach ( $rows as $row ) {
			$map[$row->meal_slot] = $row;
		}
		return $map;
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	private function get_one(int $id): ?object {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . Schema::table('meal_diets') . " WHERE id = %d",
			$id
		)) ?: null;
	}

	private function redirect(string $page): void {
		wp_safe_redirect(admin_url("admin.php?page={$page}"));
		exit;
	}
}
