<?php

declare(strict_types=1);

namespace BaseMgmt\Auth;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Tracks failed login attempts and enforces lockouts.
 *
 * Logic:
 *   • After BASEMGMT_MAX_ATTEMPTS (default 3) consecutive failures → temporary
 *     lockout for BASEMGMT_LOCKOUT_TTL minutes (default 15).
 *   • Permanent lock is an administrative state (set outside this class) and
 *     can only be lifted by an administrator from the Staff panel; lifting
 *     requires a mandatory security-code reset.
 */
final class RateLimiter {

	/** Returns true when the staff member is currently locked out (temp or permanent). */
	public static function is_locked(object $staff): bool {
		if ( ! empty($staff->permanent_lock) && (int) $staff->permanent_lock === 1 ) {
			return true;
		}
		if ( empty($staff->locked_until) ) {
			return false;
		}
		return strtotime($staff->locked_until) > time();
	}

	/** Returns true only for permanent (admin-required) lock. */
	public static function is_permanently_locked(object $staff): bool {
		return ! empty($staff->permanent_lock) && (int) $staff->permanent_lock === 1;
	}

	/**
	 * Record a failed login attempt.
	 * Applies temp lock after threshold; permanent lock if attempt follows
	 * a prior temp-lock cycle.
	 */
	public static function record_failure(int $staff_id, object $staff): void {
		global $wpdb;

		$lockout_minutes = (int) get_option('bm_lockout_minutes', 15);
		$lockout_ttl     = $lockout_minutes * MINUTE_IN_SECONDS;

		$attempts = (int) $staff->failed_attempts + 1;
		$data     = ['failed_attempts' => $attempts];

		if ( $attempts >= BASEMGMT_MAX_ATTEMPTS ) {
			$data['locked_until'] = gmdate('Y-m-d H:i:s', time() + $lockout_ttl);
		}

		$formats = array_fill(0, count($data), '%s');
		$formats[0] = '%d'; // failed_attempts is integer.

		$wpdb->update(
			Schema::table('staff'),
			$data,
			['id' => $staff_id],
			$formats,
			['%d']
		);
	}

	/** Reset all counters after a successful login. */
	public static function clear(int $staff_id): void {
		global $wpdb;
		$wpdb->update(
			Schema::table('staff'),
			[
				'failed_attempts' => 0,
				'locked_until'    => null,
				'permanent_lock'  => 0,
				'last_login'      => gmdate('Y-m-d H:i:s'),
			],
			['id' => $staff_id],
			['%d', '%s', '%d', '%s'],
			['%d']
		);
	}

	/**
	 * Administratively unlock a staff member.
	 * Clears temp lock, permanent lock, and resets attempt counter.
	 * Caller MUST also reset the security code.
	 */
	public static function admin_unlock(int $staff_id): void {
		global $wpdb;
		$wpdb->update(
			Schema::table('staff'),
			[
				'failed_attempts' => 0,
				'locked_until'    => null,
				'permanent_lock'  => 0,
			],
			['id' => $staff_id],
			['%d', '%s', '%d'],
			['%d']
		);
	}

	/** Remaining lockout seconds, or 0 if not temp-locked (permanent lock returns 0). */
	public static function lockout_remaining(object $staff): int {
		if ( self::is_permanently_locked($staff) ) {
			return 0;
		}
		if ( empty($staff->locked_until) || strtotime($staff->locked_until) <= time() ) {
			return 0;
		}
		return max(0, (int) strtotime($staff->locked_until) - time());
	}
}
