<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Reservations;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access for Rezerwacje (Reservations) module.
 *
 * Table: bm_resource_reservations
 *
 * Conflict protection:
 *   create_with_conflict_check() wraps INSERT inside a MySQL transaction
 *   and uses SELECT … FOR UPDATE to lock matching rows. This prevents
 *   double-booking even under concurrent requests (requires InnoDB engine).
 *
 * Blocking policy: both 'pending' AND 'approved' reservations block a slot.
 */
final class ReservationRepository {

	public const STATUS_PENDING   = 'pending';
	public const STATUS_APPROVED  = 'approved';
	public const STATUS_REJECTED  = 'rejected';
	public const STATUS_CANCELLED = 'cancelled';
	public const STATUS_EXPIRED   = 'expired';

	/** Statuses that block a time slot. */
	public const BLOCKING_STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED];

	public const STATUSES = [
		self::STATUS_PENDING   => 'Oczekująca',
		self::STATUS_APPROVED  => 'Zatwierdzona',
		self::STATUS_REJECTED  => 'Odrzucona',
		self::STATUS_CANCELLED => 'Anulowana',
		self::STATUS_EXPIRED   => 'Wygasła',
	];

	// ── Queries ───────────────────────────────────────────────────────────────

	public static function get_all(array $filters = []): array {
		global $wpdb;
		$t     = Schema::table('resource_reservations');
		$where = ['1=1'];
		$vals  = [];

		if ( ! empty($filters['resource_id']) ) {
			$where[] = 'resource_id = %d';
			$vals[]  = (int) $filters['resource_id'];
		}
		if ( ! empty($filters['camp_id']) ) {
			$where[] = 'camp_id = %d';
			$vals[]  = (int) $filters['camp_id'];
		}
		if ( ! empty($filters['status']) ) {
			$where[] = 'status = %s';
			$vals[]  = $filters['status'];
		}
		if ( ! empty($filters['date_from']) ) {
			$where[] = 'res_date >= %s';
			$vals[]  = $filters['date_from'];
		}
		if ( ! empty($filters['date_to']) ) {
			$where[] = 'res_date <= %s';
			$vals[]  = $filters['date_to'];
		}

		$sql = 'SELECT * FROM ' . $t . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY res_date DESC, start_time DESC';
		if ( $vals ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results($wpdb->prepare($sql, ...$vals));
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results($sql);
	}

	public static function get_by_camp(int $camp_id, array $filters = []): array {
		return self::get_all(array_merge($filters, ['camp_id' => $camp_id]));
	}

	public static function get(int $id): ?object {
		global $wpdb;
		$t = Schema::table('resource_reservations');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $id)) ?: null;
	}

	public static function count_pending(): int {
		global $wpdb;
		$t = Schema::table('resource_reservations');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE status = %s", self::STATUS_PENDING));
	}

	/**
	 * Returns upcoming approved reservations (today onwards), limited to $limit.
	 */
	public static function get_upcoming(int $limit = 5): array {
		global $wpdb;
		$t    = Schema::table('resource_reservations');
		$today = gmdate('Y-m-d');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM $t WHERE status = %s AND res_date >= %s ORDER BY res_date ASC, start_time ASC LIMIT %d",
			self::STATUS_APPROVED, $today, $limit
		));
	}

	// ── Create with conflict check (transaction + FOR UPDATE) ────────────────

	/**
	 * Attempts to create a reservation.
	 *
	 * Returns:
	 *   - ['id' => int]              on success
	 *   - ['error' => 'conflict']    when time slot is taken
	 *   - ['error' => 'blocked']     when resource has a maintenance block
	 *   - ['error' => 'unavailable'] when resource is inactive or out of hours
	 *   - ['error' => 'db_error']    on unexpected DB failure
	 */
	public static function create_with_conflict_check(array $data): array {
		global $wpdb;

		$resource_id = (int) $data['resource_id'];
		$camp_id     = (int) $data['camp_id'];
		$staff_id    = (int) $data['staff_id'];
		$res_date    = sanitize_text_field($data['res_date']);
		$start_time  = sanitize_text_field($data['start_time']);
		$end_time    = sanitize_text_field($data['end_time']);
		$purpose     = sanitize_textarea_field($data['purpose'] ?? '');

		// 1. Load resource (outside transaction – read-only check).
		$resource = ResourceRepository::get($resource_id);
		if ( ! $resource || $resource->status !== 'active' ) {
			return ['error' => 'unavailable'];
		}

		// 2. Check resource-level technical block (global is_blocked flag).
		if ( $resource->is_blocked ) {
			if ( ! $resource->block_from && ! $resource->block_to ) {
				return ['error' => 'blocked'];
			}
			$slot_start = $res_date . ' ' . $start_time;
			$slot_end   = $res_date . ' ' . $end_time;
			if ( $resource->block_from <= $slot_end && $resource->block_to >= $slot_start ) {
				return ['error' => 'blocked'];
			}
		}

		// 3. Check maintenance blocks table.
		$slot_start = $res_date . ' ' . $start_time;
		$slot_end   = $res_date . ' ' . $end_time;
		if ( ResourceRepository::has_block_conflict($resource_id, $slot_start, $slot_end) ) {
			return ['error' => 'blocked'];
		}

		// 4. Check available_from / available_to hours.
		if ( ! ResourceRepository::slot_within_hours($resource, $start_time, $end_time) ) {
			return ['error' => 'unavailable'];
		}

		// 5. Duration check.
		$duration_min = self::duration_minutes($start_time, $end_time);
		if ( $resource->min_duration_minutes > 0 && $duration_min < $resource->min_duration_minutes ) {
			return ['error' => 'too_short'];
		}
		if ( $resource->max_duration_minutes > 0 && $duration_min > $resource->max_duration_minutes ) {
			return ['error' => 'too_long'];
		}

		// 5b. Camp active-reservation limit check (pre-transaction, advisory).
		$max_per_camp = (int) $resource->max_reservations_per_camp;
		if ( $max_per_camp > 0 ) {
			$active_count = (int) $wpdb->get_var($wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM $t WHERE resource_id = %d AND camp_id = %d AND status IN ('" . implode("','", self::BLOCKING_STATUSES) . "')",
				$resource_id, $camp_id
			));
			if ( $active_count >= $max_per_camp ) {
				return ['error' => 'camp_limit'];
			}
		}

		$t = Schema::table('resource_reservations');

		// 6. BEGIN TRANSACTION + SELECT FOR UPDATE (prevents race conditions).
		$wpdb->query('START TRANSACTION');

		$statuses_in = implode("','", self::BLOCKING_STATUSES);
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$conflict = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $t
			 WHERE resource_id = %d
			   AND res_date = %s
			   AND status IN ('$statuses_in')
			   AND start_time < %s
			   AND end_time   > %s
			 FOR UPDATE",
			$resource_id, $res_date, $end_time, $start_time
		));

		if ( $conflict > 0 ) {
			$wpdb->query('ROLLBACK');
			return ['error' => 'conflict'];
		}

		// 7. Insert reservation.
		$inserted = $wpdb->insert($t, [
			'resource_id'    => $resource_id,
			'camp_id'        => $camp_id,
			'staff_id'       => $staff_id,
			'res_date'       => $res_date,
			'start_time'     => $start_time,
			'end_time'       => $end_time,
			'purpose'        => $purpose,
			'status'         => self::STATUS_PENDING,
			'admin_comment'  => '',
			'created_at'     => current_time('mysql', true),
			'updated_at'     => current_time('mysql', true),
		]);

		if ( ! $inserted ) {
			$wpdb->query('ROLLBACK');
			return ['error' => 'db_error'];
		}

		$new_id = (int) $wpdb->insert_id;
		$wpdb->query('COMMIT');

		do_action('bm_reservation_created', $new_id, $data);

		return ['id' => $new_id];
	}

	// ── Status updates (admin) ────────────────────────────────────────────────

	public static function update_status(int $id, string $status, string $comment = '', int $user_id = 0): bool {
		global $wpdb;
		if ( ! array_key_exists($status, self::STATUSES) ) return false;

		$result = $wpdb->update(Schema::table('resource_reservations'), [
			'status'         => $status,
			'admin_comment'  => sanitize_textarea_field($comment),
			'updated_at'     => current_time('mysql', true),
		], ['id' => $id]);

		if ( $result !== false ) {
			do_action('bm_reservation_status_changed', $id, $status, $user_id);
		}

		return $result !== false;
	}

	/**
	 * Camp cancels their own reservation.
	 * Only pending reservations can be cancelled by the camp.
	 * Respects cancel_advance_hours if set on the resource.
	 */
	public static function cancel_by_camp(int $id, int $camp_id): bool {
		global $wpdb;
		$t   = Schema::table('resource_reservations');
		$row = self::get($id);

		if ( ! $row || (int) $row->camp_id !== $camp_id ) return false;
		if ( $row->status !== self::STATUS_PENDING ) return false;
		// Prevent cancelling past reservations.
		if ( $row->res_date < gmdate('Y-m-d') ) return false;

		// Respect cancel_advance_hours from resource.
		$resource = ResourceRepository::get((int) $row->resource_id);
		if ( $resource && (int) $resource->cancel_advance_hours > 0 ) {
			$cutoff = strtotime($row->res_date . ' ' . $row->start_time) - ((int) $resource->cancel_advance_hours * HOUR_IN_SECONDS);
			if ( time() > $cutoff ) return false;
		}

		$ok = (bool) $wpdb->update($t, [
			'status'     => self::STATUS_CANCELLED,
			'updated_at' => current_time('mysql', true),
		], ['id' => $id]);

		if ( $ok ) {
			do_action('bm_reservation_status_changed', $id, self::STATUS_CANCELLED, 0);
		}

		return $ok;
	}

	// ── Admin override create ─────────────────────────────────────────────────

	public static function admin_create(array $data): array {
		// Admin bypasses advance-time restrictions but still checks conflicts.
		return self::create_with_conflict_check($data + ['staff_id' => 0]);
	}

	// ── Cron: expire pending reservations past their date ────────────────────

	public static function expire_past(): int {
		global $wpdb;
		$t     = Schema::table('resource_reservations');
		$today = gmdate('Y-m-d');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query($wpdb->prepare(
			"UPDATE $t SET status = %s, updated_at = %s WHERE status = %s AND res_date < %s",
			self::STATUS_EXPIRED, current_time('mysql', true), self::STATUS_PENDING, $today
		));
		return (int) ($result ?: 0);
	}

	// ── Availability helper for frontend ─────────────────────────────────────

	/**
	 * Returns an array of reserved slots for a resource on a given date.
	 * Shape: [{start_time, end_time, status}]
	 */
	public static function get_slots_for_date(int $resource_id, string $date): array {
		global $wpdb;
		$t        = Schema::table('resource_reservations');
		$statuses = implode("','", self::BLOCKING_STATUSES);
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results($wpdb->prepare(
			"SELECT start_time, end_time, status FROM $t
			 WHERE resource_id = %d AND res_date = %s AND status IN ('$statuses')
			 ORDER BY start_time ASC",
			$resource_id, $date
		));
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	private static function duration_minutes(string $start, string $end): int {
		$s = strtotime("2000-01-01 $start");
		$e = strtotime("2000-01-01 $end");
		return (int) max(0, ($e - $s) / 60);
	}
}
