<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Core\PdfSettings;
use BaseMgmt\Database\Schema;
use BaseMgmt\Modules\Camps\CampCaseRepository;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Camps\CampSettlementRepository;

defined('ABSPATH') || exit;

/**
 * Page controller for the camp settlement (Rozliczenie) feature.
 *
 * Entry points:
 *   – edit form:  admin.php?page=basemgmt-camps&action=settlement&id={camp_id}
 *   – PDF render: admin.php?page=basemgmt-camps&action=settlement_pdf&id={camp_id}
 *     (handled via maybe_early_exit in CampsPage so no WP chrome is rendered)
 */
final class CampSettlementPage {

	// ── Render ────────────────────────────────────────────────────────────────

	public function render_edit(int $camp_id): void {
		Capabilities::require_admin();

		if ( $camp_id <= 0 ) {
			AdminMenu::set_notice(__('Nieprawidłowy identyfikator obozu.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-camps'));
			exit;
		}

		$camp = CampRepository::get($camp_id);
		if ( ! $camp ) {
			AdminMenu::set_notice(__('Obóz nie istnieje.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-camps'));
			exit;
		}

		global $wpdb;

		// Get or create draft settlement.
		$settlement_id = CampSettlementRepository::get_or_create_draft($camp_id);
		$settlement    = CampSettlementRepository::get_by_id($settlement_id);
		$lines         = CampSettlementRepository::get_lines($settlement_id);

		// Organizer snapshot (editable).
		$organizer_snapshot = [];
		if ( $settlement && ! empty($settlement->organizer_snapshot) ) {
			$organizer_snapshot = json_decode($settlement->organizer_snapshot, true) ?: [];
		}
		if ( empty($organizer_snapshot) ) {
			$org = CampCaseRepository::get_organizer($camp_id);
			if ( $org ) {
				$organizer_snapshot = [
					'organization_name'        => $org->organization_name,
					'contact_person'           => $org->contact_person,
					'contact_email'            => $org->contact_email,
					'contact_phone'            => $org->contact_phone,
					'billing_name'             => $org->billing_name,
					'billing_tax_id'           => $org->billing_tax_id,
					'billing_regon'            => $org->billing_regon ?? '',
					'billing_krs'              => $org->billing_krs ?? '',
					'billing_street'           => $org->billing_street ?? '',
					'billing_city'             => $org->billing_city ?? '',
					'billing_zip'              => $org->billing_zip ?? '',
					'bank_name'                => $org->bank_name ?? '',
					'bank_account'             => $org->bank_account ?? '',
					'billing_address'          => $org->billing_address,
					'settlement_contact_name'  => $org->settlement_contact_name,
					'settlement_contact_email' => $org->settlement_contact_email,
					'settlement_contact_phone' => $org->settlement_contact_phone,
					'notes'                    => $org->notes,
				];
			}
		}

		// Stay summary snapshot.
		$stay_summary = [];
		if ( $settlement && ! empty($settlement->stay_summary_snapshot) ) {
			$stay_summary = json_decode($settlement->stay_summary_snapshot, true) ?: [];
		}
		if ( empty($stay_summary) ) {
			$stay_summary = CampSettlementRepository::build_stay_summary($camp_id);
		}

		// Equipment.
		$camp_equipment = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_equipment') . " WHERE camp_id = %d ORDER BY created_at ASC",
			$camp_id
		)) ?: [];

		// Camp case for header info.
		$case = CampCaseRepository::get_case($camp_id);

		$statuses   = CampSettlementRepository::statuses();
		$line_types = CampSettlementRepository::line_types();

		include BASEMGMT_DIR . 'templates/admin/camps/settlement-edit.php';
	}

	/**
	 * Render a print-ready PDF page for the settlement.
	 * Must be called before any WP output (via maybe_early_exit).
	 */
	public function render_pdf(int $camp_id): void {
		Capabilities::require_admin();

		if ( $camp_id <= 0 ) {
			wp_die(esc_html__('Nieprawidłowy obóz.', 'basemgmt'));
		}

		$camp       = CampRepository::get($camp_id);
		$settlement = CampSettlementRepository::get_by_camp($camp_id);

		if ( ! $camp || ! $settlement ) {
			wp_die(esc_html__('Brak rozliczenia dla tego obozu.', 'basemgmt'));
		}

		$lines = CampSettlementRepository::get_lines((int) $settlement->id);

		$organizer_snapshot = json_decode($settlement->organizer_snapshot ?? '{}', true) ?: [];
		$stay_summary       = json_decode($settlement->stay_summary_snapshot ?? '{}', true) ?: [];

		$settings       = PdfSettings::get_settings();
		$generated_at   = current_time('d.m.Y H:i');
		$title          = sprintf(
			/* translators: %s: camp name */
			__('Rozliczenie – %s', 'basemgmt'),
			$camp->name
		);
		$formatted_date = date_i18n('d.m.Y', strtotime($settlement->issue_date ?? current_time('Y-m-d')));

		$line_types = CampSettlementRepository::line_types();

		$content = $this->capture(BASEMGMT_DIR . 'templates/admin/pdf/settlement.php', compact(
			'camp', 'settlement', 'lines', 'organizer_snapshot',
			'stay_summary', 'generated_at', 'line_types'
		));

		include BASEMGMT_DIR . 'templates/admin/pdf/base.php';
		exit;
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_settlement');

		$camp_id       = (int) ($_POST['camp_id'] ?? 0);
		$settlement_id = (int) ($_POST['settlement_id'] ?? 0);
		$status_intent = sanitize_key($_POST['status_intent'] ?? 'draft');

		if ( $camp_id <= 0 || $settlement_id <= 0 ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane rozliczenia.', 'basemgmt'), 'error');
			$this->redirect_back($camp_id);
			return;
		}

		// Verify settlement belongs to this camp.
		$settlement = CampSettlementRepository::get_by_id($settlement_id);
		if ( ! $settlement || (int) $settlement->camp_id !== $camp_id ) {
			AdminMenu::set_notice(__('Nie znaleziono rozliczenia.', 'basemgmt'), 'error');
			$this->redirect_back($camp_id);
			return;
		}

		// Build organizer snapshot from form.
		$org_fields = [
			'organization_name', 'contact_person', 'contact_email', 'contact_phone',
			'billing_name', 'billing_tax_id', 'billing_regon', 'billing_krs',
			'billing_street', 'billing_city', 'billing_zip',
			'bank_name', 'bank_account', 'billing_address',
			'settlement_contact_name', 'settlement_contact_email', 'settlement_contact_phone',
			'notes',
		];
		$org_snapshot = [];
		foreach ( $org_fields as $field ) {
			$raw = wp_unslash($_POST['org_' . $field] ?? '');
			$org_snapshot[$field] = sanitize_text_field((string) $raw);
		}
		$org_snapshot['billing_address'] = sanitize_textarea_field(wp_unslash((string) ($_POST['org_billing_address'] ?? '')));
		$org_snapshot['notes']           = sanitize_textarea_field(wp_unslash((string) ($_POST['org_notes'] ?? '')));

		// Determine new status.
		$new_status = match($status_intent) {
			'ready'  => CampSettlementRepository::STATUS_READY,
			'issued' => CampSettlementRepository::STATUS_ISSUED,
			'paid'   => CampSettlementRepository::STATUS_PAID,
			default  => CampSettlementRepository::STATUS_DRAFT,
		};

		// Save settlement header.
		CampSettlementRepository::save($settlement_id, [
			'status'               => $new_status,
			'document_number'      => sanitize_text_field(wp_unslash((string) ($_POST['document_number'] ?? ''))),
			'issue_date'           => sanitize_text_field(wp_unslash((string) ($_POST['issue_date'] ?? current_time('Y-m-d')))),
			'due_date'             => sanitize_text_field(wp_unslash((string) ($_POST['due_date'] ?? ''))),
			'payment_terms'        => sanitize_textarea_field(wp_unslash((string) ($_POST['payment_terms'] ?? ''))),
			'global_discount'      => (float) str_replace(',', '.', (string) ($_POST['global_discount'] ?? '0')),
			'global_discount_type' => sanitize_key((string) ($_POST['global_discount_type'] ?? 'fixed')),
			'notes'                => sanitize_textarea_field(wp_unslash((string) ($_POST['settlement_notes'] ?? ''))),
			'organizer_snapshot'   => wp_json_encode($org_snapshot, JSON_UNESCAPED_UNICODE),
		]);

		// Save lines.
		$raw_lines         = (array) ($_POST['lines'] ?? []);
		$labels            = (array) ($raw_lines['label']                ?? []);
		$types             = (array) ($raw_lines['line_type']            ?? []);
		$amounts           = (array) ($raw_lines['amount']               ?? []);
		$discounts         = (array) ($raw_lines['discount']             ?? []);
		$disc_types        = (array) ($raw_lines['discount_type']        ?? []);
		$payment_statuses  = (array) ($raw_lines['payment_status']       ?? []);
		$includes          = (array) ($raw_lines['include_in_settlement']?? []);
		$src_schedule_ids  = (array) ($raw_lines['source_schedule_id']   ?? []);
		$src_damage_ids    = (array) ($raw_lines['source_damage_id']     ?? []);
		$src_equip_ids     = (array) ($raw_lines['source_equipment_id']  ?? []);

		$parsed_lines = [];
		foreach ( $labels as $i => $label ) {
			$label = sanitize_text_field(wp_unslash((string) $label));
			if ( $label === '' ) {
				continue;
			}
			$parsed_lines[] = [
				'label'                 => $label,
				'line_type'             => sanitize_key((string) ($types[$i] ?? CampSettlementRepository::LINE_CUSTOM)),
				'amount'                => (string) ($amounts[$i] ?? '0'),
				'discount'              => (string) ($discounts[$i] ?? '0'),
				'discount_type'         => sanitize_key((string) ($disc_types[$i] ?? 'fixed')),
				'payment_status'        => sanitize_key((string) ($payment_statuses[$i] ?? 'expected')),
				'include_in_settlement' => isset($includes[$i]) ? 1 : 0,
				'source_schedule_id'    => (int) ($src_schedule_ids[$i] ?? 0),
				'source_damage_id'      => (int) ($src_damage_ids[$i] ?? 0),
				'source_equipment_id'   => (int) ($src_equip_ids[$i] ?? 0),
			];
		}

		CampSettlementRepository::save_lines($settlement_id, $parsed_lines);

		AdminMenu::set_notice(
			$status_intent === 'ready'
				? __('Rozliczenie zapisane i oznaczone jako gotowe do PDF.', 'basemgmt')
				: __('Rozliczenie zapisane jako szkic.', 'basemgmt')
		);

		$this->redirect_back($camp_id);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function redirect_back(int $camp_id): void {
		wp_safe_redirect(admin_url("admin.php?page=basemgmt-camps&action=settlement&id={$camp_id}"));
		exit;
	}

	private function capture(string $file, array $vars): string {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract($vars, EXTR_SKIP);
		ob_start();
		include $file;
		return (string) ob_get_clean();
	}
}
