<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Camps;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Daily headcount / report history for a camp.
 * One record per camp per day (UNIQUE KEY on camp_id + count_date).
 * Status workflow: none → draft → submitted.
 */
final class DailyCountRepository {

	public const STATUS_NONE      = 'none';
	public const STATUS_DRAFT     = 'draft';
	public const STATUS_SUBMITTED = 'submitted';

	/** Upsert today's count – does NOT submit (keeps/sets 'draft' status). */
	public static function upsert(
		int $camp_id,
		string $count_date,
		int $participants,
		int $staff,
		int $workers,
		?string $notes,
		?int $submitted_by,
		string $status = self::STATUS_DRAFT
	): bool {
		global $wpdb;

		$existing = self::get_by_date($camp_id, $count_date);

		// Never downgrade a submitted report back to draft.
		if ( $existing && $existing->status === self::STATUS_SUBMITTED ) {
			$status = self::STATUS_SUBMITTED;
		}

		if ( $existing ) {
			return $wpdb->update(
				Schema::table('daily_counts'),
				[
					'participants' => $participants,
					'staff'        => $staff,
					'workers'      => $workers,
					'notes'        => $notes,
					'submitted_by' => $submitted_by,
					'status'       => $status,
					'updated_at'   => gmdate('Y-m-d H:i:s'),
				],
				['id' => (int) $existing->id],
				['%d', '%d', '%d', '%s', '%d', '%s', '%s'],
				['%d']
			) !== false;
		}

		return (bool) $wpdb->insert(
			Schema::table('daily_counts'),
			[
				'camp_id'      => $camp_id,
				'count_date'   => $count_date,
				'participants' => $participants,
				'staff'        => $staff,
				'workers'      => $workers,
				'notes'        => $notes,
				'submitted_by' => $submitted_by,
				'status'       => $status,
			],
			['%d', '%s', '%d', '%d', '%d', '%s', '%d', '%s']
		);
	}

	/** Mark the report as submitted (final). Returns false if already submitted. */
	public static function submit(int $camp_id, string $count_date, int $staff_id): bool {
		global $wpdb;

		$existing = self::get_by_date($camp_id, $count_date);
		if ( ! $existing ) {
			return false;
		}
		if ( $existing->status === self::STATUS_SUBMITTED ) {
			return false;
		}

		return $wpdb->update(
			Schema::table('daily_counts'),
			[
				'status'       => self::STATUS_SUBMITTED,
				'submitted_at' => gmdate('Y-m-d H:i:s'),
				'submitted_by' => $staff_id,
				'updated_at'   => gmdate('Y-m-d H:i:s'),
			],
			['id' => (int) $existing->id],
			['%s', '%s', '%d', '%s'],
			['%d']
		) !== false;
	}

	/** Latest headcount entry for the given camp. */
	public static function get_latest(int $camp_id): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `" . Schema::table('daily_counts') . "`
				 WHERE camp_id = %d
				 ORDER BY count_date DESC
				 LIMIT 1",
				$camp_id
			)
		) ?: null;
	}

	/** Entry for a specific date. */
	public static function get_by_date(int $camp_id, string $date): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `" . Schema::table('daily_counts') . "`
				 WHERE camp_id = %d AND count_date = %s LIMIT 1",
				$camp_id,
				$date
			)
		) ?: null;
	}

	/** Has the camp submitted (status = submitted) a count today? */
	public static function is_submitted_today(int $camp_id): bool {
		$row = self::get_by_date($camp_id, gmdate('Y-m-d'));
		return $row && $row->status === self::STATUS_SUBMITTED;
	}

	/** Has any record (draft or submitted) been saved today? */
	public static function submitted_today(int $camp_id): bool {
		return null !== self::get_by_date($camp_id, gmdate('Y-m-d'));
	}

	/** Full history for a camp, ordered by date DESC. */
	public static function get_history(int $camp_id, int $limit = 30): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `" . Schema::table('daily_counts') . "`
				 WHERE camp_id = %d
				 ORDER BY count_date DESC
				 LIMIT %d",
				$camp_id,
				$limit
			)
		) ?: [];
	}

	/** Get all reports for a specific date (admin view). */
	public static function get_all_for_date(string $date): array {
		global $wpdb;
		$t_counts = Schema::table('daily_counts');
		$t_camps  = Schema::table('camps');

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT dc.*, c.name AS camp_name
				 FROM `{$t_counts}` dc
				 JOIN `{$t_camps}` c ON c.id = dc.camp_id
				 WHERE dc.count_date = %s
				 ORDER BY c.name ASC",
				$date
			)
		) ?: [];
	}

	/** Count of reports by status for a specific date. */
	public static function daily_status_counts(string $date): object {
		global $wpdb;
		$t = Schema::table('daily_counts');

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT status, COUNT(*) AS cnt FROM `{$t}` WHERE count_date = %s GROUP BY status",
				$date
			)
		) ?: [];

		$result = (object) ['none' => 0, 'draft' => 0, 'submitted' => 0];
		foreach ( $rows as $row ) {
			if ( property_exists($result, $row->status) ) {
				$result->{$row->status} = (int) $row->cnt;
			}
		}
		return $result;
	}

	/** Aggregate totals for submitted reports on a specific date. */
	public static function daily_totals(string $date): object {
		global $wpdb;
		$t = Schema::table('daily_counts');
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COALESCE(SUM(participants), 0) AS total_participants,
					COALESCE(SUM(staff), 0)        AS total_staff,
					COALESCE(SUM(workers), 0)      AS total_workers
				 FROM `{$t}` WHERE count_date = %s AND status = 'submitted'",
				$date
			)
		) ?: (object) ['total_participants' => 0, 'total_staff' => 0, 'total_workers' => 0];
	}

	/** Active camps that have NOT submitted a report for the given date. */
	public static function get_missing_camps_for_date(string $date): array {
		global $wpdb;
		$t_counts = Schema::table('daily_counts');
		$t_camps  = Schema::table('camps');

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.id, c.name FROM `{$t_camps}` c
				 WHERE c.status = 'active'
				   AND c.id NOT IN (
				       SELECT camp_id FROM `{$t_counts}`
				       WHERE count_date = %s AND status = 'submitted'
				   )
				 ORDER BY c.name ASC",
				$date
			)
		) ?: [];
	}

	/** Get reports with filters for admin listing. */
	public static function get_admin_list(
		string $date = '',
		int $camp_id = 0,
		string $status = '',
		int $limit = 50,
		int $offset = 0
	): array {
		global $wpdb;
		$t_counts = Schema::table('daily_counts');
		$t_camps  = Schema::table('camps');
		$t_staff  = Schema::table('staff');

		$where  = [];
		$params = [];

		if ( $date ) {
			$where[]  = 'dc.count_date = %s';
			$params[] = $date;
		}
		if ( $camp_id > 0 ) {
			$where[]  = 'dc.camp_id = %d';
			$params[] = $camp_id;
		}
		if ( $status ) {
			$where[]  = 'dc.status = %s';
			$params[] = $status;
		}

		$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
		$params[]  = $limit;
		$params[]  = $offset;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT dc.*, c.name AS camp_name,
				        CONCAT(s.first_name, ' ', s.last_name) AS submitted_by_name
				 FROM `{$t_counts}` dc
				 JOIN `{$t_camps}` c ON c.id = dc.camp_id
				 LEFT JOIN `{$t_staff}` s ON s.id = dc.submitted_by
				 {$where_sql}
				 ORDER BY dc.count_date DESC, c.name ASC
				 LIMIT %d OFFSET %d",
				...$params
			)
		) ?: [];
	}
}
