<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Camps;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Daily headcount history for a camp.
 * One record per camp per day (UNIQUE KEY on camp_id + count_date).
 * Foundation for the future Meldunki module.
 */
final class DailyCountRepository {

	/** Upsert (insert or update) today's count for a camp. */
	public static function upsert(
		int $camp_id,
		string $count_date,
		int $participants,
		int $staff,
		int $workers,
		?string $notes,
		?int $submitted_by
	): bool {
		global $wpdb;

		$existing = self::get_by_date($camp_id, $count_date);

		if ( $existing ) {
			return $wpdb->update(
				Schema::table('daily_counts'),
				[
					'participants' => $participants,
					'staff'        => $staff,
					'workers'      => $workers,
					'notes'        => $notes,
					'submitted_by' => $submitted_by,
					'updated_at'   => gmdate('Y-m-d H:i:s'),
				],
				['id' => (int) $existing->id],
				['%d', '%d', '%d', '%s', '%d', '%s'],
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
			],
			['%d', '%s', '%d', '%d', '%d', '%s', '%d']
		);
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

	/** Has the camp already submitted a count today? */
	public static function submitted_today(int $camp_id): bool {
		return null !== self::get_by_date($camp_id, gmdate('Y-m-d'));
	}

	/** Full history for a camp (for future Meldunki module). */
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
}
