<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Camps;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access layer for camp staff (kadra).
 * Staff members are NOT WordPress users.
 */
final class StaffRepository {

	public static function get_all(array $args = []): array {
		global $wpdb;
		$table  = Schema::table('staff');
		$where  = '1=1';
		$params = [];

		if ( ! empty($args['camp_id']) ) {
			$where   .= ' AND camp_id = %d';
			$params[] = (int) $args['camp_id'];
		}
		if ( isset($args['is_active']) && $args['is_active'] !== '' ) {
			$where   .= ' AND is_active = %d';
			$params[] = (int) $args['is_active'];
		}

		$order = 'ORDER BY last_name ASC, first_name ASC';
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
		$table  = Schema::table('staff');
		$where  = '1=1';
		$params = [];

		if ( ! empty($args['camp_id']) ) {
			$where   .= ' AND camp_id = %d';
			$params[] = (int) $args['camp_id'];
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
			$wpdb->prepare("SELECT * FROM `" . Schema::table('staff') . "` WHERE id = %d LIMIT 1", $id)
		) ?: null;
	}

	/** Returns staff belonging to a specific camp; used for access screen. */
	public static function get_for_camp(int $camp_id, bool $active_only = true): array {
		global $wpdb;
		$active = $active_only ? 'AND is_active = 1' : '';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, first_name, last_name, role_in_camp
				 FROM `" . Schema::table('staff') . "`
				 WHERE camp_id = %d $active
				 ORDER BY last_name ASC, first_name ASC",
				$camp_id
			)
		) ?: [];
	}

	public static function insert(array $data): int|false {
		global $wpdb;

		$clean = self::sanitize_fields($data);

		// Hash the security code if provided.
		if ( ! empty($data['security_code']) ) {
			$clean['security_code_hash'] = wp_hash_password($data['security_code']);
		}

		$result = $wpdb->insert(Schema::table('staff'), $clean);
		return $result ? (int) $wpdb->insert_id : false;
	}

	public static function update(int $id, array $data): bool {
		global $wpdb;

		$clean = self::sanitize_fields($data);

		if ( ! empty($data['security_code']) ) {
			$clean['security_code_hash'] = wp_hash_password($data['security_code']);
		}

		$result = $wpdb->update(Schema::table('staff'), $clean, ['id' => $id]);
		return $result !== false;
	}

	/** Set (reset) a security code for the given staff member. */
	public static function set_security_code(int $staff_id, string $raw_code): bool {
		global $wpdb;
		return $wpdb->update(
			Schema::table('staff'),
			['security_code_hash' => wp_hash_password($raw_code)],
			['id' => $staff_id],
			['%s'],
			['%d']
		) !== false;
	}

	public static function toggle_active(int $id): bool {
		global $wpdb;
		$staff = self::get($id);
		if ( ! $staff ) {
			return false;
		}
		return $wpdb->update(
			Schema::table('staff'),
			['is_active' => (int) $staff->is_active ? 0 : 1],
			['id' => $id],
			['%d'],
			['%d']
		) !== false;
	}

	public static function delete(int $id): bool {
		global $wpdb;
		return (bool) $wpdb->delete(Schema::table('staff'), ['id' => $id], ['%d']);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private static function sanitize_fields(array $data): array {
		$clean = [];
		if ( array_key_exists('camp_id',      $data) ) $clean['camp_id']      = (int) $data['camp_id'];
		if ( array_key_exists('first_name',   $data) ) $clean['first_name']   = sanitize_text_field($data['first_name']);
		if ( array_key_exists('last_name',    $data) ) $clean['last_name']    = sanitize_text_field($data['last_name']);
		if ( array_key_exists('email',        $data) ) $clean['email']        = sanitize_email($data['email']);
		if ( array_key_exists('phone',        $data) ) $clean['phone']        = sanitize_text_field($data['phone']);
		if ( array_key_exists('role_in_camp', $data) ) $clean['role_in_camp'] = sanitize_text_field($data['role_in_camp']);
		if ( array_key_exists('is_active',    $data) ) $clean['is_active']    = (int) (bool) $data['is_active'];
		return $clean;
	}
}
