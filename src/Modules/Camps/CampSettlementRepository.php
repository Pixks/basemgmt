<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Camps;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Repository for camp billing settlements.
 * Manages the bm_camp_settlements + bm_camp_settlement_lines tables.
 */
final class CampSettlementRepository {

	// ── Status constants ──────────────────────────────────────────────────────

	public const STATUS_DRAFT    = 'draft';
	public const STATUS_READY    = 'ready';
	public const STATUS_ISSUED   = 'issued';
	public const STATUS_PAID     = 'paid';
	public const STATUS_CANCELED = 'canceled';

	// ── Line type constants ───────────────────────────────────────────────────

	public const LINE_ACCOMMODATION = 'accommodation';
	public const LINE_FOOD          = 'food';
	public const LINE_TAX           = 'tax';
	public const LINE_DEPOSIT       = 'deposit';
	public const LINE_DAMAGE        = 'damage';
	public const LINE_EQUIPMENT     = 'equipment';
	public const LINE_CORRECTION    = 'correction';
	public const LINE_REFUND        = 'refund';
	public const LINE_CUSTOM        = 'custom';

	public static function statuses(): array {
		return [
			self::STATUS_DRAFT    => __('Szkic', 'basemgmt'),
			self::STATUS_READY    => __('Gotowe do PDF', 'basemgmt'),
			self::STATUS_ISSUED   => __('Wystawione', 'basemgmt'),
			self::STATUS_PAID     => __('Opłacone', 'basemgmt'),
			self::STATUS_CANCELED => __('Anulowane', 'basemgmt'),
		];
	}

	public static function line_types(): array {
		return [
			self::LINE_ACCOMMODATION => __('Nocleg', 'basemgmt'),
			self::LINE_FOOD          => __('Wyżywienie', 'basemgmt'),
			self::LINE_TAX           => __('Podatek / opłata', 'basemgmt'),
			self::LINE_DEPOSIT       => __('Zaliczka', 'basemgmt'),
			self::LINE_DAMAGE        => __('Szkoda', 'basemgmt'),
			self::LINE_EQUIPMENT     => __('Sprzęt', 'basemgmt'),
			self::LINE_CORRECTION    => __('Korekta', 'basemgmt'),
			self::LINE_REFUND        => __('Zwrot', 'basemgmt'),
			self::LINE_CUSTOM        => __('Inne', 'basemgmt'),
		];
	}

	// ── Read ──────────────────────────────────────────────────────────────────

	public static function get_by_camp(int $camp_id): ?object {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_settlements') . " WHERE camp_id = %d ORDER BY id DESC LIMIT 1",
			$camp_id
		)) ?: null;
	}

	public static function get_by_id(int $id): ?object {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_settlements') . " WHERE id = %d",
			$id
		)) ?: null;
	}

	public static function get_lines(int $settlement_id): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_settlement_lines') . " WHERE settlement_id = %d ORDER BY sort_order ASC, id ASC",
			$settlement_id
		)) ?: [];
	}

	// ── Write ─────────────────────────────────────────────────────────────────

	/**
	 * Returns the existing draft settlement ID or creates a new one pre-filled
	 * from the camp's current data (payment schedules, organizer, declaration).
	 */
	public static function get_or_create_draft(int $camp_id): int {
		$existing = self::get_by_camp($camp_id);
		if ( $existing ) {
			return (int) $existing->id;
		}
		return self::create_draft_from_camp($camp_id);
	}

	public static function create_draft_from_camp(int $camp_id): int {
		global $wpdb;

		$camp      = CampRepository::get($camp_id);
		$organizer = CampCaseRepository::get_organizer($camp_id);

		$org_snapshot = $organizer ? json_encode([
			'organization_name'        => $organizer->organization_name,
			'contact_person'           => $organizer->contact_person,
			'contact_email'            => $organizer->contact_email,
			'contact_phone'            => $organizer->contact_phone,
			'billing_name'             => $organizer->billing_name,
			'billing_tax_id'           => $organizer->billing_tax_id,
			'billing_regon'            => $organizer->billing_regon ?? '',
			'billing_krs'              => $organizer->billing_krs ?? '',
			'billing_street'           => $organizer->billing_street ?? '',
			'billing_city'             => $organizer->billing_city ?? '',
			'billing_zip'              => $organizer->billing_zip ?? '',
			'bank_name'                => $organizer->bank_name ?? '',
			'bank_account'             => $organizer->bank_account ?? '',
			'billing_address'          => $organizer->billing_address,
			'settlement_contact_name'  => $organizer->settlement_contact_name,
			'settlement_contact_email' => $organizer->settlement_contact_email,
			'settlement_contact_phone' => $organizer->settlement_contact_phone,
			'notes'                    => $organizer->notes,
		], JSON_UNESCAPED_UNICODE) : '{}';

		$stay_summary = self::build_stay_summary($camp_id);

		$wpdb->insert(Schema::table('camp_settlements'), [
			'camp_id'               => $camp_id,
			'status'                => self::STATUS_DRAFT,
			'period_start'          => $camp ? $camp->start_date : null,
			'period_end'            => $camp ? $camp->end_date   : null,
			'issue_date'            => current_time('Y-m-d'),
			'global_discount'       => 0.00,
			'global_discount_type'  => 'fixed',
			'total_gross'           => 0.00,
			'total_discounts'       => 0.00,
			'total_damages'         => 0.00,
			'amount_paid'           => 0.00,
			'outstanding_amount'    => 0.00,
			'organizer_snapshot'    => $org_snapshot,
			'stay_summary_snapshot' => json_encode($stay_summary, JSON_UNESCAPED_UNICODE),
			'created_by'            => get_current_user_id(),
		]);

		$settlement_id = (int) $wpdb->insert_id;
		if ( ! $settlement_id ) {
			return 0;
		}

		// Pre-fill lines from payment schedule.
		self::import_schedule_lines($settlement_id, $camp_id);
		// Pre-fill damage lines.
		self::import_damage_lines($settlement_id, $camp_id);

		// Recalculate totals.
		self::recalculate_totals($settlement_id);

		return $settlement_id;
	}

	/**
	 * Save (update) a settlement header.
	 */
	public static function save(int $settlement_id, array $data): bool {
		global $wpdb;

		$allowed = [
			'status', 'document_number', 'issue_date', 'due_date', 'payment_terms',
			'global_discount', 'global_discount_type', 'notes',
			'organizer_snapshot', 'stay_summary_snapshot',
		];

		$clean = [];
		foreach ( $allowed as $key ) {
			if ( array_key_exists($key, $data) ) {
				$clean[$key] = $data[$key];
			}
		}

		if ( empty($clean) ) {
			return false;
		}

		$result = $wpdb->update(Schema::table('camp_settlements'), $clean, ['id' => $settlement_id]);
		return $result !== false;
	}

	/**
	 * Replace all lines for a settlement.
	 *
	 * @param array<int,array<string,mixed>> $lines
	 */
	public static function save_lines(int $settlement_id, array $lines): void {
		global $wpdb;

		$settlement = self::get_by_id($settlement_id);
		if ( ! $settlement ) {
			return;
		}

		$line_table = Schema::table('camp_settlement_lines');
		$wpdb->delete($line_table, ['settlement_id' => $settlement_id]);

		foreach ( $lines as $i => $line ) {
			$label       = sanitize_text_field((string) ($line['label'] ?? ''));
			$description = sanitize_textarea_field((string) ($line['description'] ?? ''));
			if ( $label === '' && $description === '' ) {
				continue;
			}
			$amount       = (float) str_replace(',', '.', (string) ($line['amount'] ?? '0'));
			$discount     = (float) str_replace(',', '.', (string) ($line['discount'] ?? '0'));
			$disc_type    = sanitize_key((string) ($line['discount_type'] ?? 'fixed'));
			$include      = isset($line['include_in_settlement']) ? 1 : 0;
			$total        = self::apply_discount($amount, $discount, $disc_type);

			$wpdb->insert($line_table, [
				'settlement_id'         => $settlement_id,
				'camp_id'               => (int) $settlement->camp_id,
				'line_type'             => sanitize_key((string) ($line['line_type'] ?? self::LINE_CUSTOM)),
				'description'           => $label !== '' ? $label : $description,
				'quantity'              => 1.00,
				'unit_price'            => $amount,
				'discount'              => $discount,
				'discount_type'         => $disc_type,
				'total_amount'          => $total,
				'include_in_settlement' => $include,
				'sort_order'            => $i,
				'source_schedule_id'    => (int) ($line['source_schedule_id'] ?? 0) ?: null,
				'source_damage_id'      => (int) ($line['source_damage_id'] ?? 0) ?: null,
				'source_equipment_id'   => (int) ($line['source_equipment_id'] ?? 0) ?: null,
				'payment_status'        => sanitize_key((string) ($line['payment_status'] ?? 'expected')),
				'manual_adjustment'     => ! empty($line['manual_adjustment']) ? 1 : 0,
			]);
		}

		self::recalculate_totals($settlement_id);
	}

	// ── Stay summary builder ──────────────────────────────────────────────────

	/**
	 * Builds an aggregated stay summary from camp_declaration_days.
	 *
	 * @return array{
	 *   date_from: string, date_to: string,
	 *   person_days: int, arrival_time: string, departure_time: string,
	 *   diets: array<string,int>, accommodations: array<string,int>, days: list<array>
	 * }
	 */
	public static function build_stay_summary(int $camp_id): array {
		global $wpdb;

		$days = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_declaration_days') . " WHERE camp_id = %d ORDER BY declaration_date ASC",
			$camp_id
		)) ?: [];

		if ( empty($days) ) {
			return [
				'date_from'      => '',
				'date_to'        => '',
				'person_days'    => 0,
				'arrival_time'   => '',
				'departure_time' => '',
				'diets'          => [],
				'accommodations' => [],
				'days'           => [],
			];
		}

		$day_ids          = array_map(static fn($d) => (int) $d->id, $days);
		$ids_placeholder  = implode(',', $day_ids);

		$diet_rows = $wpdb->get_results(
			"SELECT ddl.*, md.name as diet_name FROM " . Schema::table('camp_declaration_diet_lines') . " ddl
			 LEFT JOIN " . Schema::table('meal_diets') . " md ON md.id = ddl.diet_id
			 WHERE ddl.day_id IN ({$ids_placeholder})" // phpcs:ignore
		) ?: [];

		$accom_rows = $wpdb->get_results(
			"SELECT dal.*, at.name as accom_name FROM " . Schema::table('camp_declaration_accommodation_lines') . " dal
			 LEFT JOIN " . Schema::table('accommodation_types') . " at ON at.id = dal.accommodation_type_id
			 WHERE dal.day_id IN ({$ids_placeholder})" // phpcs:ignore
		) ?: [];

		// Index by day_id.
		$diets_by_day  = [];
		$accom_by_day  = [];
		$diet_totals   = [];
		$accom_totals  = [];

		foreach ( $diet_rows as $dr ) {
			$diets_by_day[(int) $dr->day_id][(string) $dr->diet_name] = (int) $dr->count;
			$diet_totals[(string) $dr->diet_name] = ($diet_totals[(string) $dr->diet_name] ?? 0) + (int) $dr->count;
		}
		foreach ( $accom_rows as $ar ) {
			$accom_by_day[(int) $ar->day_id][(string) $ar->accom_name] = (int) $ar->count;
			$accom_totals[(string) $ar->accom_name] = ($accom_totals[(string) $ar->accom_name] ?? 0) + (int) $ar->count;
		}

		$total_persons = 0;
		$day_items     = [];
		foreach ( $days as $d ) {
			$total_persons += (int) $d->declared_persons;
			$day_items[]    = [
				'date'           => $d->declaration_date,
				'persons'        => (int) $d->declared_persons,
				'arrival_time'   => $d->arrival_time,
				'departure_time' => $d->departure_time,
				'diets'          => $diets_by_day[(int) $d->id] ?? [],
				'accommodations' => $accom_by_day[(int) $d->id] ?? [],
			];
		}

		$first = reset($days);
		$last  = end($days);

		return [
			'date_from'      => $first ? $first->declaration_date : '',
			'date_to'        => $last  ? $last->declaration_date  : '',
			'person_days'    => $total_persons,
			'arrival_time'   => $first ? $first->arrival_time   : '',
			'departure_time' => $last  ? $last->departure_time  : '',
			'diets'          => $diet_totals,
			'accommodations' => $accom_totals,
			'days'           => $day_items,
		];
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	public static function recalculate_totals(int $settlement_id): void {
		global $wpdb;

		$settlement = self::get_by_id($settlement_id);
		if ( ! $settlement ) {
			return;
		}

		$lines = self::get_lines($settlement_id);

		$total_gross    = 0.0;
		$total_discounts = 0.0;
		$total_damages  = 0.0;
		$amount_paid    = 0.0;

		foreach ( $lines as $line ) {
			if ( ! (int) $line->include_in_settlement ) {
				continue;
			}
			$unit_price = (float) $line->unit_price;
			$discount   = (float) $line->discount;
			$disc_type  = (string) $line->discount_type;
			$disc_value = self::calc_discount_value($unit_price, $discount, $disc_type);

			$total_gross += (float) $line->total_amount;
			$total_discounts += $disc_value;

			if ( $line->line_type === self::LINE_DAMAGE ) {
				$total_damages += (float) $line->total_amount;
			}
			if ( $line->payment_status === 'paid' ) {
				$amount_paid += (float) $line->total_amount;
			}
		}

		// Apply global discount.
		$global_discount      = (float) ($settlement->global_discount ?? 0);
		$global_discount_type = (string) ($settlement->global_discount_type ?? 'fixed');
		$global_disc_value    = self::calc_discount_value($total_gross, $global_discount, $global_discount_type);
		$total_gross          = max(0.0, $total_gross - $global_disc_value);
		$total_discounts     += $global_disc_value;
		$outstanding          = max(0.0, $total_gross - $amount_paid);

		$wpdb->update(Schema::table('camp_settlements'), [
			'total_gross'           => round($total_gross, 2),
			'total_discounts'       => round($total_discounts, 2),
			'total_damages'         => round($total_damages, 2),
			'amount_paid'           => round($amount_paid, 2),
			'outstanding_amount'    => round($outstanding, 2),
		], ['id' => $settlement_id]);
	}

	public static function apply_discount(float $amount, float $discount, string $disc_type): float {
		return max(0.0, $amount - self::calc_discount_value($amount, $discount, $disc_type));
	}

	public static function calc_discount_value(float $amount, float $discount, string $disc_type): float {
		if ( $discount <= 0 ) {
			return 0.0;
		}
		if ( $disc_type === 'percent' ) {
			return round($amount * $discount / 100, 2);
		}
		return min($amount, $discount);
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	private static function import_schedule_lines(int $settlement_id, int $camp_id): void {
		global $wpdb;

		$schedules = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_payment_schedules') . " WHERE camp_id = %d ORDER BY due_date ASC, id ASC",
			$camp_id
		)) ?: [];

		$line_table = Schema::table('camp_settlement_lines');

		foreach ( $schedules as $i => $sched ) {
			$amount    = (float) $sched->amount;
			$discount  = (float) $sched->discount;
			$disc_type = (string) $sched->discount_type;
			$total     = self::apply_discount($amount, $discount, $disc_type);

			$wpdb->insert($line_table, [
				'settlement_id'         => $settlement_id,
				'camp_id'               => $camp_id,
				'line_type'             => sanitize_key((string) $sched->payment_type),
				'description'           => sanitize_text_field((string) $sched->label),
				'quantity'              => 1.00,
				'unit_price'            => $amount,
				'discount'              => $discount,
				'discount_type'         => $disc_type,
				'total_amount'          => $total,
				'include_in_settlement' => 1,
				'sort_order'            => $i,
				'source_schedule_id'    => (int) $sched->id,
				'payment_status'        => sanitize_key((string) $sched->status),
				'manual_adjustment'     => 0,
			]);
		}
	}

	private static function import_damage_lines(int $settlement_id, int $camp_id): void {
		global $wpdb;

		$damages = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_damages') . " WHERE camp_id = %d ORDER BY created_at ASC",
			$camp_id
		)) ?: [];

		if ( empty($damages) ) {
			return;
		}

		$line_table = Schema::table('camp_settlement_lines');
		$count      = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$line_table} WHERE settlement_id = %d",
			$settlement_id
		));

		foreach ( $damages as $i => $dmg ) {
			$amount = (float) $dmg->cost;
			$wpdb->insert($line_table, [
				'settlement_id'         => $settlement_id,
				'camp_id'               => $camp_id,
				'line_type'             => self::LINE_DAMAGE,
				'description'           => sanitize_text_field((string) $dmg->name),
				'quantity'              => 1.00,
				'unit_price'            => $amount,
				'discount'              => 0.00,
				'discount_type'         => 'fixed',
				'total_amount'          => $amount,
				'include_in_settlement' => 1,
				'sort_order'            => $count + $i,
				'source_damage_id'      => (int) $dmg->id,
				'payment_status'        => 'expected',
				'manual_adjustment'     => 0,
			]);
		}
	}
}
