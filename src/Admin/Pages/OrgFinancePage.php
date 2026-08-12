<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Database\Schema;

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
			self::LINE_OTHER         => __('Inne koszty', 'basemgmt'),
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
		wp_safe_redirect(admin_url("admin.php?page=basemgmt-org-finance&action=edit&id={$id}"));
		exit;
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_payment_package_{$id}");

		global $wpdb;
		$wpdb->delete(Schema::table('payment_package_lines'), ['package_id' => $id]);
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
