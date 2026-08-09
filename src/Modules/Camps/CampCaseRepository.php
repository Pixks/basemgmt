<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Camps;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Handles the extended "camp case file" workflow and related records.
 */
final class CampCaseRepository {

	public const STAGE_INQUIRY         = 'inquiry';
	public const STAGE_OFFER           = 'offer';
	public const STAGE_NEGOTIATION     = 'negotiation';
	public const STAGE_TENTATIVE       = 'tentative_booking';
	public const STAGE_CONTRACT_DRAFT  = 'contract_draft';
	public const STAGE_CONTRACT_SIGNED = 'contract_signed';
	public const STAGE_AWAITING_PAYMENT = 'awaiting_payment';
	public const STAGE_READY           = 'ready_for_arrival';
	public const STAGE_ON_SITE         = 'on_site';
	public const STAGE_SETTLEMENT      = 'settlement';
	public const STAGE_CLOSED          = 'closed';
	public const STAGE_CANCELLED       = 'cancelled';

	public const PROCESS_STAGES = [
		self::STAGE_INQUIRY          => 'Nowe zapytanie',
		self::STAGE_OFFER            => 'Oferta przygotowana',
		self::STAGE_NEGOTIATION      => 'Negocjacje',
		self::STAGE_TENTATIVE        => 'Rezerwacja wstępna',
		self::STAGE_CONTRACT_DRAFT   => 'Umowa do podpisu',
		self::STAGE_CONTRACT_SIGNED  => 'Umowa podpisana',
		self::STAGE_AWAITING_PAYMENT => 'Oczekiwanie na płatność',
		self::STAGE_READY            => 'Gotowe do przyjazdu',
		self::STAGE_ON_SITE          => 'Pobyt',
		self::STAGE_SETTLEMENT       => 'Rozliczenie',
		self::STAGE_CLOSED           => 'Zamknięte',
		self::STAGE_CANCELLED        => 'Anulowane',
	];

	public const RISK_LOW      = 'low';
	public const RISK_MEDIUM   = 'medium';
	public const RISK_HIGH     = 'high';
	public const RISK_CRITICAL = 'critical';

	public const RISK_LEVELS = [
		self::RISK_LOW      => 'Niskie',
		self::RISK_MEDIUM   => 'Średnie',
		self::RISK_HIGH     => 'Wysokie',
		self::RISK_CRITICAL => 'Krytyczne',
	];

	public const CHECKLIST_PARTY_ORGANIZER = 'organizer';
	public const CHECKLIST_PARTY_CENTER    = 'center';
	public const CHECKLIST_PARTY_SHARED    = 'shared';

	public const CHECKLIST_PARTIES = [
		self::CHECKLIST_PARTY_ORGANIZER => 'Organizator',
		self::CHECKLIST_PARTY_CENTER    => 'Ośrodek',
		self::CHECKLIST_PARTY_SHARED    => 'Wspólne',
	];

	public const CHECKLIST_STATUS_PENDING     = 'pending';
	public const CHECKLIST_STATUS_IN_PROGRESS = 'in_progress';
	public const CHECKLIST_STATUS_DONE        = 'done';
	public const CHECKLIST_STATUS_BLOCKED     = 'blocked';

	public const CHECKLIST_STATUSES = [
		self::CHECKLIST_STATUS_PENDING     => 'Oczekuje',
		self::CHECKLIST_STATUS_IN_PROGRESS => 'W toku',
		self::CHECKLIST_STATUS_DONE        => 'Gotowe',
		self::CHECKLIST_STATUS_BLOCKED     => 'Zablokowane',
	];

	public const DEFAULT_CHECKLIST = [
		['label' => 'Podpisana umowa', 'party' => self::CHECKLIST_PARTY_ORGANIZER],
		['label' => 'Zaksięgowana zaliczka', 'party' => self::CHECKLIST_PARTY_ORGANIZER],
		['label' => 'Lista uczestników i kadry', 'party' => self::CHECKLIST_PARTY_ORGANIZER],
		['label' => 'Dane kontaktowe osób odpowiedzialnych', 'party' => self::CHECKLIST_PARTY_ORGANIZER],
		['label' => 'Potwierdzone ubezpieczenie', 'party' => self::CHECKLIST_PARTY_ORGANIZER],
		['label' => 'Informacje o dietach i potrzebach szczególnych', 'party' => self::CHECKLIST_PARTY_SHARED],
		['label' => 'Zatwierdzony plan infrastruktury', 'party' => self::CHECKLIST_PARTY_SHARED],
		['label' => 'Przygotowane zakwaterowanie i klucze', 'party' => self::CHECKLIST_PARTY_CENTER],
		['label' => 'Zapotrzebowanie techniczne lub transportowe', 'party' => self::CHECKLIST_PARTY_SHARED],
	];

	public static function get_case(int $camp_id): ?object {
		global $wpdb;
		$table = Schema::table('camp_cases');
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE camp_id = %d LIMIT 1", $camp_id)
		) ?: null;
	}

	public static function save_case(int $camp_id, array $data): void {
		global $wpdb;

		$existing = self::get_case($camp_id);
		$stage    = self::sanitize_stage($data['process_stage'] ?? ($existing->process_stage ?? self::STAGE_INQUIRY));
		$payload  = [
			'camp_id'               => $camp_id,
			'process_stage'         => $stage,
			'needs_attention'       => empty($data['needs_attention']) ? 0 : 1,
			'risk_level'            => self::sanitize_risk_level($data['risk_level'] ?? ($existing->risk_level ?? self::RISK_LOW)),
			'owner_user_id'         => ! empty($data['owner_user_id']) ? (int) $data['owner_user_id'] : null,
			'next_action_due_date'  => self::sanitize_date_or_null($data['next_action_due_date'] ?? ''),
			'notes'                 => sanitize_textarea_field($data['notes'] ?? ''),
			'readiness_notes'       => sanitize_textarea_field($data['readiness_notes'] ?? ''),
		];

		if ( $existing ) {
			$wpdb->update(Schema::table('camp_cases'), $payload, ['camp_id' => $camp_id]);
		} else {
			$wpdb->insert(Schema::table('camp_cases'), $payload);
		}

		if ( ! $existing || $existing->process_stage !== $stage ) {
			$wpdb->insert(Schema::table('camp_case_history'), [
				'camp_id'      => $camp_id,
				'old_stage'    => $existing->process_stage ?? '',
				'new_stage'    => $stage,
				'changed_by'   => (int) get_current_user_id(),
				'change_note'  => sanitize_textarea_field($data['stage_change_note'] ?? ''),
			]);
		}
	}

	public static function get_history(int $camp_id, int $limit = 20): array {
		global $wpdb;
		$table = Schema::table('camp_case_history');
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE camp_id = %d ORDER BY id DESC LIMIT %d",
				$camp_id,
				max(1, $limit)
			)
		) ?: [];
	}

	public static function get_organizer(int $camp_id): ?object {
		global $wpdb;
		$table = Schema::table('camp_organizers');
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE camp_id = %d LIMIT 1", $camp_id)
		) ?: null;
	}

	public static function save_organizer(int $camp_id, array $data): void {
		global $wpdb;

		$payload = [
			'camp_id'                 => $camp_id,
			'organization_name'       => sanitize_text_field($data['organization_name'] ?? ''),
			'contact_person'          => sanitize_text_field($data['contact_person'] ?? ''),
			'contact_email'           => sanitize_email($data['contact_email'] ?? ''),
			'contact_phone'           => sanitize_text_field($data['contact_phone'] ?? ''),
			'billing_name'            => sanitize_text_field($data['billing_name'] ?? ''),
			'billing_tax_id'          => sanitize_text_field($data['billing_tax_id'] ?? ''),
			'billing_address'         => sanitize_textarea_field($data['billing_address'] ?? ''),
			'settlement_contact_name' => sanitize_text_field($data['settlement_contact_name'] ?? ''),
			'settlement_contact_email'=> sanitize_email($data['settlement_contact_email'] ?? ''),
			'settlement_contact_phone'=> sanitize_text_field($data['settlement_contact_phone'] ?? ''),
			'notes'                   => sanitize_textarea_field($data['notes'] ?? ''),
		];

		$existing = self::get_organizer($camp_id);
		if ( $existing ) {
			$wpdb->update(Schema::table('camp_organizers'), $payload, ['camp_id' => $camp_id]);
			return;
		}

		$wpdb->insert(Schema::table('camp_organizers'), $payload);
	}

	public static function get_prearrival(int $camp_id): ?object {
		global $wpdb;
		$table = Schema::table('camp_prearrival');
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE camp_id = %d LIMIT 1", $camp_id)
		) ?: null;
	}

	public static function save_prearrival(int $camp_id, array $data): void {
		global $wpdb;

		$payload = [
			'camp_id'                => $camp_id,
			'arrival_date'           => self::sanitize_date_or_null($data['arrival_date'] ?? ''),
			'arrival_time'           => self::sanitize_time_or_null($data['arrival_time'] ?? ''),
			'departure_date'         => self::sanitize_date_or_null($data['departure_date'] ?? ''),
			'departure_time'         => self::sanitize_time_or_null($data['departure_time'] ?? ''),
			'declared_participants'  => max(0, (int) ($data['declared_participants'] ?? 0)),
			'declared_staff'         => max(0, (int) ($data['declared_staff'] ?? 0)),
			'declared_support'       => max(0, (int) ($data['declared_support'] ?? 0)),
			'dietary_requirements'   => sanitize_textarea_field($data['dietary_requirements'] ?? ''),
			'allergens'              => sanitize_textarea_field($data['allergens'] ?? ''),
			'infrastructure_plan'    => sanitize_textarea_field($data['infrastructure_plan'] ?? ''),
			'additional_needs'       => sanitize_textarea_field($data['additional_needs'] ?? ''),
			'invoice_details'        => sanitize_textarea_field($data['invoice_details'] ?? ''),
			'authorized_contacts'    => sanitize_textarea_field($data['authorized_contacts'] ?? ''),
		];

		$existing = self::get_prearrival($camp_id);
		if ( $existing ) {
			$wpdb->update(Schema::table('camp_prearrival'), $payload, ['camp_id' => $camp_id]);
			return;
		}

		$wpdb->insert(Schema::table('camp_prearrival'), $payload);
	}

	public static function get_checklist(int $camp_id): array {
		global $wpdb;
		$table = Schema::table('camp_checklist_items');
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE camp_id = %d ORDER BY sort_order ASC, id ASC",
				$camp_id
			)
		) ?: [];
	}

	public static function replace_checklist(int $camp_id, array $items): void {
		global $wpdb;
		$table = Schema::table('camp_checklist_items');

		$wpdb->delete($table, ['camp_id' => $camp_id]);

		$sort = 0;
		foreach ( $items as $item ) {
			$label = sanitize_text_field($item['label'] ?? '');
			if ( $label === '' ) {
				continue;
			}

			$wpdb->insert($table, [
				'camp_id'       => $camp_id,
				'party'         => self::sanitize_checklist_party($item['party'] ?? self::CHECKLIST_PARTY_SHARED),
				'label'         => $label,
				'status'        => self::sanitize_checklist_status($item['status'] ?? self::CHECKLIST_STATUS_PENDING),
				'assigned_to'   => sanitize_text_field($item['assigned_to'] ?? ''),
				'due_date'      => self::sanitize_date_or_null($item['due_date'] ?? ''),
				'comment'       => sanitize_textarea_field($item['comment'] ?? ''),
				'sort_order'    => $sort++,
				'completed_at'  => ($item['status'] ?? '') === self::CHECKLIST_STATUS_DONE ? current_time('mysql') : null,
				'completed_by'  => ($item['status'] ?? '') === self::CHECKLIST_STATUS_DONE ? (int) get_current_user_id() : null,
			]);
		}
	}

	/**
	 * @return array{total:int,done:int,overdue:int,percent:int}
	 */
	public static function get_readiness_summary(int $camp_id): array {
		global $wpdb;

		$table = Schema::table('camp_checklist_items');
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) AS total_items,
					COALESCE(SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END), 0) AS done_items,
					COALESCE(SUM(CASE WHEN status <> 'done' AND due_date IS NOT NULL AND due_date < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue_items
				 FROM {$table}
				 WHERE camp_id = %d",
				$camp_id
			)
		);

		$total   = (int) ($row->total_items ?? 0);
		$done    = (int) ($row->done_items ?? 0);
		$overdue = (int) ($row->overdue_items ?? 0);

		return [
			'total'   => $total,
			'done'    => $done,
			'overdue' => $overdue,
			'percent' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
		];
	}

	/**
	 * @return array<string,int>
	 */
	public static function get_future_module_counts(int $camp_id): array {
		return [
			'documents'   => self::count_rows('camp_documents', $camp_id),
			'payments'    => self::count_rows('camp_payments', $camp_id),
			'actuals'     => self::count_rows('camp_actual_stays', $camp_id),
			'pricing'     => self::count_rows('camp_pricing_rules', $camp_id),
			'settlements' => self::count_rows('camp_settlements', $camp_id),
			'issues'      => self::count_rows('camp_settlement_issues', $camp_id),
			'closures'    => self::count_rows('camp_closures', $camp_id),
		];
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public static function default_checklist_rows(int $minimum_rows = 10): array {
		$rows = [];
		foreach ( self::DEFAULT_CHECKLIST as $item ) {
			$rows[] = [
				'label'       => $item['label'],
				'party'       => $item['party'],
				'status'      => self::CHECKLIST_STATUS_PENDING,
				'assigned_to' => '',
				'due_date'    => '',
				'comment'     => '',
			];
		}

		while ( count($rows) < $minimum_rows ) {
			$rows[] = [
				'label'       => '',
				'party'       => self::CHECKLIST_PARTY_SHARED,
				'status'      => self::CHECKLIST_STATUS_PENDING,
				'assigned_to' => '',
				'due_date'    => '',
				'comment'     => '',
			];
		}

		return $rows;
	}

	private static function count_rows(string $table_key, int $camp_id): int {
		global $wpdb;
		$table = Schema::table($table_key);
		return (int) $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE camp_id = %d", $camp_id)
		);
	}

	private static function sanitize_stage(string $value): string {
		$value = sanitize_key($value);
		return array_key_exists($value, self::PROCESS_STAGES) ? $value : self::STAGE_INQUIRY;
	}

	private static function sanitize_risk_level(string $value): string {
		$value = sanitize_key($value);
		return array_key_exists($value, self::RISK_LEVELS) ? $value : self::RISK_LOW;
	}

	private static function sanitize_checklist_party(string $value): string {
		$value = sanitize_key($value);
		return array_key_exists($value, self::CHECKLIST_PARTIES) ? $value : self::CHECKLIST_PARTY_SHARED;
	}

	private static function sanitize_checklist_status(string $value): string {
		$value = sanitize_key($value);
		return array_key_exists($value, self::CHECKLIST_STATUSES) ? $value : self::CHECKLIST_STATUS_PENDING;
	}

	private static function sanitize_date_or_null(string $value): ?string {
		$value = sanitize_text_field($value);
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
	}

	private static function sanitize_time_or_null(string $value): ?string {
		$value = sanitize_text_field($value);
		return preg_match('/^\d{2}:\d{2}$/', $value) ? $value : null;
	}
}
