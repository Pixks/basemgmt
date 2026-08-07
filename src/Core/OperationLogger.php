<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Centralized operation logging for the plugin.
 * Logs are stored in bm_operation_logs and viewable from the admin panel.
 */
final class OperationLogger {

	// ── Action constants ──────────────────────────────────────────────────────

	public const ACTION_LOGIN_SUCCESS  = 'login_success';
	public const ACTION_LOGIN_FAILED   = 'login_failed';
	public const ACTION_LOGIN_LOCKED   = 'login_locked';
	public const ACTION_LOGOUT         = 'logout';
	public const ACTION_UNLOCK_STAFF   = 'unlock_staff';

	public const ACTION_CAMP_CREATED   = 'camp_created';
	public const ACTION_CAMP_UPDATED   = 'camp_updated';
	public const ACTION_CAMP_DELETED   = 'camp_deleted';

	public const ACTION_STAFF_CREATED  = 'staff_created';
	public const ACTION_STAFF_UPDATED  = 'staff_updated';
	public const ACTION_STAFF_DELETED  = 'staff_deleted';

	public const ACTION_REPORT_SAVED   = 'report_saved';

	public const ACTION_PLAN_CREATED   = 'plan_created';
	public const ACTION_PLAN_UPDATED   = 'plan_updated';
	public const ACTION_PLAN_DELETED   = 'plan_deleted';
	public const ACTION_PLAN_ITEM_SAVED   = 'plan_item_saved';
	public const ACTION_PLAN_ITEM_DELETED = 'plan_item_deleted';

	public const ACTION_MEAL_CREATED   = 'meal_created';
	public const ACTION_MEAL_UPDATED   = 'meal_updated';
	public const ACTION_MEAL_DELETED   = 'meal_deleted';
	public const ACTION_MEAL_ITEM_SAVED   = 'meal_item_saved';
	public const ACTION_MEAL_ITEM_DELETED = 'meal_item_deleted';

	public const ACTION_THREAD_CREATED = 'thread_created';
	public const ACTION_MESSAGE_SENT   = 'message_sent';

	public const ACTION_FORM_SAVED     = 'form_saved';
	public const ACTION_SUBMISSION_UPDATED = 'submission_updated';

	public const ACTION_SETTINGS_SAVED = 'settings_saved';

	public const ACTION_TEMPLATE_CREATED = 'template_created';
	public const ACTION_TEMPLATE_UPDATED = 'template_updated';
	public const ACTION_TEMPLATE_DELETED = 'template_deleted';

	// ── Log method ────────────────────────────────────────────────────────────

	/**
	 * @param string       $action      One of the ACTION_* constants.
	 * @param string       $object_type E.g. 'camp', 'staff', 'plan', 'meal'.
	 * @param int|null     $object_id   Primary key of the affected record.
	 * @param string|array $details     Human-readable note or array serialized to JSON.
	 * @param int|null     $staff_id    Front-end staff ID (if applicable).
	 */
	public static function log(
		string       $action,
		string       $object_type = '',
		?int         $object_id   = null,
		string|array $details     = '',
		?int         $staff_id    = null
	): void {
		global $wpdb;

		if ( is_array($details) ) {
			$details = wp_json_encode($details);
		}

		$wpdb->insert(
			Schema::table('operation_logs'),
			[
				'user_id'     => (int) get_current_user_id(),
				'staff_id'    => $staff_id,
				'action'      => $action,
				'object_type' => $object_type,
				'object_id'   => $object_id,
				'details'     => (string) $details,
				'ip_address'  => self::get_ip(),
			],
			['%d', '%s', '%s', '%s', '%d', '%s', '%s']
		);
	}

	// ── Query methods ─────────────────────────────────────────────────────────

	public static function get_all(array $filters = [], int $per_page = 50, int $page = 1): array {
		global $wpdb;
		$t     = Schema::table('operation_logs');
		$where = ['1=1'];
		$vals  = [];

		if ( ! empty($filters['action']) ) {
			$where[] = 'action = %s';
			$vals[]  = $filters['action'];
		}
		if ( ! empty($filters['user_id']) ) {
			$where[] = 'user_id = %d';
			$vals[]  = (int) $filters['user_id'];
		}
		if ( ! empty($filters['date_from']) ) {
			$where[] = 'created_at >= %s';
			$vals[]  = $filters['date_from'] . ' 00:00:00';
		}
		if ( ! empty($filters['date_to']) ) {
			$where[] = 'created_at <= %s';
			$vals[]  = $filters['date_to'] . ' 23:59:59';
		}

		$offset = ($page - 1) * $per_page;
		$sql    = "SELECT * FROM {$t} WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT %d OFFSET %d";
		$vals[] = $per_page;
		$vals[] = $offset;

		return $wpdb->get_results($wpdb->prepare($sql, ...$vals)) ?: [];
	}

	public static function count(array $filters = []): int {
		global $wpdb;
		$t     = Schema::table('operation_logs');
		$where = ['1=1'];
		$vals  = [];

		if ( ! empty($filters['action']) ) {
			$where[] = 'action = %s';
			$vals[]  = $filters['action'];
		}
		if ( ! empty($filters['user_id']) ) {
			$where[] = 'user_id = %d';
			$vals[]  = (int) $filters['user_id'];
		}
		if ( ! empty($filters['date_from']) ) {
			$where[] = 'created_at >= %s';
			$vals[]  = $filters['date_from'] . ' 00:00:00';
		}
		if ( ! empty($filters['date_to']) ) {
			$where[] = 'created_at <= %s';
			$vals[]  = $filters['date_to'] . ' 23:59:59';
		}

		$sql = "SELECT COUNT(*) FROM {$t} WHERE " . implode(' AND ', $where);
		if ( ! empty($vals) ) {
			return (int) $wpdb->get_var($wpdb->prepare($sql, ...$vals));
		}
		return (int) $wpdb->get_var($sql);
	}

	public static function delete_older_than_days(int $days): int {
		global $wpdb;
		$t    = Schema::table('operation_logs');
		$date = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
		return (int) $wpdb->query(
			$wpdb->prepare("DELETE FROM {$t} WHERE created_at < %s", $date)
		);
	}

	/** @return string[] Unique action types present in the log. */
	public static function get_action_types(): array {
		global $wpdb;
		$t = Schema::table('operation_logs');
		return $wpdb->get_col("SELECT DISTINCT action FROM {$t} ORDER BY action ASC") ?: [];
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	private static function get_ip(): string {
		foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
			if ( ! empty($_SERVER[$key]) ) {
				$ip = sanitize_text_field(wp_unslash($_SERVER[$key]));
				// Take only first IP if comma-list.
				return trim(explode(',', $ip)[0]);
			}
		}
		return '';
	}
}
