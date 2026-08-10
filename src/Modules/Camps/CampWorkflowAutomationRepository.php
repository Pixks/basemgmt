<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Camps;

use BaseMgmt\Core\OperationLogger;
use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Handles automated workflow events, reminders, and attention flags for camps.
 */
final class CampWorkflowAutomationRepository {

	public const STATUS_OPEN     = 'open';
	public const STATUS_RESOLVED = 'resolved';

	public const TYPE_STAGE_CHANGE      = 'stage_change';
	public const TYPE_OVERDUE_PAYMENT   = 'overdue_payment';
	public const TYPE_MISSING_PREARRIVAL = 'missing_prearrival';
	public const TYPE_UPCOMING_START    = 'upcoming_start';
	public const TYPE_MISSING_SETTLEMENT = 'missing_settlement';

	public static function evaluate_all_active_camps(): void {
		foreach ( CampRepository::get_all(['status' => 'active']) as $camp ) {
			self::evaluate_camp((int) $camp->id);
		}
	}

	public static function evaluate_camp(int $camp_id): void {
		$camp = CampRepository::get($camp_id);
		if ( ! $camp ) {
			return;
		}

		$case          = CampCaseRepository::get_case($camp_id);
		$organizer     = CampCaseRepository::get_organizer($camp_id);
		$prearrival    = CampCaseRepository::get_prearrival($camp_id);
		$readiness     = CampCaseRepository::get_readiness_summary($camp_id);
		$module_summary = CampCaseRepository::get_module_summary($camp_id);
		$today         = current_time('Y-m-d');
		$days_to_start = self::days_between($today, (string) ($camp->start_date ?? ''));
		$days_after_end = self::days_between((string) ($camp->end_date ?? ''), $today);
		$open_keys     = [];
		$auto_attention = false;

		if ( (int) ($module_summary['payments']['overdue'] ?? 0) > 0 ) {
			$open_keys[]      = self::TYPE_OVERDUE_PAYMENT;
			$auto_attention   = true;
			self::upsert_event(
				$camp_id,
				self::TYPE_OVERDUE_PAYMENT,
				__('Zaległa płatność wymaga reakcji', 'basemgmt'),
				__('Co najmniej jedna płatność przekroczyła termin i nadal nie jest oznaczona jako opłacona.', 'basemgmt'),
				[
					'severity'         => CampCaseRepository::CHECKLIST_PRIORITY_CRITICAL,
					'suggested_action' => __('Skontaktuj się z organizatorem i potwierdź termin opłacenia zaległości.', 'basemgmt'),
					'draft_message'    => sprintf(__('Dzień dobry, przypominamy o zaległej płatności za obóz "%s". Prosimy o potwierdzenie terminu realizacji przelewu.', 'basemgmt'), (string) $camp->name),
					'reminder_date'    => $today,
					'source_stage'     => (string) ($case->process_stage ?? CampCaseRepository::STAGE_INQUIRY),
					'metadata'         => ['overdue_count' => (int) $module_summary['payments']['overdue']],
				]
			);
			CampCaseRepository::ensure_checklist_task($camp_id, [
				'label'    => __('Follow-up zaległej płatności', 'basemgmt'),
				'party'    => CampCaseRepository::CHECKLIST_PARTY_CENTER,
				'status'   => CampCaseRepository::CHECKLIST_STATUS_PENDING,
				'priority' => CampCaseRepository::CHECKLIST_PRIORITY_CRITICAL,
				'due_date' => $today,
				'comment'  => __('Automatycznie dodane po wykryciu płatności po terminie.', 'basemgmt'),
			]);
		}

		if ( $days_to_start !== null && $days_to_start >= 0 && $days_to_start <= 14 && ! CampCaseRepository::is_prearrival_ready($prearrival) ) {
			$open_keys[]    = self::TYPE_MISSING_PREARRIVAL;
			$auto_attention = true;
			self::upsert_event(
				$camp_id,
				self::TYPE_MISSING_PREARRIVAL,
				__('Brakuje danych operacyjnych przed przyjazdem', 'basemgmt'),
				sprintf(
					/* translators: %d number of days before start */
					__('Do startu obozu zostało %d dni, a dane przyjazdu / liczebności nie są kompletne.', 'basemgmt'),
					$days_to_start
				),
				[
					'severity'         => $days_to_start <= 7 ? CampCaseRepository::CHECKLIST_PRIORITY_CRITICAL : CampCaseRepository::CHECKLIST_PRIORITY_HIGH,
					'suggested_action' => __('Uzupełnij terminy przyjazdu, liczebność oraz osoby upoważnione.', 'basemgmt'),
					'draft_message'    => sprintf(__('Dzień dobry, zbliża się przyjazd obozu "%s". Prosimy o pilne uzupełnienie danych operacyjnych (przyjazd, liczebność, kontakty).', 'basemgmt'), (string) $camp->name),
					'reminder_date'    => $today,
					'source_stage'     => (string) ($case->process_stage ?? CampCaseRepository::STAGE_INQUIRY),
				]
			);
			CampCaseRepository::ensure_checklist_task($camp_id, [
				'label'    => __('Uzupełnij brakujące dane prearrival', 'basemgmt'),
				'party'    => CampCaseRepository::CHECKLIST_PARTY_ORGANIZER,
				'status'   => CampCaseRepository::CHECKLIST_STATUS_PENDING,
				'priority' => $days_to_start <= 7 ? CampCaseRepository::CHECKLIST_PRIORITY_CRITICAL : CampCaseRepository::CHECKLIST_PRIORITY_HIGH,
				'due_date' => (string) ($camp->start_date ?? $today),
				'comment'  => __('Automatycznie dodane, bo start obozu się zbliża.', 'basemgmt'),
			]);
		}

		if ( $days_to_start !== null && $days_to_start >= 0 && $days_to_start <= 7 && (int) ($readiness['percent'] ?? 0) < 100 ) {
			$open_keys[] = self::TYPE_UPCOMING_START;
			if ( $days_to_start <= 3 ) {
				$auto_attention = true;
			}
			self::upsert_event(
				$camp_id,
				self::TYPE_UPCOMING_START,
				__('Zbliżający się start obozu', 'basemgmt'),
				sprintf(
					/* translators: 1: camp name, 2: percent */
					__('Obóz "%1$s" startuje wkrótce, a gotowość workflow wynosi %2$d%%.', 'basemgmt'),
					(string) $camp->name,
					(int) ($readiness['percent'] ?? 0)
				),
				[
					'severity'         => $days_to_start <= 3 ? CampCaseRepository::CHECKLIST_PRIORITY_HIGH : CampCaseRepository::CHECKLIST_PRIORITY_NORMAL,
					'suggested_action' => __('Przejrzyj otwarte taski, blokery i przygotowanie operacyjne przed startem.', 'basemgmt'),
					'draft_message'    => sprintf(__('Przypomnienie wewnętrzne: obóz "%s" zbliża się do startu. Sprawdź otwarte taski, wpłaty i przygotowanie operacyjne.', 'basemgmt'), (string) $camp->name),
					'reminder_date'    => (string) ($camp->start_date ?? $today),
					'source_stage'     => (string) ($case->process_stage ?? CampCaseRepository::STAGE_INQUIRY),
				]
			);
		}

		if ( $days_after_end !== null && $days_after_end > 0 && (int) ($module_summary['settlements']['total'] ?? 0) === 0 ) {
			$open_keys[]    = self::TYPE_MISSING_SETTLEMENT;
			$auto_attention = true;
			self::upsert_event(
				$camp_id,
				self::TYPE_MISSING_SETTLEMENT,
				__('Pobyt zakończony bez rozliczenia', 'basemgmt'),
				__('Obóz ma już zakończony pobyt, ale nie ma żadnego rozliczenia końcowego.', 'basemgmt'),
				[
					'severity'         => CampCaseRepository::CHECKLIST_PRIORITY_CRITICAL,
					'suggested_action' => __('Przygotuj rozliczenie końcowe, wyjaśnij rozbieżności i domknij sprawę.', 'basemgmt'),
					'draft_message'    => sprintf(__('Pobyt obozu "%s" już się zakończył. Potrzebne jest przygotowanie rozliczenia końcowego i zamknięcie sprawy.', 'basemgmt'), (string) $camp->name),
					'reminder_date'    => $today,
					'source_stage'     => (string) ($case->process_stage ?? CampCaseRepository::STAGE_INQUIRY),
				]
			);
			CampCaseRepository::ensure_checklist_task($camp_id, [
				'label'    => __('Przygotuj rozliczenie końcowe', 'basemgmt'),
				'party'    => CampCaseRepository::CHECKLIST_PARTY_CENTER,
				'status'   => CampCaseRepository::CHECKLIST_STATUS_PENDING,
				'priority' => CampCaseRepository::CHECKLIST_PRIORITY_CRITICAL,
				'due_date' => $today,
				'comment'  => __('Automatycznie dodane po zakończeniu pobytu bez rozliczenia.', 'basemgmt'),
			]);
		}

		self::resolve_missing_events(
			$camp_id,
			[
				self::TYPE_OVERDUE_PAYMENT,
				self::TYPE_MISSING_PREARRIVAL,
				self::TYPE_UPCOMING_START,
				self::TYPE_MISSING_SETTLEMENT,
			],
			$open_keys
		);
		CampCaseRepository::set_attention_state($camp_id, $auto_attention);
	}

	public static function handle_stage_change(int $camp_id, string $old_stage, string $new_stage): void {
		$camp       = CampRepository::get($camp_id);
		$case       = CampCaseRepository::get_case($camp_id);
		$organizer  = CampCaseRepository::get_organizer($camp_id);
		$prearrival = CampCaseRepository::get_prearrival($camp_id);
		$readiness  = CampCaseRepository::get_readiness_summary($camp_id);
		$workflow   = CampCaseRepository::build_workflow_snapshot(
			$camp,
			$case,
			$organizer,
			$prearrival,
			$readiness,
			CampCaseRepository::get_future_module_counts($camp_id)
		);
		$stage_labels = CampCaseRepository::process_stages();
		$target_label = $stage_labels[$new_stage] ?? $new_stage;
		$title        = sprintf(
			/* translators: 1: old stage label, 2: new stage label */
			__('Etap zmieniony: %1$s → %2$s', 'basemgmt'),
			$stage_labels[$old_stage] ?? ($old_stage ?: '—'),
			$target_label
		);
		$suggested_action = $workflow['next_actions'][0] ?? __('Przejrzyj checklistę i kolejny termin dla bieżącego etapu.', 'basemgmt');
		self::upsert_event(
			$camp_id,
			self::TYPE_STAGE_CHANGE,
			$title,
			__('Zmiana etapu uruchomiła automatyczne podpowiedzi i przypomnienia dla aktualnej fazy workflow.', 'basemgmt'),
			[
				'severity'         => CampCaseRepository::CHECKLIST_PRIORITY_LOW,
				'suggested_action' => $suggested_action,
				'draft_message'    => sprintf(__('Dzień dobry, sprawa obozu "%1$s" jest już na etapie "%2$s". Najbliższy krok: %3$s', 'basemgmt'), (string) ($camp->name ?? ''), $target_label, $suggested_action),
				'reminder_date'    => (string) ($case->next_action_due_date ?? ($camp->start_date ?? current_time('Y-m-d'))),
				'source_stage'     => $new_stage,
				'metadata'         => ['old_stage' => $old_stage, 'new_stage' => $new_stage],
			]
		);
		OperationLogger::log('camp_workflow_stage_change_automation', 'camp_workflow_event', $camp_id, [
			'old_stage' => $old_stage,
			'new_stage' => $new_stage,
		]);
	}

	public static function get_open_events(int $camp_id, int $limit = 10): array {
		global $wpdb;
		$table = Schema::table('camp_workflow_events');
		if ( ! self::table_exists($table) ) {
			return [];
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				 FROM {$table}
				 WHERE camp_id = %d AND status = %s
				 ORDER BY " . self::severity_order_sql() . ", COALESCE(reminder_date, '9999-12-31') ASC, id DESC
				 LIMIT %d",
				$camp_id,
				self::STATUS_OPEN,
				max(1, $limit)
			)
		) ?: [];
	}

	public static function get_recent_events(int $camp_id, int $limit = 10): array {
		global $wpdb;
		$table = Schema::table('camp_workflow_events');
		if ( ! self::table_exists($table) ) {
			return [];
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				 FROM {$table}
				 WHERE camp_id = %d
				 ORDER BY id DESC
				 LIMIT %d",
				$camp_id,
				max(1, $limit)
			)
		) ?: [];
	}

	private static function upsert_event(int $camp_id, string $event_key, string $title, string $description, array $payload = []): void {
		global $wpdb;

		$table = Schema::table('camp_workflow_events');
		if ( ! self::table_exists($table) ) {
			return;
		}

		$row = [
			'camp_id'          => $camp_id,
			'event_key'        => sanitize_key($event_key),
			'event_type'       => sanitize_key((string) ($payload['event_type'] ?? $event_key)),
			'severity'         => sanitize_key((string) ($payload['severity'] ?? CampCaseRepository::CHECKLIST_PRIORITY_NORMAL)),
			'status'           => self::STATUS_OPEN,
			'title'            => sanitize_text_field($title),
			'description'      => sanitize_textarea_field($description),
			'suggested_action' => sanitize_textarea_field((string) ($payload['suggested_action'] ?? '')),
			'draft_message'    => sanitize_textarea_field((string) ($payload['draft_message'] ?? '')),
			'reminder_date'    => self::sanitize_date_or_null((string) ($payload['reminder_date'] ?? '')),
			'source_stage'     => sanitize_key((string) ($payload['source_stage'] ?? '')),
			'metadata_json'    => ! empty($payload['metadata']) ? wp_json_encode($payload['metadata']) : null,
			'resolved_at'      => null,
		];

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE camp_id = %d AND event_key = %s LIMIT 1",
				$camp_id,
				$row['event_key']
			)
		);

		if ( $existing ) {
			$wpdb->update($table, $row, ['id' => (int) $existing]);
			return;
		}

		$wpdb->insert($table, $row);
		OperationLogger::log('camp_workflow_event_opened', 'camp_workflow_event', $camp_id, [
			'event_key' => $row['event_key'],
			'severity'  => $row['severity'],
		]);
	}

	private static function resolve_missing_events(int $camp_id, array $managed_keys, array $active_keys): void {
		global $wpdb;
		$table = Schema::table('camp_workflow_events');
		if ( ! self::table_exists($table) || empty($managed_keys) ) {
			return;
		}

		$managed_keys = array_values(array_map('sanitize_key', $managed_keys));
		$active_keys  = array_values(array_map('sanitize_key', $active_keys));
		$placeholders = implode(', ', array_fill(0, count($managed_keys), '%s'));
		$params       = array_merge([$camp_id, self::STATUS_OPEN], $managed_keys);
		$sql          = "SELECT id, event_key FROM {$table} WHERE camp_id = %d AND status = %s AND event_key IN ({$placeholders})";
		$rows         = $wpdb->get_results($wpdb->prepare($sql, ...$params)) ?: [];

		foreach ( $rows as $row ) {
			if ( in_array((string) $row->event_key, $active_keys, true) ) {
				continue;
			}

			$wpdb->update(
				$table,
				[
					'status'      => self::STATUS_RESOLVED,
					'resolved_at' => current_time('mysql'),
				],
				['id' => (int) $row->id],
				['%s', '%s'],
				['%d']
			);
			OperationLogger::log('camp_workflow_event_resolved', 'camp_workflow_event', $camp_id, [
				'event_key' => (string) $row->event_key,
			]);
		}
	}

	private static function severity_order_sql(): string {
		return "CASE severity
			WHEN 'critical' THEN 1
			WHEN 'high' THEN 2
			WHEN 'normal' THEN 3
			WHEN 'medium' THEN 3
			WHEN 'low' THEN 4
			ELSE 5
		END";
	}

	private static function sanitize_date_or_null(string $value): ?string {
		$value = sanitize_text_field($value);
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
	}

	private static function days_between(string $from, string $to): ?int {
		if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ) {
			return null;
		}

		$from_dt = new \DateTimeImmutable($from);
		$to_dt   = new \DateTimeImmutable($to);
		return (int) $from_dt->diff($to_dt)->format('%r%a');
	}

	private static function table_exists(string $table): bool {
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
	}
}
