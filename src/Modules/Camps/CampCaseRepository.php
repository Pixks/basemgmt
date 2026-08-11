<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Camps;

use BaseMgmt\Core\OperationLogger;
use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Handles the extended "camp case file" workflow and related records.
 */
final class CampCaseRepository {

	private static ?bool $tables_ready_cache = null;

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

	public const RISK_LOW      = 'low';
	public const RISK_MEDIUM   = 'medium';
	public const RISK_HIGH     = 'high';
	public const RISK_CRITICAL = 'critical';

	public const CHECKLIST_PARTY_ORGANIZER = 'organizer';
	public const CHECKLIST_PARTY_CENTER    = 'center';
	public const CHECKLIST_PARTY_SHARED    = 'shared';

	public const CHECKLIST_STATUS_PENDING     = 'pending';
	public const CHECKLIST_STATUS_IN_PROGRESS = 'in_progress';
	public const CHECKLIST_STATUS_DONE        = 'done';
	public const CHECKLIST_STATUS_BLOCKED     = 'blocked';

	public const CHECKLIST_PRIORITY_LOW      = 'low';
	public const CHECKLIST_PRIORITY_NORMAL   = 'normal';
	public const CHECKLIST_PRIORITY_HIGH     = 'high';
	public const CHECKLIST_PRIORITY_CRITICAL = 'critical';

	public static function process_stages(): array {
		return [
			self::STAGE_INQUIRY          => __('Nowe zapytanie', 'basemgmt'),
			self::STAGE_OFFER            => __('Oferta przygotowana', 'basemgmt'),
			self::STAGE_NEGOTIATION      => __('Negocjacje', 'basemgmt'),
			self::STAGE_TENTATIVE        => __('Rezerwacja wstępna', 'basemgmt'),
			self::STAGE_CONTRACT_DRAFT   => __('Umowa do podpisu', 'basemgmt'),
			self::STAGE_CONTRACT_SIGNED  => __('Umowa podpisana', 'basemgmt'),
			self::STAGE_AWAITING_PAYMENT => __('Oczekiwanie na płatność', 'basemgmt'),
			self::STAGE_READY            => __('Gotowe do przyjazdu', 'basemgmt'),
			self::STAGE_ON_SITE          => __('Pobyt', 'basemgmt'),
			self::STAGE_SETTLEMENT       => __('Rozliczenie', 'basemgmt'),
			self::STAGE_CLOSED           => __('Zamknięte', 'basemgmt'),
			self::STAGE_CANCELLED        => __('Anulowane', 'basemgmt'),
		];
	}

	public static function workflow_phases(): array {
		return [
			'lead' => [
				'label'  => __('Lead / zapytanie', 'basemgmt'),
				'stages' => [
					self::STAGE_INQUIRY,
				],
			],
			'offer' => [
				'label'  => __('Oferta i ustalenia', 'basemgmt'),
				'stages' => [
					self::STAGE_OFFER,
					self::STAGE_NEGOTIATION,
					self::STAGE_TENTATIVE,
				],
			],
			'contract' => [
				'label'  => __('Umowa i płatności', 'basemgmt'),
				'stages' => [
					self::STAGE_CONTRACT_DRAFT,
					self::STAGE_CONTRACT_SIGNED,
					self::STAGE_AWAITING_PAYMENT,
				],
			],
			'operations' => [
				'label'  => __('Przygotowanie operacyjne', 'basemgmt'),
				'stages' => [
					self::STAGE_READY,
				],
			],
			'on_site' => [
				'label'  => __('Przyjazd i pobyt', 'basemgmt'),
				'stages' => [
					self::STAGE_ON_SITE,
				],
			],
			'closing' => [
				'label'  => __('Rozliczenie i zamknięcie', 'basemgmt'),
				'stages' => [
					self::STAGE_SETTLEMENT,
					self::STAGE_CLOSED,
					self::STAGE_CANCELLED,
				],
			],
		];
	}

	public static function risk_levels(): array {
		return [
			self::RISK_LOW      => __('Niskie', 'basemgmt'),
			self::RISK_MEDIUM   => __('Średnie', 'basemgmt'),
			self::RISK_HIGH     => __('Wysokie', 'basemgmt'),
			self::RISK_CRITICAL => __('Krytyczne', 'basemgmt'),
		];
	}

	public static function checklist_parties(): array {
		return [
			self::CHECKLIST_PARTY_ORGANIZER => __('Organizator', 'basemgmt'),
			self::CHECKLIST_PARTY_CENTER    => __('Ośrodek', 'basemgmt'),
			self::CHECKLIST_PARTY_SHARED    => __('Wspólne', 'basemgmt'),
		];
	}

	public static function checklist_statuses(): array {
		return [
			self::CHECKLIST_STATUS_PENDING     => __('Oczekuje', 'basemgmt'),
			self::CHECKLIST_STATUS_IN_PROGRESS => __('W toku', 'basemgmt'),
			self::CHECKLIST_STATUS_DONE        => __('Gotowe', 'basemgmt'),
			self::CHECKLIST_STATUS_BLOCKED     => __('Zablokowane', 'basemgmt'),
		];
	}

	public static function checklist_priorities(): array {
		return [
			self::CHECKLIST_PRIORITY_LOW      => __('Niski', 'basemgmt'),
			self::CHECKLIST_PRIORITY_NORMAL   => __('Normalny', 'basemgmt'),
			self::CHECKLIST_PRIORITY_HIGH     => __('Wysoki', 'basemgmt'),
			self::CHECKLIST_PRIORITY_CRITICAL => __('Krytyczny', 'basemgmt'),
		];
	}

	public static function allowed_stage_transitions(): array {
		return [
			self::STAGE_INQUIRY          => [self::STAGE_OFFER, self::STAGE_CANCELLED],
			self::STAGE_OFFER            => [self::STAGE_NEGOTIATION, self::STAGE_TENTATIVE, self::STAGE_CANCELLED],
			self::STAGE_NEGOTIATION      => [self::STAGE_OFFER, self::STAGE_TENTATIVE, self::STAGE_CANCELLED],
			self::STAGE_TENTATIVE        => [self::STAGE_NEGOTIATION, self::STAGE_CONTRACT_DRAFT, self::STAGE_CANCELLED],
			self::STAGE_CONTRACT_DRAFT   => [self::STAGE_TENTATIVE, self::STAGE_CONTRACT_SIGNED, self::STAGE_CANCELLED],
			self::STAGE_CONTRACT_SIGNED  => [self::STAGE_CONTRACT_DRAFT, self::STAGE_AWAITING_PAYMENT, self::STAGE_READY, self::STAGE_CANCELLED],
			self::STAGE_AWAITING_PAYMENT => [self::STAGE_CONTRACT_SIGNED, self::STAGE_READY, self::STAGE_CANCELLED],
			self::STAGE_READY            => [self::STAGE_AWAITING_PAYMENT, self::STAGE_ON_SITE, self::STAGE_CANCELLED],
			self::STAGE_ON_SITE          => [self::STAGE_READY, self::STAGE_SETTLEMENT],
			self::STAGE_SETTLEMENT       => [self::STAGE_ON_SITE, self::STAGE_CLOSED],
			self::STAGE_CLOSED           => [],
			self::STAGE_CANCELLED        => [],
		];
	}

	public static function can_transition(string $from_stage, string $to_stage): bool {
		$from_stage = self::sanitize_stage($from_stage);
		$to_stage   = self::sanitize_stage($to_stage);
		if ( $from_stage === $to_stage ) {
			return true;
		}

		return in_array($to_stage, self::allowed_stage_transitions()[$from_stage] ?? [], true);
	}

	public static function default_checklist_template(): array {
		return [
			['label' => __('Podpisana umowa', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_ORGANIZER],
			['label' => __('Zaksięgowana zaliczka', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_ORGANIZER],
			['label' => __('Lista uczestników i kadry', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_ORGANIZER],
			['label' => __('Dane kontaktowe osób odpowiedzialnych', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_ORGANIZER],
			['label' => __('Potwierdzone ubezpieczenie', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_ORGANIZER],
			['label' => __('Informacje o dietach i potrzebach szczególnych', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_SHARED],
			['label' => __('Zatwierdzony plan infrastruktury', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_SHARED],
			['label' => __('Przygotowane zakwaterowanie i klucze', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_CENTER],
			['label' => __('Zapotrzebowanie techniczne lub transportowe', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_SHARED],
		];
	}

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
			'manual_attention'      => empty($data['needs_attention']) ? 0 : 1,
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

	public static function get_phase_for_stage(string $stage): string {
		$stage = self::sanitize_stage($stage);
		foreach ( self::workflow_phases() as $phase => $config ) {
			if ( in_array($stage, $config['stages'], true) ) {
				return $phase;
			}
		}

		return 'lead';
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
			'billing_regon'           => sanitize_text_field($data['billing_regon'] ?? ''),
			'billing_krs'             => sanitize_text_field($data['billing_krs'] ?? ''),
			'billing_street'          => sanitize_text_field($data['billing_street'] ?? ''),
			'billing_city'            => sanitize_text_field($data['billing_city'] ?? ''),
			'billing_zip'             => sanitize_text_field($data['billing_zip'] ?? ''),
			'bank_name'               => sanitize_text_field($data['bank_name'] ?? ''),
			'bank_account'            => sanitize_text_field($data['bank_account'] ?? ''),
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
		$existing_items = self::get_checklist($camp_id);
		$preserved_done = [];

		foreach ( $existing_items as $existing_item ) {
			if ( $existing_item->status !== self::CHECKLIST_STATUS_DONE ) {
				continue;
			}

			$key = self::checklist_item_key((string) $existing_item->label, (string) $existing_item->party);
			$preserved_done[$key] = [
				'id'           => (int) $existing_item->id,
				'completed_at' => $existing_item->completed_at,
				'completed_by' => $existing_item->completed_by,
			];
			$preserved_done['id:' . (int) $existing_item->id] = $preserved_done[$key];
		}

		$wpdb->query('START TRANSACTION');
		$deleted = $wpdb->delete($table, ['camp_id' => $camp_id]);
		if ( $deleted === false ) {
			$wpdb->query('ROLLBACK');
			return;
		}

		$sort = 0;
		foreach ( $items as $item ) {
			$label = sanitize_text_field($item['label'] ?? '');
			if ( $label === '' ) {
				continue;
			}

			$party    = self::sanitize_checklist_party($item['party'] ?? self::CHECKLIST_PARTY_SHARED);
			$status   = self::sanitize_checklist_status($item['status'] ?? self::CHECKLIST_STATUS_PENDING);
			$priority = self::sanitize_checklist_priority($item['priority'] ?? self::CHECKLIST_PRIORITY_NORMAL);
			$key      = self::checklist_item_key($label, $party);
			$item_id = (int) ($item['id'] ?? 0);
			$done   = $item_id > 0 ? ($preserved_done['id:' . $item_id] ?? null) : ($preserved_done[$key] ?? null);

			$inserted = $wpdb->insert($table, [
				'camp_id'       => $camp_id,
				'party'         => $party,
				'label'         => $label,
				'description'   => sanitize_textarea_field($item['description'] ?? ''),
				'status'        => $status,
				'priority'      => $priority,
				'assigned_to'   => sanitize_text_field($item['assigned_to'] ?? ''),
				'due_date'      => self::sanitize_date_or_null($item['due_date'] ?? ''),
				'comment'       => sanitize_textarea_field($item['comment'] ?? ''),
				'sort_order'    => $sort++,
				'completed_at'  => $status === self::CHECKLIST_STATUS_DONE ? ($done['completed_at'] ?? current_time('mysql')) : null,
				'completed_by'  => $status === self::CHECKLIST_STATUS_DONE ? ($done['completed_by'] ?? (int) get_current_user_id()) : null,
			]);
			if ( $inserted === false ) {
				$wpdb->query('ROLLBACK');
				return;
			}
		}

		$wpdb->query('COMMIT');
	}

	/**
	 * Adds missing stage-template tasks without overwriting existing checklist rows.
	 */
	public static function sync_checklist_for_stage(int $camp_id, string $stage): void {
		$existing = self::get_checklist($camp_id);
		$rows     = array_map(
			static fn(object $item): array => [
				'label'       => (string) $item->label,
				'id'          => (string) $item->id,
				'party'       => (string) $item->party,
				'status'      => (string) $item->status,
				'priority'    => (string) ($item->priority ?? self::CHECKLIST_PRIORITY_NORMAL),
				'assigned_to' => (string) ($item->assigned_to ?? ''),
				'due_date'    => (string) ($item->due_date ?? ''),
				'comment'     => (string) ($item->comment ?? ''),
			],
			$existing
		);

		$known_keys = [];
		foreach ( $rows as $row ) {
			$known_keys[self::checklist_item_key((string) $row['label'], (string) $row['party'])] = true;
		}

		$changed = false;
		foreach ( self::stage_checklist_template($stage) as $item ) {
			$key = self::checklist_item_key($item['label'], $item['party']);
			if ( isset($known_keys[$key]) ) {
				continue;
			}

			$rows[]      = [
				'label'       => $item['label'],
				'id'          => '',
				'party'       => $item['party'],
				'status'      => self::CHECKLIST_STATUS_PENDING,
				'priority'    => self::CHECKLIST_PRIORITY_NORMAL,
				'assigned_to' => '',
				'due_date'    => '',
				'comment'     => '',
			];
			$known_keys[$key] = true;
			$changed          = true;
		}

		if ( ! $changed ) {
			return;
		}

		self::replace_checklist($camp_id, $rows);
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
	public static function default_checklist_rows(int $minimum_rows = 5): array {
		$rows = [];
		foreach ( self::default_checklist_template() as $item ) {
			$rows[] = [
				'label'       => $item['label'],
				'id'          => '',
				'party'       => $item['party'],
				'description' => '',
				'status'      => self::CHECKLIST_STATUS_PENDING,
				'priority'    => self::CHECKLIST_PRIORITY_NORMAL,
				'assigned_to' => '',
				'due_date'    => '',
				'comment'     => '',
			];
		}

		return $rows;
	}

	public static function pad_checklist_rows(array $items, int $minimum_rows = 0): array {
		$rows = array_map(
			static function (array $item): array {
				return [
					'label'       => (string) ($item['label'] ?? ''),
					'id'          => (string) ($item['id'] ?? ''),
					'party'       => (string) ($item['party'] ?? self::CHECKLIST_PARTY_SHARED),
					'description' => (string) ($item['description'] ?? ''),
					'status'      => (string) ($item['status'] ?? self::CHECKLIST_STATUS_PENDING),
					'priority'    => (string) ($item['priority'] ?? self::CHECKLIST_PRIORITY_NORMAL),
					'assigned_to' => (string) ($item['assigned_to'] ?? ''),
					'due_date'    => (string) ($item['due_date'] ?? ''),
					'comment'     => (string) ($item['comment'] ?? ''),
				];
			},
			$items
		);

		return $rows;
	}

	public static function get_single_checklist_item(int $id): ?object {
		global $wpdb;
		$table = Schema::table('camp_checklist_items');
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id)
		) ?: null;
	}

	public static function insert_checklist_item(int $camp_id, array $data): int {
		global $wpdb;
		$table  = Schema::table('camp_checklist_items');
		$status = self::sanitize_checklist_status($data['status'] ?? self::CHECKLIST_STATUS_PENDING);
		$wpdb->insert($table, [
			'camp_id'      => $camp_id,
			'party'        => self::sanitize_checklist_party($data['party'] ?? self::CHECKLIST_PARTY_SHARED),
			'label'        => sanitize_text_field($data['label'] ?? ''),
			'description'  => sanitize_textarea_field($data['description'] ?? ''),
			'status'       => $status,
			'priority'     => self::sanitize_checklist_priority($data['priority'] ?? self::CHECKLIST_PRIORITY_NORMAL),
			'assigned_to'  => sanitize_text_field($data['assigned_to'] ?? ''),
			'due_date'     => self::sanitize_date_or_null($data['due_date'] ?? ''),
			'comment'      => sanitize_textarea_field($data['comment'] ?? ''),
			'sort_order'   => (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM {$table} WHERE camp_id=%d", $camp_id)),
			'completed_at' => $status === self::CHECKLIST_STATUS_DONE ? current_time('mysql') : null,
			'completed_by' => $status === self::CHECKLIST_STATUS_DONE ? (int) get_current_user_id() : null,
		]);
		return (int) $wpdb->insert_id;
	}

	public static function update_checklist_item(int $id, int $camp_id, array $data): void {
		global $wpdb;
		$table      = Schema::table('camp_checklist_items');
		$status     = self::sanitize_checklist_status($data['status'] ?? self::CHECKLIST_STATUS_PENDING);
		$existing   = self::get_single_checklist_item($id);
		$was_done   = $existing && $existing->status === self::CHECKLIST_STATUS_DONE;
		$now_done   = $status === self::CHECKLIST_STATUS_DONE;
		$wpdb->update(
			$table,
			[
				'party'        => self::sanitize_checklist_party($data['party'] ?? self::CHECKLIST_PARTY_SHARED),
				'label'        => sanitize_text_field($data['label'] ?? ''),
				'description'  => sanitize_textarea_field($data['description'] ?? ''),
				'status'       => $status,
				'priority'     => self::sanitize_checklist_priority($data['priority'] ?? self::CHECKLIST_PRIORITY_NORMAL),
				'assigned_to'  => sanitize_text_field($data['assigned_to'] ?? ''),
				'due_date'     => self::sanitize_date_or_null($data['due_date'] ?? ''),
				'comment'      => sanitize_textarea_field($data['comment'] ?? ''),
				'completed_at' => ( $now_done && ! $was_done ) ? current_time('mysql') : ( $was_done ? $existing->completed_at : null ),
				'completed_by' => ( $now_done && ! $was_done ) ? (int) get_current_user_id() : ( $was_done ? $existing->completed_by : null ),
			],
			['id' => $id, 'camp_id' => $camp_id]
		);
	}

	public static function delete_checklist_item(int $id, int $camp_id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('camp_checklist_items'), ['id' => $id, 'camp_id' => $camp_id]);
	}

	public static function set_attention_state(int $camp_id, bool $automation_attention): void {
		global $wpdb;

		$case = self::get_case($camp_id);
		if ( ! $case ) {
			return;
		}

		$manual_attention = ! empty($case->manual_attention) || ! empty($case->needs_attention);
		$wpdb->update(
			Schema::table('camp_cases'),
			[
				'needs_attention' => $manual_attention || $automation_attention ? 1 : 0,
			],
			['camp_id' => $camp_id],
			['%d'],
			['%d']
		);
	}

	public static function ensure_checklist_task(int $camp_id, array $task): void {
		$rows = array_map(
			static fn(object $item): array => [
				'label'       => (string) $item->label,
				'id'          => (string) $item->id,
				'party'       => (string) $item->party,
				'status'      => (string) $item->status,
				'priority'    => (string) ($item->priority ?? self::CHECKLIST_PRIORITY_NORMAL),
				'assigned_to' => (string) ($item->assigned_to ?? ''),
				'due_date'    => (string) ($item->due_date ?? ''),
				'comment'     => (string) ($item->comment ?? ''),
			],
			self::get_checklist($camp_id)
		);

		$label = sanitize_text_field((string) ($task['label'] ?? ''));
		if ( $label === '' ) {
			return;
		}

		$party    = self::sanitize_checklist_party((string) ($task['party'] ?? self::CHECKLIST_PARTY_CENTER));
		$status   = self::sanitize_checklist_status((string) ($task['status'] ?? self::CHECKLIST_STATUS_PENDING));
		$priority = self::sanitize_checklist_priority((string) ($task['priority'] ?? self::CHECKLIST_PRIORITY_HIGH));
		$key      = self::checklist_item_key($label, $party);
		$updated  = false;

		foreach ( $rows as &$row ) {
			if ( self::checklist_item_key((string) $row['label'], (string) $row['party']) !== $key ) {
				continue;
			}

			if ( (string) $row['status'] !== self::CHECKLIST_STATUS_DONE ) {
				$row['status'] = $status;
			}
			$row['priority']    = $priority;
			$row['assigned_to'] = sanitize_text_field((string) ($task['assigned_to'] ?? $row['assigned_to']));
			$row['due_date']    = (string) (self::sanitize_date_or_null((string) ($task['due_date'] ?? $row['due_date'])) ?? '');
			$row['comment']     = sanitize_textarea_field((string) ($task['comment'] ?? $row['comment']));
			$updated            = true;
			break;
		}
		unset($row);

		if ( ! $updated ) {
			$rows[] = [
				'label'       => $label,
				'id'          => '',
				'party'       => $party,
				'status'      => $status,
				'priority'    => $priority,
				'assigned_to' => sanitize_text_field((string) ($task['assigned_to'] ?? '')),
				'due_date'    => (string) (self::sanitize_date_or_null((string) ($task['due_date'] ?? '')) ?? ''),
				'comment'     => sanitize_textarea_field((string) ($task['comment'] ?? '')),
			];
		}

		self::replace_checklist($camp_id, $rows);
	}

	public static function get_open_checklist_items(int $camp_id, int $limit = 10): array {
		$rows = array_filter(
			self::get_checklist($camp_id),
			static fn(object $item): bool => (string) $item->status !== self::CHECKLIST_STATUS_DONE
		);

		usort($rows, static function (object $a, object $b): int {
			$a_overdue = ! empty($a->due_date) && (string) $a->due_date < current_time('Y-m-d');
			$b_overdue = ! empty($b->due_date) && (string) $b->due_date < current_time('Y-m-d');
			if ( $a_overdue !== $b_overdue ) {
				return $a_overdue ? -1 : 1;
			}

			$a_priority = self::priority_weight((string) ($a->priority ?? self::CHECKLIST_PRIORITY_NORMAL));
			$b_priority = self::priority_weight((string) ($b->priority ?? self::CHECKLIST_PRIORITY_NORMAL));
			if ( $a_priority !== $b_priority ) {
				return $b_priority <=> $a_priority;
			}

			return strcmp((string) ($a->due_date ?? ''), (string) ($b->due_date ?? ''));
		});

		return array_slice($rows, 0, max(1, $limit));
	}

	public static function get_recent_activity(int $camp_id, int $limit = 8): array {
		global $wpdb;

		$table = Schema::table('operation_logs');
		if ( ! self::table_exists($table) ) {
			return [];
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT created_at, action, object_type, details
				 FROM {$table}
				 WHERE object_id = %d
				   AND object_type IN ('camp', 'camp_case', 'camp_checklist', 'camp_workflow_event')
				 ORDER BY id DESC
				 LIMIT %d",
				$camp_id,
				max(1, $limit)
			)
		) ?: [];
	}

	public static function get_module_summary(int $camp_id): array {
		global $wpdb;

		$today = current_time('Y-m-d');
		$docs_t = Schema::table('camp_documents');
		$pay_sched_t = Schema::table('camp_payment_schedules');
		$pay_t = Schema::table('camp_payments');
		$settlements_t = Schema::table('camp_settlements');
		$issues_t = Schema::table('camp_settlement_issues');
		$closures_t = Schema::table('camp_closures');

		$documents = ['total' => 0, 'open' => 0, 'overdue' => 0, 'items' => []];
		if ( self::table_exists($docs_t) ) {
			$documents['items'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT title, status, due_date
					 FROM {$docs_t}
					 WHERE camp_id = %d
					 ORDER BY COALESCE(due_date, '9999-12-31') ASC, id DESC
					 LIMIT 5",
					$camp_id
				)
			) ?: [];
			$documents['total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$docs_t} WHERE camp_id = %d", $camp_id));
			$documents['open'] = (int) $wpdb->get_var(
				$wpdb->prepare("SELECT COUNT(*) FROM {$docs_t} WHERE camp_id = %d AND status <> 'signed'", $camp_id)
			);
			$documents['overdue'] = (int) $wpdb->get_var(
				$wpdb->prepare("SELECT COUNT(*) FROM {$docs_t} WHERE camp_id = %d AND due_date IS NOT NULL AND due_date < %s AND status <> 'signed'", $camp_id, $today)
			);
		}

		$payments = ['scheduled' => 0, 'paid' => 0, 'overdue' => 0, 'upcoming' => 0, 'items' => []];
		if ( self::table_exists($pay_sched_t) ) {
			$payments['items'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT label, amount, due_date, status
					 FROM {$pay_sched_t}
					 WHERE camp_id = %d
					 ORDER BY COALESCE(due_date, '9999-12-31') ASC, id DESC
					 LIMIT 5",
					$camp_id
				)
			) ?: [];
			$payments['scheduled'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$pay_sched_t} WHERE camp_id = %d", $camp_id));
			$payments['overdue'] = (int) $wpdb->get_var(
				$wpdb->prepare("SELECT COUNT(*) FROM {$pay_sched_t} WHERE camp_id = %d AND due_date IS NOT NULL AND due_date < %s AND status NOT IN ('paid','cancelled')", $camp_id, $today)
			);
			$payments['upcoming'] = (int) $wpdb->get_var(
				$wpdb->prepare("SELECT COUNT(*) FROM {$pay_sched_t} WHERE camp_id = %d AND due_date IS NOT NULL AND due_date BETWEEN %s AND DATE_ADD(%s, INTERVAL 7 DAY) AND status NOT IN ('paid','cancelled')", $camp_id, $today, $today)
			);
		}
		if ( self::table_exists($pay_t) ) {
			$payments['paid'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$pay_t} WHERE camp_id = %d", $camp_id));
		}

		$settlements = ['total' => 0, 'open' => 0, 'items' => []];
		if ( self::table_exists($settlements_t) ) {
			$settlements['items'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT status, period_end, outstanding_amount
					 FROM {$settlements_t}
					 WHERE camp_id = %d
					 ORDER BY id DESC
					 LIMIT 5",
					$camp_id
				)
			) ?: [];
			$settlements['total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$settlements_t} WHERE camp_id = %d", $camp_id));
			$settlements['open'] = (int) $wpdb->get_var(
				$wpdb->prepare("SELECT COUNT(*) FROM {$settlements_t} WHERE camp_id = %d AND status <> 'closed'", $camp_id)
			);
		}

		$issues = ['open' => 0, 'items' => []];
		if ( self::table_exists($issues_t) ) {
			$issues['items'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT title, status, created_at
					 FROM {$issues_t}
					 WHERE camp_id = %d
					 ORDER BY id DESC
					 LIMIT 5",
					$camp_id
				)
			) ?: [];
			$issues['open'] = (int) $wpdb->get_var(
				$wpdb->prepare("SELECT COUNT(*) FROM {$issues_t} WHERE camp_id = %d AND status <> 'resolved'", $camp_id)
			);
		}

		$closures = ['total' => 0, 'closed' => 0, 'items' => []];
		if ( self::table_exists($closures_t) ) {
			$closures['items'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT status, closed_at, follow_up_actions
					 FROM {$closures_t}
					 WHERE camp_id = %d
					 ORDER BY id DESC
					 LIMIT 3",
					$camp_id
				)
			) ?: [];
			$closures['total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$closures_t} WHERE camp_id = %d", $camp_id));
			$closures['closed'] = (int) $wpdb->get_var(
				$wpdb->prepare("SELECT COUNT(*) FROM {$closures_t} WHERE camp_id = %d AND status = 'closed'", $camp_id)
			);
		}

		return [
			'documents'   => $documents,
			'payments'    => $payments,
			'settlements' => $settlements,
			'issues'      => $issues,
			'closures'    => $closures,
		];
	}

	public static function is_organizer_ready(?object $organizer): bool {
		return self::organizer_ready($organizer);
	}

	public static function is_prearrival_ready(?object $prearrival): bool {
		return self::prearrival_ready($prearrival);
	}

	public static function stage_workspace(string $stage): array {
		$phase = self::get_phase_for_stage($stage);

		return match ( $phase ) {
			'lead' => [
				'label'    => __('Lead / zapytanie', 'basemgmt'),
				'sections' => ['process', 'organizer', 'checklist'],
			],
			'offer' => [
				'label'    => __('Oferta i ustalenia', 'basemgmt'),
				'sections' => ['process', 'organizer', 'checklist'],
			],
			'contract' => [
				'label'    => __('Umowa i płatności', 'basemgmt'),
				'sections' => ['process', 'organizer', 'checklist', 'settlement'],
			],
			'operations' => [
				'label'    => __('Przygotowanie operacyjne', 'basemgmt'),
				'sections' => ['process', 'prearrival', 'checklist', 'settlement'],
			],
			'on_site' => [
				'label'    => __('Przyjazd i pobyt', 'basemgmt'),
				'sections' => ['process', 'checklist', 'settlement'],
			],
			default => [
				'label'    => __('Rozliczenie i zamknięcie', 'basemgmt'),
				'sections' => ['process', 'settlement', 'checklist'],
			],
		};
	}

	/**
	 * @param array{total:int,done:int,overdue:int,percent:int} $readiness
	 * @param array<string,int>                                 $future_counts
	 * @return array{
	 *   current_stage:string,
	 *   current_stage_label:string,
	 *   current_phase:string,
	 *   current_phase_label:string,
	 *   health:string,
	 *   health_label:string,
	 *   blockers:array<int,string>,
	 *   next_actions:array<int,string>,
	 *   phases:array<int,array{slug:string,label:string,state:string}>
	 * }
	 */
	public static function build_workflow_snapshot(
		?object $camp,
		?object $case,
		?object $organizer,
		?object $prearrival,
		array $readiness,
		array $future_counts
	): array {
		$stage        = self::sanitize_stage((string) ($case->process_stage ?? self::STAGE_INQUIRY));
		$phase        = self::get_phase_for_stage($stage);
		$phase_map    = self::workflow_phases();
		$blockers     = self::collect_blockers($stage, $case, $organizer, $prearrival, $readiness, $future_counts);
		$next_actions = self::collect_next_actions($stage, $case, $organizer, $prearrival, $readiness, $future_counts, $camp);
		$health       = self::determine_health($case, $readiness, $blockers);

		return [
			'current_stage'       => $stage,
			'current_stage_label' => self::process_stages()[$stage] ?? $stage,
			'current_phase'       => $phase,
			'current_phase_label' => $phase_map[$phase]['label'] ?? $phase,
			'health'              => $health,
			'health_label'        => self::health_labels()[$health] ?? $health,
			'blockers'            => $blockers,
			'next_actions'        => $next_actions,
			'phases'              => self::phase_progress($phase),
		];
	}

	public static function tables_ready(): bool {
		global $wpdb;

		if ( self::$tables_ready_cache !== null ) {
			return self::$tables_ready_cache;
		}

		foreach (['camp_cases', 'camp_organizers', 'camp_checklist_items', 'camp_workflow_events', 'camp_prearrival'] as $key) {
			$table = Schema::table($key);
			if ( ! self::table_exists($table) ) {
				self::$tables_ready_cache = false;
				return false;
			}
		}

		self::$tables_ready_cache = true;
		return true;
	}

	public static function invalidate_tables_ready_cache(): void {
		self::$tables_ready_cache = null;
	}

	private static function count_rows(string $table_key, int $camp_id): int {
		global $wpdb;
		$table = Schema::table($table_key);

		if ( ! self::table_exists($table) ) {
			return 0;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE camp_id = %d", $camp_id)
		);
	}

	/**
	 * @return array<int,array{label:string,party:string}>
	 */
	private static function stage_checklist_template(string $stage): array {
		return match ( self::get_phase_for_stage($stage) ) {
			'lead' => [
				['label' => __('Potwierdzone terminy i liczebność wstępna', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_SHARED],
				['label' => __('Przypisany owner sprawy i termin kolejnego kontaktu', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_CENTER],
			],
			'offer' => [
				['label' => __('Przygotowana oferta i warunki pobytu', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_CENTER],
				['label' => __('Ustalone potrzeby programu i infrastruktury', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_SHARED],
				['label' => __('Decyzja organizatora / follow-up sprzedażowy', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_ORGANIZER],
			],
			'contract' => [
				['label' => __('Podpisana umowa', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_ORGANIZER],
				['label' => __('Harmonogram płatności i termin zaliczki', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_CENTER],
				['label' => __('Potwierdzenie wpłaty lub przypomnienie o płatności', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_SHARED],
			],
			'operations' => [
				['label' => __('Lista uczestników i kadry', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_ORGANIZER],
				['label' => __('Dane przyjazdu / wyjazdu i osoby upoważnione', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_ORGANIZER],
				['label' => __('Diety, alergeny i potrzeby dodatkowe', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_SHARED],
				['label' => __('Zakwaterowanie i infrastruktura gotowe', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_CENTER],
			],
			'on_site' => [
				['label' => __('Codzienna komunikacja i meldunki przebiegają bez blokad', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_SHARED],
				['label' => __('Zebrane dane do rozliczenia pobytu', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_CENTER],
			],
			'closing' => [
				['label' => __('Przygotowane rozliczenie końcowe', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_CENTER],
				['label' => __('Wyjaśnione rozbieżności i uwagi', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_SHARED],
				['label' => __('Sprawa zamknięta i zarchiwizowana', 'basemgmt'), 'party' => self::CHECKLIST_PARTY_CENTER],
			],
			default => self::default_checklist_template(),
		};
	}

	private static function health_labels(): array {
		return [
			'ok'       => __('Stabilnie', 'basemgmt'),
			'warning'  => __('Do dopilnowania', 'basemgmt'),
			'critical' => __('Blokery', 'basemgmt'),
		];
	}

	/**
	 * @return array<int,array{slug:string,label:string,state:string}>
	 */
	private static function phase_progress(string $current_phase): array {
		$phases        = [];
		$current_index = array_search($current_phase, array_keys(self::workflow_phases()), true);
		$current_index = $current_index === false ? 0 : $current_index;

		foreach ( array_values(self::workflow_phases()) as $index => $phase ) {
			$state = 'upcoming';
			if ( $index < $current_index ) {
				$state = 'done';
			} elseif ( $index === $current_index ) {
				$state = 'current';
			}

			$phases[] = [
				'slug'  => array_keys(self::workflow_phases())[$index],
				'label' => $phase['label'],
				'state' => $state,
			];
		}

		return $phases;
	}

	/**
	 * @param array{total:int,done:int,overdue:int,percent:int} $readiness
	 * @param array<string,int>                                 $future_counts
	 * @return string[]
	 */
	private static function collect_blockers(
		string $stage,
		?object $case,
		?object $organizer,
		?object $prearrival,
		array $readiness,
		array $future_counts
	): array {
		$blockers = [];
		$phase    = self::get_phase_for_stage($stage);

		if ( $phase !== 'lead' && ! self::organizer_ready($organizer) ) {
			$blockers[] = __('Brakuje kompletnych danych organizatora.', 'basemgmt');
		}

		if ( in_array($phase, ['operations', 'on_site'], true) && ! self::prearrival_ready($prearrival) ) {
			$blockers[] = __('Brakuje kompletu danych operacyjnych przed przyjazdem.', 'basemgmt');
		}

		if ( $phase === 'contract' && (int) ($future_counts['payments'] ?? 0) === 0 ) {
			$blockers[] = __('Etap umowy/płatności nie ma jeszcze żadnej wpłaty.', 'basemgmt');
		}

		if ( $phase === 'closing' && (int) ($future_counts['settlements'] ?? 0) === 0 ) {
			$blockers[] = __('Brakuje rozliczenia końcowego.', 'basemgmt');
		}

		if ( $stage === self::STAGE_CLOSED && (int) ($future_counts['closures'] ?? 0) === 0 ) {
			$blockers[] = __('Etap zamknięcia nie ma wpisu archiwizacyjnego.', 'basemgmt');
		}

		if ( (int) ($readiness['overdue'] ?? 0) > 0 ) {
			$blockers[] = sprintf(
				/* translators: %d number of overdue tasks */
				__('%d zadań checklisty jest po terminie.', 'basemgmt'),
				(int) $readiness['overdue']
			);
		}

		if ( empty($case?->owner_user_id) ) {
			$blockers[] = __('Brakuje przypisanego ownera sprawy.', 'basemgmt');
		}

		return array_values(array_unique($blockers));
	}

	/**
	 * @param array{total:int,done:int,overdue:int,percent:int} $readiness
	 * @param array<string,int>                                 $future_counts
	 * @return string[]
	 */
	private static function collect_next_actions(
		string $stage,
		?object $case,
		?object $organizer,
		?object $prearrival,
		array $readiness,
		array $future_counts,
		?object $camp
	): array {
		$actions = [];
		$phase   = self::get_phase_for_stage($stage);

		if ( ! self::organizer_ready($organizer) ) {
			$actions[] = __('Uzupełnij dane organizatora i kontakt do rozliczeń.', 'basemgmt');
		}

		if ( empty($case?->next_action_due_date) ) {
			$actions[] = __('Ustaw termin następnego działania dla ownera sprawy.', 'basemgmt');
		}

		if ( $phase === 'contract' && (int) ($future_counts['payments'] ?? 0) === 0 ) {
			$actions[] = __('Dodaj lub potwierdź pierwszą płatność / zaliczkę.', 'basemgmt');
		}

		if ( in_array($phase, ['operations', 'on_site'], true) && ! self::prearrival_ready($prearrival) ) {
			$actions[] = __('Dokończ dane przyjazdu, liczebności i osoby upoważnione.', 'basemgmt');
		}

		if ( (int) ($readiness['total'] ?? 0) === 0 ) {
			$actions[] = __('Wygeneruj checklistę dla aktualnego etapu i przypisz zadania.', 'basemgmt');
		} elseif ( (int) ($readiness['done'] ?? 0) < (int) ($readiness['total'] ?? 0) ) {
			$actions[] = __('Domknij otwarte zadania checklisty przed przejściem dalej.', 'basemgmt');
		}

		if ( $phase === 'closing' && (int) ($future_counts['settlements'] ?? 0) === 0 ) {
			$actions[] = __('Przygotuj rozliczenie końcowe i archiwizację sprawy.', 'basemgmt');
		}

		if ( $camp && ! empty($camp->start_date) && $phase !== 'closing' ) {
			$actions[] = sprintf(
				/* translators: %s camp start date */
				__('Pilnuj przygotowania obozu przed startem: %s.', 'basemgmt'),
				(string) $camp->start_date
			);
		}

		return array_values(array_slice(array_unique($actions), 0, 4));
	}

	/**
	 * @param array{total:int,done:int,overdue:int,percent:int} $readiness
	 * @param string[]                                          $blockers
	 */
	private static function determine_health(?object $case, array $readiness, array $blockers): string {
		if ( ! empty($blockers) || (string) ($case->risk_level ?? '') === self::RISK_CRITICAL ) {
			return 'critical';
		}

		$today = current_time('Y-m-d');
		if (
			! empty($case?->needs_attention)
			|| (int) ($readiness['overdue'] ?? 0) > 0
			|| ( ! empty($case?->next_action_due_date) && (string) $case->next_action_due_date <= $today )
		) {
			return 'warning';
		}

		return 'ok';
	}

	private static function organizer_ready(?object $organizer): bool {
		if ( ! $organizer ) {
			return false;
		}

		return (string) ($organizer->organization_name ?? '') !== ''
			&& (string) ($organizer->contact_person ?? '') !== ''
			&& (string) ($organizer->contact_email ?? '') !== '';
	}

	private static function prearrival_ready(?object $prearrival): bool {
		if ( ! $prearrival ) {
			return false;
		}

		return (string) ($prearrival->arrival_date ?? '') !== ''
			&& (string) ($prearrival->departure_date ?? '') !== ''
			&& ((int) ($prearrival->declared_participants ?? 0) > 0 || (int) ($prearrival->declared_staff ?? 0) > 0)
			&& (string) ($prearrival->authorized_contacts ?? '') !== '';
	}

	private static function sanitize_stage(string $value): string {
		$value = sanitize_key($value);
		return array_key_exists($value, self::process_stages()) ? $value : self::STAGE_INQUIRY;
	}

	private static function sanitize_risk_level(string $value): string {
		$value = sanitize_key($value);
		return array_key_exists($value, self::risk_levels()) ? $value : self::RISK_LOW;
	}

	private static function sanitize_checklist_party(string $value): string {
		$value = sanitize_key($value);
		return array_key_exists($value, self::checklist_parties()) ? $value : self::CHECKLIST_PARTY_SHARED;
	}

	private static function sanitize_checklist_status(string $value): string {
		$value = sanitize_key($value);
		return array_key_exists($value, self::checklist_statuses()) ? $value : self::CHECKLIST_STATUS_PENDING;
	}

	private static function sanitize_checklist_priority(string $value): string {
		$value = sanitize_key($value);
		return array_key_exists($value, self::checklist_priorities()) ? $value : self::CHECKLIST_PRIORITY_NORMAL;
	}

	private static function sanitize_date_or_null(string $value): ?string {
		$value = sanitize_text_field($value);
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
	}

	private static function sanitize_time_or_null(string $value): ?string {
		$value = sanitize_text_field($value);
		return preg_match('/^\d{2}:\d{2}$/', $value) ? $value : null;
	}

	private static function checklist_item_key(string $label, string $party): string {
		return md5($party . '|' . $label);
	}

	private static function priority_weight(string $priority): int {
		return match ( self::sanitize_checklist_priority($priority) ) {
			self::CHECKLIST_PRIORITY_CRITICAL => 4,
			self::CHECKLIST_PRIORITY_HIGH => 3,
			self::CHECKLIST_PRIORITY_NORMAL => 2,
			default => 1,
		};
	}

	private static function table_exists(string $table): bool {
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
	}
}

