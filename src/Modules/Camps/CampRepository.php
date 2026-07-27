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

		$table  = Schema::table('camps');
		$where  = '1=1';
		$params = [];

		if ( isset($args['status']) && $args['status'] !== '' ) {
			$where   .= ' AND status = %s';
			$params[] = sanitize_key($args['status']);
		}

		$order  = 'ORDER BY start_date DESC';
		$limit  = '';

		if ( ! empty($args['per_page']) ) {
			$page    = max(1, (int) ($args['page'] ?? 1));
			$offset  = ($page - 1) * (int) $args['per_page'];
			$limit   = $wpdb->prepare('LIMIT %d OFFSET %d', (int) $args['per_page'], $offset);
		}

		$sql = "SELECT * FROM `$table` WHERE $where $order $limit";

		if ( $params ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results($wpdb->prepare($sql, ...$params)) ?: [];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results($sql) ?: [];
	}

	public static function count(array $args = []): int {
		global $wpdb;
		$table  = Schema::table('camps');
		$where  = '1=1';
		$params = [];

		if ( isset($args['status']) && $args['status'] !== '' ) {
			$where   .= ' AND status = %s';
			$params[] = sanitize_key($args['status']);
		}

		$sql = "SELECT COUNT(*) FROM `$table` WHERE $where";

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
}
