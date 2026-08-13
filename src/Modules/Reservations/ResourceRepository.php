<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Reservations;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access for resources (Zasoby) in the Reservations module.
 *
 * Tables: bm_resources, bm_resource_blocks
 */
final class ResourceRepository {

	public const TYPE_PLACE    = 'miejsce';
	public const TYPE_FIELD    = 'boisko';
	public const TYPE_ROOM     = 'sala';
	public const TYPE_EQUIPMENT = 'sprzet';
	public const TYPE_OTHER    = 'inne';

	public const TYPES = [
		self::TYPE_PLACE     => 'Miejsce (np. ogniskowe)',
		self::TYPE_FIELD     => 'Boisko / teren',
		self::TYPE_ROOM      => 'Sala',
		self::TYPE_EQUIPMENT => 'Sprzęt',
		self::TYPE_OTHER     => 'Inne',
	];

	// ── Resources ─────────────────────────────────────────────────────────────

	public static function get_all(array $filters = []): array {
		global $wpdb;
		$t     = Schema::table('resources');
		$where = ['1=1'];
		$vals  = [];

		if ( isset($filters['status']) ) {
			$where[] = 'status = %s';
			$vals[]  = $filters['status'];
		}
		if ( isset($filters['type']) ) {
			$where[] = 'type = %s';
			$vals[]  = $filters['type'];
		}

		$sql = 'SELECT * FROM ' . $t . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY name ASC';
		if ( $vals ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results($wpdb->prepare($sql, ...$vals));
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results($sql);
	}

	public static function get_active(): array {
		return self::get_all(['status' => 'active']);
	}

	public static function get(int $id): ?object {
		global $wpdb;
		$t = Schema::table('resources');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $id)) ?: null;
	}

	public static function create(array $data): int {
		global $wpdb;
		$wpdb->insert(Schema::table('resources'), [
			'name'                      => sanitize_text_field($data['name']),
			'type'                      => sanitize_key($data['type'] ?? self::TYPE_OTHER),
			'description'               => sanitize_textarea_field($data['description'] ?? ''),
			'status'                    => sanitize_key($data['status'] ?? 'active'),
			'rules'                     => sanitize_textarea_field($data['rules'] ?? ''),
			'available_from'            => sanitize_text_field($data['available_from'] ?? '06:00'),
			'available_to'              => sanitize_text_field($data['available_to']   ?? '22:00'),
			'min_duration_minutes'      => (int) ($data['min_duration_minutes']      ?? 0),
			'max_duration_minutes'      => (int) ($data['max_duration_minutes']      ?? 0),
			'min_advance_hours'         => (int) ($data['min_advance_hours']         ?? 0),
			'max_advance_days'          => (int) ($data['max_advance_days']          ?? 30),
			'cancel_advance_hours'      => (int) ($data['cancel_advance_hours']      ?? 0),
			'max_reservations_per_camp' => (int) ($data['max_reservations_per_camp'] ?? 0),
			'cost_per_reservation'      => (float) str_replace(',', '.', (string) ($data['cost_per_reservation'] ?? '0')),
			'pricing_mode'              => in_array($data['pricing_mode'] ?? '', ['flat', 'per_unit'], true) ? $data['pricing_mode'] : 'flat',
			'total_units'               => max(0, (int) ($data['total_units'] ?? 0)),
			'is_blocked'                => (int) ($data['is_blocked']                ?? 0),
			'block_reason'              => sanitize_text_field($data['block_reason'] ?? ''),
			'block_from'                => $data['block_from'] ?: null,
			'block_to'                  => $data['block_to']   ?: null,
		]);
		return (int) $wpdb->insert_id;
	}

	public static function update(int $id, array $data): bool {
		global $wpdb;
		$fields = ['name', 'type', 'description', 'status', 'rules', 'available_from', 'available_to',
		           'min_duration_minutes', 'max_duration_minutes', 'min_advance_hours', 'max_advance_days',
		           'cancel_advance_hours', 'max_reservations_per_camp', 'cost_per_reservation',
		           'pricing_mode', 'total_units',
		           'is_blocked', 'block_reason', 'block_from', 'block_to'];
		$update = [];
		foreach ( $fields as $f ) {
			if ( ! array_key_exists($f, $data) ) continue;
			$update[$f] = match ($f) {
				'name'                 => sanitize_text_field($data[$f]),
				'type', 'status'       => sanitize_key($data[$f]),
				'description', 'rules' => sanitize_textarea_field($data[$f]),
				'min_duration_minutes', 'max_duration_minutes', 'min_advance_hours',
				'max_advance_days', 'cancel_advance_hours', 'max_reservations_per_camp',
				'is_blocked', 'total_units' => max(0, (int) $data[$f]),
				'cost_per_reservation' => (float) str_replace(',', '.', (string) $data[$f]),
				'pricing_mode'         => in_array($data[$f], ['flat', 'per_unit'], true) ? $data[$f] : 'flat',
				'block_from', 'block_to' => $data[$f] ?: null,
				default => sanitize_text_field($data[$f]),
			};
		}
		if ( empty($update) ) return false;
		return (bool) $wpdb->update(Schema::table('resources'), $update, ['id' => $id]);
	}

	public static function delete(int $id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('resource_blocks'), ['resource_id' => $id]);
		$wpdb->delete(Schema::table('resources'), ['id' => $id]);
	}

	// ── Blocks (maintenance windows) ──────────────────────────────────────────

	public static function get_blocks(int $resource_id, string $date_from = '', string $date_to = ''): array {
		global $wpdb;
		$t     = Schema::table('resource_blocks');
		$where = ['resource_id = %d'];
		$vals  = [$resource_id];

		if ( $date_from && $date_to ) {
			$where[] = 'block_to >= %s AND block_from <= %s';
			$vals[]  = $date_from . ' 00:00:00';
			$vals[]  = $date_to   . ' 23:59:59';
		}

		$sql = "SELECT * FROM $t WHERE " . implode(' AND ', $where) . ' ORDER BY block_from ASC';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results($wpdb->prepare($sql, ...$vals));
	}

	public static function create_block(array $data): int {
		global $wpdb;
		$wpdb->insert(Schema::table('resource_blocks'), [
			'resource_id' => (int) $data['resource_id'],
			'reason'      => sanitize_text_field($data['reason'] ?? ''),
			'block_from'  => $data['block_from'],
			'block_to'    => $data['block_to'],
			'created_by'  => (int) ($data['created_by'] ?? get_current_user_id()),
		]);
		return (int) $wpdb->insert_id;
	}

	public static function delete_block(int $id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('resource_blocks'), ['id' => $id]);
	}

	/**
	 * Checks whether a resource is available for a given datetime slot.
	 * Returns true if the slot is free, false otherwise.
	 * Does NOT check reservation conflicts (see ReservationRepository::check_conflicts).
	 */
	public static function slot_within_hours(object $resource, string $start_time, string $end_time): bool {
		if ( ! $resource->available_from || ! $resource->available_to ) return true;
		return $start_time >= $resource->available_from && $end_time <= $resource->available_to;
	}

	/**
	 * Checks if a datetime range overlaps with any maintenance block for this resource.
	 */
	public static function has_block_conflict(int $resource_id, string $datetime_from, string $datetime_to): bool {
		global $wpdb;
		$t = Schema::table('resource_blocks');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $t WHERE resource_id = %d AND block_from < %s AND block_to > %s",
			$resource_id, $datetime_to, $datetime_from
		));
		return $count > 0;
	}
}
