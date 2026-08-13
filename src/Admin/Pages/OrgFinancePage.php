<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Database\Schema;
// OrgAccommodationsPage and OrgDietsPage are referenced via FQCN in render_edit()

defined('ABSPATH') || exit;

final class OrgFinancePage {

	// ── Constants ─────────────────────────────────────────────────────────────

	public const LINE_ACCOMMODATION = 'accommodation';
	public const LINE_FOOD          = 'food';
	public const LINE_OTHER         = 'other';
	public const LINE_TAX           = 'tax';
	public const LINE_DEPOSIT       = 'deposit';
	public const LINE_CUSTOM        = 'custom';

	public static function line_types(): array {
		return [
			self::LINE_ACCOMMODATION => __('Nocleg', 'basemgmt'),
			self::LINE_FOOD          => __('Wyżywienie', 'basemgmt'),
			self::LINE_TAX           => __('Podatek', 'basemgmt'),
			self::LINE_CUSTOM        => __('Inne', 'basemgmt'),
		];
	}

	public static function units(): array {
		return [
			'person_night' => __('za osobę', 'basemgmt'),
			'days'         => __('liczba dni', 'basemgmt'),
			'flat'         => __('ryczałt', 'basemgmt'),
		];
	}

	// ── Render ────────────────────────────────────────────────────────────────

	public function render(): void {
		Capabilities::require_admin();

		$action = sanitize_key($_GET['action'] ?? 'list');
		$id     = (int) ($_GET['id'] ?? 0);

		match($action) {
			'edit', 'new' => $this->render_edit($id),
			default       => $this->render_list(),
		};
	}

	private function render_list(): void {
		global $wpdb;
		$table    = Schema::table('payment_packages');
		$packages = $wpdb->get_results("SELECT * FROM {$table} ORDER BY is_default DESC, id DESC");
		include BASEMGMT_DIR . 'templates/admin/org/finance/list.php';
	}

	private function render_edit(int $id): void {
		global $wpdb;
		$pkg_table  = Schema::table('payment_packages');
		$line_table = Schema::table('payment_package_lines');
		$package    = $id > 0 ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$pkg_table} WHERE id = %d", $id)) : null;
		$lines      = $id > 0
			? $wpdb->get_results($wpdb->prepare("SELECT * FROM {$line_table} WHERE package_id = %d ORDER BY sort_order ASC, id ASC", $id))
			: [];

		// Accommodations in package
		$pkg_accom = $id > 0
			? $wpdb->get_results($wpdb->prepare("SELECT * FROM " . Schema::table('payment_pkg_accom') . " WHERE package_id = %d ORDER BY sort_order ASC", $id))
			: [];

		// Diet slots in package — indexed by diet_id → slot → row
		$pkg_diet_slots_raw = $id > 0
			? $wpdb->get_results($wpdb->prepare("SELECT * FROM " . Schema::table('payment_pkg_diet_slots') . " WHERE package_id = %d", $id))
			: [];
		$pkg_diet_slots = [];
		foreach ( $pkg_diet_slots_raw as $row ) {
			$pkg_diet_slots[(int)$row->diet_id][$row->meal_slot] = $row;
		}

		$all_accom_types = \BaseMgmt\Admin\Pages\OrgAccommodationsPage::get_all();
		$all_diets       = \BaseMgmt\Admin\Pages\OrgDietsPage::get_all();
		$meal_slots      = \BaseMgmt\Admin\Pages\OrgDietsPage::meal_slots();

		// Pre-load default diet slot costs for JS pre-fill
		$diet_default_costs = [];
		foreach ( $all_diets as $d ) {
			$costs = \BaseMgmt\Admin\Pages\OrgDietsPage::get_costs((int)$d->id);
			$diet_default_costs[(int)$d->id] = $costs;
		}

		$line_types = self::line_types();
		$units      = self::units();
		include BASEMGMT_DIR . 'templates/admin/org/finance/edit.php';
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_payment_package');

		global $wpdb;
		$pkg_table  = Schema::table('payment_packages');
		$line_table = Schema::table('payment_package_lines');
		$id         = (int) ($_POST['package_id'] ?? 0);

		$pkg_data = [
			'name'        => sanitize_text_field($_POST['name'] ?? ''),
			'description' => sanitize_textarea_field($_POST['description'] ?? ''),
			'currency'    => strtoupper(sanitize_text_field($_POST['currency'] ?? 'PLN')),
			'created_by'  => get_current_user_id(),
		];

		if ( empty($pkg_data['name']) ) {
			AdminMenu::set_notice(__('Podaj nazwę pakietu.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-org-finance&action=' . ($id > 0 ? "edit&id={$id}" : 'new')));
			exit;
		}

		if ( $id > 0 ) {
			unset($pkg_data['created_by']);
			$wpdb->update($pkg_table, $pkg_data, ['id' => $id]);
		} else {
			$wpdb->insert($pkg_table, $pkg_data);
			$id = (int) $wpdb->insert_id;
		}

		// Save lines.
		$wpdb->delete($line_table, ['package_id' => $id]);
		$labels       = (array) ($_POST['line_label'] ?? []);
		$types        = (array) ($_POST['line_type'] ?? []);
		$custom_types = (array) ($_POST['line_custom_type'] ?? []);
		$prices       = (array) ($_POST['line_price'] ?? []);
		$vat_rates    = (array) ($_POST['line_vat'] ?? []);
		$units        = (array) ($_POST['line_unit'] ?? []);
		$days_before  = (array) ($_POST['line_days_before'] ?? []);

		foreach ( $labels as $i => $label ) {
			$label = sanitize_text_field($label);
			if ( empty($label) ) {
				continue;
			}
			$type = sanitize_key($types[$i] ?? self::LINE_CUSTOM);
			// For custom type, use the typed label as line_type stored as 'custom', label stays as-is.
			$wpdb->insert($line_table, [
				'package_id'  => $id,
				'line_type'   => $type,
				'label'       => $label,
				'unit_price'  => (float) str_replace(',', '.', $prices[$i] ?? '0'),
				'vat_rate'    => (float) str_replace(',', '.', $vat_rates[$i] ?? '0'),
				'unit'        => sanitize_key($units[$i] ?? 'person_night'),
				'days_before' => (int) ($days_before[$i] ?? 0),
				'is_deposit'  => 0,
				'sort_order'  => $i,
			]);
		}

		AdminMenu::set_notice(__('Pakiet zapisany.', 'basemgmt'));

		// Save accommodation rates.
		$wpdb->delete(Schema::table('payment_pkg_accom'), ['package_id' => $id]);
		$accom_type_ids  = (array) ($_POST['accom_type_id']  ?? []);
		$accom_prices    = (array) ($_POST['accom_price']    ?? []);
		$accom_vats      = (array) ($_POST['accom_vat']      ?? []);
		$accom_days      = (array) ($_POST['accom_days_before'] ?? []);
		foreach ( $accom_type_ids as $ai => $type_id ) {
			$type_id = (int) $type_id;
			if ( ! $type_id ) continue;
			$wpdb->insert(Schema::table('payment_pkg_accom'), [
				'package_id'            => $id,
				'accommodation_type_id' => $type_id,
				'price_netto'           => (float) str_replace(',', '.', $accom_prices[$ai] ?? '0'),
				'vat_rate'              => (float) str_replace(',', '.', $accom_vats[$ai]   ?? '0'),
				'days_before'           => (int) ($accom_days[$ai] ?? 30),
				'sort_order'            => $ai,
			]);
		}

		// Save diet slot rates.
		$wpdb->delete(Schema::table('payment_pkg_diet_slots'), ['package_id' => $id]);
		$diet_ids       = array_unique(array_filter(array_map('intval', (array) ($_POST['diet_id_entry'] ?? []))));
		$diet_slot_en   = (array) ($_POST['diet_slot_enabled']     ?? []);
		$diet_slot_pr   = (array) ($_POST['diet_slot_price']       ?? []);
		$diet_slot_vat  = (array) ($_POST['diet_slot_vat']         ?? []);
		$diet_days      = (array) ($_POST['diet_days_before']      ?? []);
		foreach ( $diet_ids as $diet_id ) {
			$slots_for_diet = $diet_slot_pr[$diet_id] ?? [];
			foreach ( $slots_for_diet as $slot_key => $price ) {
				$wpdb->insert(Schema::table('payment_pkg_diet_slots'), [
					'package_id' => $id,
					'diet_id'    => $diet_id,
					'meal_slot'  => sanitize_key($slot_key),
					'cost_netto' => (float) str_replace(',', '.', $price),
					'vat_rate'   => (float) str_replace(',', '.', $diet_slot_vat[$diet_id][$slot_key] ?? '0'),
					'enabled'    => isset($diet_slot_en[$diet_id][$slot_key]) ? 1 : 0,
					'days_before'=> (int) ($diet_days[$diet_id] ?? 30),
				]);
			}
		}

		wp_safe_redirect(admin_url("admin.php?page=basemgmt-org-finance&action=edit&id={$id}"));
		exit;
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_payment_package_{$id}");

		global $wpdb;
		$wpdb->delete(Schema::table('payment_package_lines'),   ['package_id' => $id]);
		$wpdb->delete(Schema::table('payment_pkg_accom'),       ['package_id' => $id]);
		$wpdb->delete(Schema::table('payment_pkg_diet_slots'),  ['package_id' => $id]);
		$wpdb->delete(Schema::table('payment_packages'), ['id' => $id]);
		AdminMenu::set_notice(__('Pakiet usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-org-finance'));
		exit;
	}

	// ── Static helpers ────────────────────────────────────────────────────────

	public static function get_packages(): array {
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM " . Schema::table('payment_packages') . " ORDER BY is_default DESC, name ASC") ?: [];
	}

	public static function get_package_lines(int $package_id): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('payment_package_lines') . " WHERE package_id = %d ORDER BY sort_order ASC",
			$package_id
		)) ?: [];
	}
}
