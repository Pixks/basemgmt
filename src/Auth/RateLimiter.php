<?php

declare(strict_types=1);

namespace BaseMgmt\Auth;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Tracks failed login attempts and enforces lockouts.
 * Data stored directly in bm_staff table (failed_attempts, locked_until).
 */
final class RateLimiter {

	/** Returns true when the staff member is currently locked out. */
	public static function is_locked(object $staff): bool {
		if ( empty($staff->locked_until) ) {
			return false;
		}
		return strtotime($staff->locked_until) > time();
	}

	/** Record a failed attempt; lock if threshold exceeded. */
	public static function record_failure(int $staff_id, object $staff): void {
		global $wpdb;

		$attempts = (int) $staff->failed_attempts + 1;
		$data     = ['failed_attempts' => $attempts];

		if ( $attempts >= BASEMGMT_MAX_ATTEMPTS ) {
			$data['locked_until'] = gmdate('Y-m-d H:i:s', time() + BASEMGMT_LOCKOUT_TTL);
		}

		$wpdb->update(
			Schema::table('staff'),
			$data,
			['id' => $staff_id],
			array_fill(0, count($data), '%s'),
			['%d']
		);
	}

	/** Reset counters after a successful login. */
	public static function clear(int $staff_id): void {
		global $wpdb;
		$wpdb->update(
			Schema::table('staff'),
			[
				'failed_attempts' => 0,
				'locked_until'    => null,
				'last_login'      => gmdate('Y-m-d H:i:s'),
			],
			['id' => $staff_id],
			['%d', '%s', '%s'],
			['%d']
		);
	}

	/** Remaining lockout seconds, or 0 if not locked. */
	public static function lockout_remaining(object $staff): int {
		if ( ! self::is_locked($staff) ) {
			return 0;
		}
		return max(0, (int) strtotime($staff->locked_until) - time());
	}
}
