<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Camps;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access layer for camps.
 */
final class CampRepository {

	/** Retrieve all camps with optional filters. */
	public static function get_all(array $args = []): array {
		global $wpdb;

		$table     = Schema::table('camps');
		$cases_t   = Schema::table('camp_cases');
		$org_t     = Schema::table('camp_organizers');
		$check_t   = Schema::table('camp_checklist_items');

		if ( ! self::extended_tables_ready() ) {
			return self::get_all_legacy($args);
		}

		$where     = ['1=1'];
		$params    = [];
		$joins     = [
			"LEFT JOIN {$cases_t} cc ON cc.camp_id = c.id",
			"LEFT JOIN {$org_t} co ON co.camp_id = c.id",
			self::readiness_join($check_t),
		];

		if ( isset($args['status']) && $args['status'] !== '' ) {
			$where[]  = 'c.status = %s';
			$params[] = sanitize_key($args['status']);
		}

		if ( ! empty($args['process_stage']) ) {
			$where[]  = 'cc.process_stage = %s';
			$params[] = sanitize_key($args['process_stage']);
		}

		if ( ! empty($args['search']) ) {
			$like     = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
			$where[]  = '(c.name LIKE %s OR co.organization_name LIKE %s OR co.contact_person LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty($args['needs_attention']) ) {
			$where[] = 'COALESCE(cc.needs_attention, 0) = 1';
		}

		if ( ! empty($args['readiness']) ) {
			$readiness = sanitize_key($args['readiness']);
			if ( $readiness === 'ready' ) {
				$where[] = 'COALESCE(readiness.readiness_total, 0) > 0 AND COALESCE(readiness.readiness_done, 0) = COALESCE(readiness.readiness_total, 0)';
			} elseif ( $readiness === 'overdue' ) {
				$where[] = 'COALESCE(readiness.readiness_overdue, 0) > 0';
			} elseif ( $readiness === 'in_progress' ) {
				$where[] = 'COALESCE(readiness.readiness_done, 0) > 0 AND COALESCE(readiness.readiness_done, 0) < COALESCE(readiness.readiness_total, 0)';
			} elseif ( $readiness === 'not_started' ) {
				$where[] = 'COALESCE(readiness.readiness_done, 0) = 0';
			}
		}

		$order  = 'ORDER BY COALESCE(cc.needs_attention, 0) DESC, c.start_date DESC';
		$limit  = '';

		if ( ! empty($args['per_page']) ) {
			$page    = max(1, (int) ($args['page'] ?? 1));
			$offset  = ($page - 1) * (int) $args['per_page'];
			$limit   = $wpdb->prepare('LIMIT %d OFFSET %d', (int) $args['per_page'], $offset);
		}

		$sql = "SELECT
				c.*,
				COALESCE(cc.process_stage, 'inquiry') AS process_stage,
				COALESCE(cc.needs_attention, 0) AS needs_attention,
				COALESCE(cc.risk_level, 'low') AS risk_level,
				cc.next_action_due_date,
				co.organization_name,
				co.contact_person,
				COALESCE(readiness.readiness_total, 0) AS readiness_total,
				COALESCE(readiness.readiness_done, 0) AS readiness_done,
				COALESCE(readiness.readiness_overdue, 0) AS readiness_overdue
			FROM {$table} c
			" . implode(' ', $joins) . '
			WHERE ' . implode(' AND ', $where) . " {$order} {$limit}";

		if ( $params ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results($wpdb->prepare($sql, ...$params)) ?: [];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results($sql) ?: [];
	}

	public static function count(array $args = []): int {
		global $wpdb;
		$table   = Schema::table('camps');
		$cases_t = Schema::table('camp_cases');
		$org_t   = Schema::table('camp_organizers');
		$check_t = Schema::table('camp_checklist_items');

		if ( ! self::extended_tables_ready() ) {
			return self::count_legacy($args);
		}

		$where   = ['1=1'];
		$params  = [];
		$readiness_join = '';

		if ( isset($args['status']) && $args['status'] !== '' ) {
			$where[]  = 'c.status = %s';
			$params[] = sanitize_key($args['status']);
		}

		if ( ! empty($args['process_stage']) ) {
			$where[]  = 'cc.process_stage = %s';
			$params[] = sanitize_key($args['process_stage']);
		}

		if ( ! empty($args['search']) ) {
			$like     = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
			$where[]  = '(c.name LIKE %s OR co.organization_name LIKE %s OR co.contact_person LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty($args['needs_attention']) ) {
			$where[] = 'COALESCE(cc.needs_attention, 0) = 1';
		}

		if ( ! empty($args['readiness']) ) {
			$readiness_join = self::readiness_join($check_t);
			$readiness = sanitize_key($args['readiness']);
			if ( $readiness === 'ready' ) {
				$where[] = 'COALESCE(readiness.readiness_total, 0) > 0 AND COALESCE(readiness.readiness_done, 0) = COALESCE(readiness.readiness_total, 0)';
			} elseif ( $readiness === 'overdue' ) {
				$where[] = 'COALESCE(readiness.readiness_overdue, 0) > 0';
			} elseif ( $readiness === 'in_progress' ) {
				$where[] = 'COALESCE(readiness.readiness_done, 0) > 0 AND COALESCE(readiness.readiness_done, 0) < COALESCE(readiness.readiness_total, 0)';
			} elseif ( $readiness === 'not_started' ) {
				$where[] = 'COALESCE(readiness.readiness_done, 0) = 0';
			}
		}

		$sql = "SELECT COUNT(*) FROM {$table} c
			LEFT JOIN {$cases_t} cc ON cc.camp_id = c.id
			LEFT JOIN {$org_t} co ON co.camp_id = c.id
			{$readiness_join}
			WHERE " . implode(' AND ', $where);

		if ( $params ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var($sql);
	}

	public static function get(int $id): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM `" . Schema::table('camps') . "` WHERE id = %d LIMIT 1", $id)
		) ?: null;
	}

	public static function insert(array $data): int|false {
		global $wpdb;
		$result = $wpdb->insert(
			Schema::table('camps'),
			self::sanitize_fields($data),
			self::formats()
		);
		return $result ? (int) $wpdb->insert_id : false;
	}

	public static function update(int $id, array $data): bool {
		global $wpdb;
		$result = $wpdb->update(
			Schema::table('camps'),
			self::sanitize_fields($data),
			['id' => $id],
			self::formats(),
			['%d']
		);
		return $result !== false;
	}

	public static function delete(int $id): bool {
		global $wpdb;
		return (bool) $wpdb->delete(Schema::table('camps'), ['id' => $id], ['%d']);
	}

	/**
	 * Dashboard stats: sum of counts from the latest daily_counts per active camp.
	 *
	 * @return array{camps:int, participants:int, staff:int, workers:int}
	 */
	public static function active_summary(): array {
		global $wpdb;
		$camps_t  = Schema::table('camps');
		$counts_t = Schema::table('daily_counts');

		$row = $wpdb->get_row(
			"SELECT
				COUNT(DISTINCT c.id) AS camps,
				COALESCE(SUM(dc.participants), 0) AS participants,
				COALESCE(SUM(dc.staff),        0) AS staff,
				COALESCE(SUM(dc.workers),      0) AS workers
			 FROM `$camps_t` c
			 LEFT JOIN `$counts_t` dc
				ON dc.camp_id = c.id
				AND dc.count_date = (
					SELECT MAX(count_date) FROM `$counts_t` WHERE camp_id = c.id
				)
			 WHERE c.status = 'active'"
		);

		return [
			'camps'        => (int) ($row->camps        ?? 0),
			'participants' => (int) ($row->participants  ?? 0),
			'staff'        => (int) ($row->staff         ?? 0),
			'workers'      => (int) ($row->workers       ?? 0),
		];
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private static function sanitize_fields(array $data): array {
		$clean = [];
		if ( array_key_exists('name',       $data) ) $clean['name']       = sanitize_text_field($data['name']);
		if ( array_key_exists('start_date', $data) ) $clean['start_date'] = sanitize_text_field($data['start_date']);
		if ( array_key_exists('end_date',   $data) ) $clean['end_date']   = sanitize_text_field($data['end_date']);
		if ( array_key_exists('status',     $data) ) $clean['status']     = sanitize_key($data['status']);
		return $clean;
	}

	private static function formats(): array {
		return ['%s', '%s', '%s', '%s'];
	}

	private static function extended_tables_ready(): bool {
		return CampCaseRepository::tables_ready();
	}

	private static function get_all_legacy(array $args = []): array {
		global $wpdb;

		$table  = Schema::table('camps');
		$where  = '1=1';
		$params = [];

		if ( isset($args['status']) && $args['status'] !== '' ) {
			$where   .= ' AND status = %s';
			$params[] = sanitize_key($args['status']);
		}

		$order = 'ORDER BY start_date DESC';
		$limit = '';

		if ( ! empty($args['per_page']) ) {
			$page   = max(1, (int) ($args['page'] ?? 1));
			$offset = ($page - 1) * (int) $args['per_page'];
			$limit  = $wpdb->prepare('LIMIT %d OFFSET %d', (int) $args['per_page'], $offset);
		}

		$sql = "SELECT
				*,
				'inquiry' AS process_stage,
				0 AS needs_attention,
				'low' AS risk_level,
				NULL AS next_action_due_date,
				'' AS organization_name,
				'' AS contact_person,
				0 AS readiness_total,
				0 AS readiness_done,
				0 AS readiness_overdue
			FROM `{$table}` WHERE {$where} {$order} {$limit}";

		if ( $params ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results($wpdb->prepare($sql, ...$params)) ?: [];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results($sql) ?: [];
	}

	private static function count_legacy(array $args = []): int {
		global $wpdb;
		$table  = Schema::table('camps');
		$where  = '1=1';
		$params = [];

		if ( isset($args['status']) && $args['status'] !== '' ) {
			$where   .= ' AND status = %s';
			$params[] = sanitize_key($args['status']);
		}

		$sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where}";

		if ( $params ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var($sql);
	}

	private static function readiness_join(string $check_table): string {
		return "LEFT JOIN (
			SELECT
				camp_id,
				COUNT(*) AS readiness_total,
				COALESCE(SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END), 0) AS readiness_done,
				COALESCE(SUM(CASE WHEN status <> 'done' AND due_date IS NOT NULL AND due_date < CURDATE() THEN 1 ELSE 0 END), 0) AS readiness_overdue
			FROM {$check_table}
			GROUP BY camp_id
		) readiness ON readiness.camp_id = c.id";
	}
}
