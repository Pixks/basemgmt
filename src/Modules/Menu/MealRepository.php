<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Menu;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access for Jadłospis (Meal Menu) module.
 *
 * Tables:
 *   bm_meal_days  – one row per calendar day
 *   bm_meal_items – individual meal entries for a day
 */
final class MealRepository {

	// ── Type constants ────────────────────────────────────────────────────────

	public const MEAL_TYPES = [
		'sniadanie'        => 'Śniadanie',
		'drugie_sniadanie' => 'Drugie śniadanie',
		'obiad'            => 'Obiad',
		'podwieczorek'     => 'Podwieczorek',
		'kolacja'          => 'Kolacja',
		'inne'             => 'Inne',
	];

	public const STATUS_PUBLISHED = 'published';
	public const STATUS_DRAFT     = 'draft';

	// ── Meal Days ─────────────────────────────────────────────────────────────

	public static function get_all_days(array $filters = []): array {
		global $wpdb;
		$t     = Schema::table('meal_days');
		$where = ['1=1'];
		$vals  = [];

		if ( ! empty($filters['date']) ) {
			$where[] = 'meal_date = %s';
			$vals[]  = $filters['date'];
		}
		if ( ! empty($filters['date_from']) ) {
			$where[] = 'meal_date >= %s';
			$vals[]  = $filters['date_from'];
		}
		if ( ! empty($filters['date_to']) ) {
			$where[] = 'meal_date <= %s';
			$vals[]  = $filters['date_to'];
		}
		if ( ! empty($filters['status']) ) {
			$where[] = 'status = %s';
			$vals[]  = $filters['status'];
		}

		$sql = "SELECT * FROM {$t} WHERE " . implode(' AND ', $where) . ' ORDER BY meal_date DESC';
		if ( ! empty($vals) ) {
			$sql = $wpdb->prepare($sql, ...$vals);
		}

		return $wpdb->get_results($sql) ?: [];
	}

	public static function get_day(int $id): ?object {
		global $wpdb;
		$t = Schema::table('meal_days');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	public static function get_day_by_date(string $date): ?object {
		global $wpdb;
		$t = Schema::table('meal_days');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE meal_date = %s", $date)) ?: null;
	}

	public static function create_day(array $data): int {
		global $wpdb;
		$t = Schema::table('meal_days');
		$wpdb->insert($t, [
			'meal_date'  => sanitize_text_field($data['meal_date']),
			'notes'      => sanitize_textarea_field($data['notes'] ?? ''),
			'status'     => sanitize_key($data['status'] ?? self::STATUS_PUBLISHED),
			'created_by' => (int) ($data['created_by'] ?? get_current_user_id()),
		]);
		return (int) $wpdb->insert_id;
	}

	public static function update_day(int $id, array $data): void {
		global $wpdb;
		$t = Schema::table('meal_days');
		$wpdb->update(
			$t,
			[
				'notes'  => sanitize_textarea_field($data['notes'] ?? ''),
				'status' => sanitize_key($data['status'] ?? self::STATUS_PUBLISHED),
			],
			['id' => $id]
		);
	}

	public static function delete_day(int $id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('meal_items'), ['meal_day_id' => $id]);
		$wpdb->delete(Schema::table('meal_days'), ['id' => $id]);
	}

	// ── Meal Items ────────────────────────────────────────────────────────────

	public static function get_items(int $meal_day_id): array {
		global $wpdb;
		$t = Schema::table('meal_items');
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE meal_day_id = %d ORDER BY sort_order ASC, id ASC",
				$meal_day_id
			)
		) ?: [];
	}

	public static function get_item(int $id): ?object {
		global $wpdb;
		$t = Schema::table('meal_items');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	public static function save_item(array $data): int {
		global $wpdb;
		$t       = Schema::table('meal_items');
		$id      = (int) ($data['id'] ?? 0);
		$payload = [
			'meal_day_id'      => (int) $data['meal_day_id'],
			'meal_type'        => sanitize_key($data['meal_type'] ?? 'inne'),
			'time_from'        => sanitize_text_field($data['time_from'] ?? ''),
			'title'            => sanitize_text_field($data['title'] ?? ''),
			'description'      => sanitize_textarea_field($data['description'] ?? ''),
			'location'         => sanitize_text_field($data['location'] ?? ''),
			'diet_info'        => sanitize_text_field($data['diet_info'] ?? ''),
			'allergens'        => sanitize_text_field($data['allergens'] ?? ''),
			'sort_order'       => (int) ($data['sort_order'] ?? 0),
			'is_new_today'     => (int) ($data['is_new_today'] ?? 0),
			'is_updated_today' => (int) ($data['is_updated_today'] ?? 0),
		];

		if ( $id ) {
			$wpdb->update($t, $payload, ['id' => $id]);
		} else {
			$wpdb->insert($t, $payload);
			$id = (int) $wpdb->insert_id;
		}

		return $id;
	}

	public static function delete_item(int $id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('meal_items'), ['id' => $id]);
	}

	public static function reset_flags(int $meal_day_id): void {
		global $wpdb;
		$t = Schema::table('meal_items');
		$wpdb->update($t, ['is_new_today' => 0, 'is_updated_today' => 0], ['meal_day_id' => $meal_day_id]);
	}

	// ── Copy & availability ───────────────────────────────────────────────────

	/**
	 * Copy all items from one date to another day.
	 * Creates target day if it doesn't exist.
	 *
	 * @return int|false Target day ID or false if source not found.
	 */
	public static function copy_from_date(string $from_date, string $to_date): int|false {
		$from = self::get_day_by_date($from_date);
		if ( ! $from ) {
			return false;
		}

		$existing = self::get_day_by_date($to_date);
		if ( $existing ) {
			$to_id = (int) $existing->id;
		} else {
			$to_id = self::create_day(['meal_date' => $to_date]);
		}

		foreach ( self::get_items((int) $from->id) as $item ) {
			self::save_item([
				'meal_day_id' => $to_id,
				'meal_type'   => $item->meal_type,
				'time_from'   => $item->time_from,
				'title'       => $item->title,
				'description' => $item->description,
				'location'    => $item->location,
				'diet_info'   => $item->diet_info,
				'allergens'   => $item->allergens,
				'sort_order'  => $item->sort_order,
			]);
		}

		return $to_id;
	}

	/** Dates with a published menu in a given range. */
	public static function get_dates_in_range(string $from, string $to): array {
		global $wpdb;
		$t = Schema::table('meal_days');
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meal_date FROM {$t} WHERE meal_date BETWEEN %s AND %s AND status = 'published' ORDER BY meal_date ASC",
				$from,
				$to
			)
		) ?: [];
	}

	/** Full day (header + grouped items) for frontend. Returns null if not published. */
	public static function get_day_for_frontend(string $date): ?array {
		$day = self::get_day_by_date($date);
		if ( ! $day || $day->status !== self::STATUS_PUBLISHED ) {
			return null;
		}

		$raw_items = self::get_items((int) $day->id);
		// Group items by meal_type for easier frontend rendering.
		$grouped = [];
		foreach ( $raw_items as $item ) {
			$grouped[$item->meal_type][] = $item;
		}

		return [
			'id'        => (int) $day->id,
			'meal_date' => $day->meal_date,
			'notes'     => $day->notes,
			'items'     => $raw_items,
			'grouped'   => $grouped,
		];
	}
}
