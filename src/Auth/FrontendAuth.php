<?php

declare(strict_types=1);

namespace BaseMgmt\Auth;

use BaseMgmt\Database\Schema;
use BaseMgmt\Modules\Camps\StaffRepository;
use BaseMgmt\Core\OperationLogger;

defined('ABSPATH') || exit;

/**
 * Handles the frontend authentication flow:
 *   1. List active camps          → for access-screen dropdown
 *   2. List active staff by camp  → for access-screen dropdown
 *   3. Authenticate staff member  → verify code, create session
 */
final class FrontendAuth {

	/**
	 * Returns active camps for the public dropdown.
	 *
	 * @return array<int, array{id:int, name:string}>
	 */
	public static function get_active_camps(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT id, name FROM `" . Schema::table('camps') . "`
			 WHERE status = 'active'
			 ORDER BY name ASC"
		);

		return array_map(
			static fn($r) => ['id' => (int) $r->id, 'name' => $r->name],
			$rows ?: []
		);
	}

	/**
	 * Returns active staff for a given camp (names only – no sensitive data).
	 *
	 * @return array<int, array{id:int, display_name:string}>
	 */
	public static function get_active_staff_for_camp(int $camp_id): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, first_name, last_name
				 FROM `" . Schema::table('staff') . "`
				 WHERE camp_id = %d AND is_active = 1
				 ORDER BY last_name ASC, first_name ASC",
				$camp_id
			)
		);

		return array_map(
			static fn($r) => [
				'id'           => (int) $r->id,
				// SEC-004: imię + pierwsza litera nazwiska – nie ujawniamy pełnych danych osobowych publicznie.
				'display_name' => esc_html( $r->first_name . ' ' . mb_substr( $r->last_name, 0, 1 ) . '.' ),
			],
			$rows ?: []
		);
	}

	/**
	 * Attempt to authenticate.
	 *
	 * Returns ['success' => true, 'token' => '...', 'camp_id' => N]
	 * or      ['success' => false, 'message' => '...', 'locked_until' => N]
	 *
	 * Intentionally keeps error messages neutral (no enumeration).
	 *
	 * @return array<string, mixed>
	 */
	public static function attempt(int $camp_id, int $staff_id, string $security_code): array {
		global $wpdb;

		$generic_error = __('Nieprawidłowe dane logowania.', 'basemgmt');

		// Load the staff record, verifying both camp_id and staff_id simultaneously.
		$staff = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `" . Schema::table('staff') . "`
				 WHERE id = %d AND camp_id = %d LIMIT 1",
				$staff_id,
				$camp_id
			)
		);

		// Unknown staff or mismatched camp → same generic error.
		if ( ! $staff ) {
			// Pause briefly to harden timing attacks.
			wp_hash_password(bin2hex(random_bytes(16)));
			return ['success' => false, 'message' => $generic_error];
		}

		// Active check.
		if ( ! (bool) $staff->is_active ) {
			wp_hash_password(bin2hex(random_bytes(16)));
			return ['success' => false, 'message' => $generic_error];
		}

		// Lockout check.
		if ( RateLimiter::is_locked($staff) ) {
			$remaining = RateLimiter::lockout_remaining($staff);
			return [
				'success'      => false,
				'message'      => __('Konto tymczasowo zablokowane. Spróbuj ponownie za chwilę.', 'basemgmt'),
				'locked_until' => $remaining,
			];
		}

		// Verify security code.
		if ( ! wp_check_password($security_code, $staff->security_code_hash) ) {
			RateLimiter::record_failure((int) $staff->id, $staff);
			OperationLogger::log(
				OperationLogger::ACTION_LOGIN_FAILED,
				'staff',
				(int) $staff->id,
				sprintf('Nieudana próba logowania dla użytkownika %s %s (ID %d)', $staff->first_name, $staff->last_name, $staff->id),
				(int) $staff->id
			);
			return ['success' => false, 'message' => $generic_error];
		}

		// ── Auth OK ──────────────────────────────────────────────────────────
		RateLimiter::clear((int) $staff->id);
		$token = SessionManager::create((int) $staff->id, (int) $staff->camp_id);

		OperationLogger::log(
			OperationLogger::ACTION_LOGIN_SUCCESS,
			'staff',
			(int) $staff->id,
			sprintf('Pomyślne logowanie: %s %s (ID %d)', $staff->first_name, $staff->last_name, $staff->id),
			(int) $staff->id
		);

		return [
			'success'      => true,
			'token'        => $token,
			'staff_id'     => (int) $staff->id,
			'camp_id'      => (int) $staff->camp_id,
			'display_name' => esc_html($staff->first_name . ' ' . $staff->last_name),
		];
	}
}
