<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Announcements;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access layer for announcements.
 *
 * Status lifecycle:
 *   draft    → active (admin publishes)
 *   pending  → active (admin approves camp submission)
 *   active   → expired (cron, when valid_until passes)
 *   *        → archived (manual admin action)
 */
final class AnnouncementRepository {

	public static function get_all(array $args = []): array {
		global $wpdb;
		$table  = Schema::table('announcements');
		$where  = '1=1';
		$params = [];

		if ( ! empty($args['status']) ) {
			$where   .= ' AND status = %s';
			$params[] = sanitize_key($args['status']);
		}
		if ( isset($args['is_global']) && $args['is_global'] !== '' ) {
			$where   .= ' AND is_global = %d';
			$params[] = (int) $args['is_global'];
		}

		$order = 'ORDER BY is_urgent DESC, priority DESC, valid_from DESC';
		$limit = '';

		if ( ! empty($args['per_page']) ) {
			$page   = max(1, (int) ($args['page'] ?? 1));
			$offset = ($page - 1) * (int) $args['per_page'];
			$limit  = $wpdb->prepare('LIMIT %d OFFSET %d', (int) $args['per_page'], $offset);
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
		$table  = Schema::table('announcements');
		$where  = '1=1';
		$params = [];

		if ( ! empty($args['status']) ) {
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
			$wpdb->prepare(
				"SELECT * FROM `" . Schema::table('announcements') . "` WHERE id = %d LIMIT 1",
				$id
			)
		) ?: null;
	}

	/**
	 * Returns announcements visible to a specific camp:
	 *   - global active announcements, OR
	 *   - active announcements targeted at this camp.
	 *
	 * @return object[]
	 */
	public static function get_for_camp(int $camp_id, string $status = 'active'): array {
		global $wpdb;
		$ann_t  = Schema::table('announcements');
		$rel_t  = Schema::table('announcement_camps');

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.* FROM `$ann_t` a
				 WHERE a.status = %s
				   AND (
				     a.is_global = 1
				     OR EXISTS (
				       SELECT 1 FROM `$rel_t` ac
				       WHERE ac.announcement_id = a.id AND ac.camp_id = %d
				     )
				   )
				 ORDER BY a.is_urgent DESC, a.priority DESC, a.valid_from DESC",
				$status,
				$camp_id
			)
		) ?: [];
	}

	/**
	 * Announcements submitted by a specific camp (own + pending).
	 *
	 * @return object[]
	 */
	public static function get_by_camp(int $camp_id): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `" . Schema::table('announcements') . "`
				 WHERE submitted_camp_id = %d
				 ORDER BY created_at DESC",
				$camp_id
			)
		) ?: [];
	}

	public static function insert(array $data): int|false {
		global $wpdb;
		$clean  = self::sanitize_fields($data);
		$result = $wpdb->insert(Schema::table('announcements'), $clean);

		if ( ! $result ) {
			return false;
		}

		$id = (int) $wpdb->insert_id;

		// Attach camp targets if not global.
		if ( empty($clean['is_global']) && ! empty($data['camp_ids']) ) {
			self::set_camp_targets($id, (array) $data['camp_ids']);
		}

		return $id;
	}

	public static function update(int $id, array $data): bool {
		global $wpdb;
		$clean  = self::sanitize_fields($data);
		$result = $wpdb->update(Schema::table('announcements'), $clean, ['id' => $id]);

		if ( isset($data['camp_ids']) && ! empty($clean['is_global']) === false ) {
			self::set_camp_targets($id, (array) $data['camp_ids']);
		}

		return $result !== false;
	}

	public static function approve(int $id, int $wp_user_id): bool {
		global $wpdb;
		return $wpdb->update(
			Schema::table('announcements'),
			[
				'status'              => 'active',
				'approved_by_user_id' => $wp_user_id,
				'approved_at'         => gmdate('Y-m-d H:i:s'),
			],
			['id' => $id],
			['%s', '%d', '%s'],
			['%d']
		) !== false;
	}

	public static function delete(int $id): bool {
		global $wpdb;
		$wpdb->delete(Schema::table('announcement_camps'), ['announcement_id' => $id], ['%d']);
		return (bool) $wpdb->delete(Schema::table('announcements'), ['id' => $id], ['%d']);
	}

	/** Called by cron to expire announcements past their valid_until date. */
	public static function expire_overdue(): int {
		global $wpdb;
		return (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE `" . Schema::table('announcements') . "`
				 SET status = 'expired'
				 WHERE status = 'active' AND valid_until < %s",
				gmdate('Y-m-d H:i:s')
			)
		);
	}

	public static function count_pending(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM `" . Schema::table('announcements') . "` WHERE status = 'pending'"
		);
	}

	// ── Camp targets ──────────────────────────────────────────────────────────

	public static function get_camp_targets(int $announcement_id): array {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT camp_id FROM `" . Schema::table('announcement_camps') . "` WHERE announcement_id = %d",
				$announcement_id
			)
		) ?: [];
	}

	private static function set_camp_targets(int $announcement_id, array $camp_ids): void {
		global $wpdb;
		$rel_t = Schema::table('announcement_camps');
		$wpdb->delete($rel_t, ['announcement_id' => $announcement_id], ['%d']);

		foreach ( $camp_ids as $cid ) {
			$wpdb->insert($rel_t, ['announcement_id' => $announcement_id, 'camp_id' => (int) $cid], ['%d', '%d']);
		}
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private static function sanitize_fields(array $data): array {
		$allowed_statuses = ['active', 'pending', 'expired', 'archived', 'draft'];
		$clean = [];

		if ( array_key_exists('title',               $data) ) $clean['title']               = sanitize_text_field($data['title']);
		if ( array_key_exists('content',             $data) ) $clean['content']             = wp_kses_post($data['content']);
		if ( array_key_exists('status',              $data) ) $clean['status']              = in_array($data['status'], $allowed_statuses, true) ? $data['status'] : 'draft';
		if ( array_key_exists('is_urgent',           $data) ) $clean['is_urgent']           = (int) (bool) $data['is_urgent'];
		if ( array_key_exists('priority',            $data) ) $clean['priority']            = max(0, min(10, (int) $data['priority']));
		if ( array_key_exists('valid_from',          $data) ) $clean['valid_from']          = sanitize_text_field($data['valid_from']);
		if ( array_key_exists('valid_until',         $data) ) $clean['valid_until']         = sanitize_text_field($data['valid_until']);
		if ( array_key_exists('is_global',           $data) ) $clean['is_global']           = (int) (bool) $data['is_global'];
		if ( array_key_exists('attachment_url',      $data) ) $clean['attachment_url']      = esc_url_raw($data['attachment_url'] ?? '');
		if ( array_key_exists('submitted_camp_id',   $data) ) $clean['submitted_camp_id']   = (int) $data['submitted_camp_id'] ?: null;
		if ( array_key_exists('submitted_staff_id',  $data) ) $clean['submitted_staff_id']  = (int) $data['submitted_staff_id'] ?: null;

		return $clean;
	}
}
