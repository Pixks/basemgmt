<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Schedule;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access for Plan dnia (Day Schedule) module.
 *
 * Tables:
 *   bm_plan_headers        – one per day per plan set
 *   bm_plan_items          – line items belonging to a header
 *   bm_plan_item_revisions – lightweight changelog for items
 *   bm_plan_camps          – camp assignments for non-global plans
 */
final class ScheduleRepository {

	// ── Category constants ─────────────────────────────────────────────────────

	public const CAT_APEL        = 'apel';
	public const CAT_POSILEK     = 'posilek';
	public const CAT_CISZA       = 'cisza_nocna';
	public const CAT_ZAJECIA     = 'zajecia';
	public const CAT_ZBIORKA     = 'zbiorka';
	public const CAT_INFO        = 'informacja';
	public const CAT_INNE        = 'inne';

	public const CATEGORIES = [
		self::CAT_APEL    => 'Apel',
		self::CAT_POSILEK => 'Posiłek',
		self::CAT_CISZA   => 'Cisza nocna',
		self::CAT_ZAJECIA => 'Zajęcia programowe',
		self::CAT_ZBIORKA => 'Zbiórka',
		self::CAT_INFO    => 'Informacja organizacyjna',
		self::CAT_INNE    => 'Inne',
	];

	// ── Item status constants ──────────────────────────────────────────────────

	public const ITEM_ACTIVE    = 'active';
	public const ITEM_CHANGED   = 'changed';
	public const ITEM_CANCELLED = 'cancelled';

	// ── Plan header status constants ───────────────────────────────────────────

	public const PLAN_ACTIVE   = 'active';
	public const PLAN_DRAFT    = 'draft';
	public const PLAN_ARCHIVED = 'archived';

	// ── Headers ───────────────────────────────────────────────────────────────

	public static function get_all_headers(array $filters = []): array {
		global $wpdb;
		$t     = Schema::table('plan_headers');
		$where = ['1=1'];
		$vals  = [];

		if ( ! empty($filters['date']) ) {
			$where[] = 'plan_date = %s';
			$vals[]  = $filters['date'];
		}
		if ( isset($filters['is_global']) ) {
			$where[] = 'is_global = %d';
			$vals[]  = (int) $filters['is_global'];
		}
		if ( ! empty($filters['status']) ) {
			$where[] = 'status = %s';
			$vals[]  = $filters['status'];
		}

		$sql = 'SELECT * FROM ' . $t . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY plan_date DESC, id DESC';
		if ( $vals ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results($wpdb->prepare($sql, ...$vals));
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results($sql);
	}

	public static function get_header(int $id): ?object {
		global $wpdb;
		$t = Schema::table('plan_headers');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $id)) ?: null;
	}

	public static function get_header_for_date(string $date): ?object {
		global $wpdb;
		$t = Schema::table('plan_headers');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE plan_date = %s ORDER BY id ASC LIMIT 1", $date)) ?: null;
	}

	public static function create_header(array $data): int {
		global $wpdb;
		$wpdb->insert(Schema::table('plan_headers'), [
			'plan_date'  => $data['plan_date'],
			'title'      => sanitize_text_field($data['title'] ?? ''),
			'is_global'  => (int) ($data['is_global'] ?? 1),
			'status'     => sanitize_key($data['status'] ?? self::PLAN_ACTIVE),
			'created_by' => (int) ($data['created_by'] ?? get_current_user_id()),
		]);
		return (int) $wpdb->insert_id;
	}

	public static function update_header(int $id, array $data): bool {
		global $wpdb;
		$update = [];
		if ( isset($data['title']) )     $update['title']     = sanitize_text_field($data['title']);
		if ( isset($data['is_global']) ) $update['is_global'] = (int) $data['is_global'];
		if ( isset($data['status']) )    $update['status']    = sanitize_key($data['status']);
		if ( empty($update) ) return false;
		return (bool) $wpdb->update(Schema::table('plan_headers'), $update, ['id' => $id]);
	}

	public static function delete_header(int $id): void {
		global $wpdb;
		// Delete items + revisions first.
		self::delete_items_for_plan($id);
		$wpdb->delete(Schema::table('plan_camps'), ['plan_id' => $id]);
		$wpdb->delete(Schema::table('plan_headers'), ['id' => $id]);
	}

	// ── Camp assignments ──────────────────────────────────────────────────────

	public static function assign_camps(int $plan_id, array $camp_ids): void {
		global $wpdb;
		$tc = Schema::table('plan_camps');
		$wpdb->delete($tc, ['plan_id' => $plan_id]);
		foreach ( array_unique(array_map('intval', $camp_ids)) as $cid ) {
			$wpdb->insert($tc, ['plan_id' => $plan_id, 'camp_id' => $cid]);
		}
	}

	public static function get_assigned_camps(int $plan_id): array {
		global $wpdb;
		$tc = Schema::table('plan_camps');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_col($wpdb->prepare("SELECT camp_id FROM $tc WHERE plan_id = %d", $plan_id));
	}

	// ── Items ─────────────────────────────────────────────────────────────────

	public static function get_items(int $plan_id): array {
		global $wpdb;
		$t = Schema::table('plan_items');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE plan_id = %d ORDER BY sort_order ASC, time_from ASC", $plan_id));
	}

	public static function get_item(int $id): ?object {
		global $wpdb;
		$t = Schema::table('plan_items');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $id)) ?: null;
	}

	public static function create_item(array $data): int {
		global $wpdb;
		$wpdb->insert(Schema::table('plan_items'), [
			'plan_id'          => (int)    $data['plan_id'],
			'time_from'        => sanitize_text_field($data['time_from'] ?? ''),
			'time_to'          => sanitize_text_field($data['time_to']   ?? ''),
			'title'            => sanitize_text_field($data['title']),
			'description'      => sanitize_textarea_field($data['description'] ?? ''),
			'category'         => sanitize_key($data['category'] ?? self::CAT_INNE),
			'item_status'      => sanitize_key($data['item_status'] ?? self::ITEM_ACTIVE),
			'is_mandatory'     => (int) ($data['is_mandatory'] ?? 0),
			'sort_order'       => (int) ($data['sort_order'] ?? 0),
			'is_new_today'     => (int) ($data['is_new_today'] ?? 0),
			'is_updated_today' => (int) ($data['is_updated_today'] ?? 0),
		]);
		return (int) $wpdb->insert_id;
	}

	public static function update_item(int $id, array $data, int $user_id = 0): bool {
		global $wpdb;
		$existing = self::get_item($id);
		if ( ! $existing ) return false;

		$update = [];
		$tracked_fields = ['time_from', 'time_to', 'title', 'description', 'category', 'item_status', 'is_mandatory', 'sort_order'];

		foreach ( $tracked_fields as $f ) {
			if ( array_key_exists($f, $data) ) {
				$update[$f] = $f === 'description' ? sanitize_textarea_field($data[$f]) : (in_array($f, ['is_mandatory', 'sort_order']) ? (int) $data[$f] : sanitize_text_field($data[$f]));
			}
		}
		if ( isset($data['is_new_today']) )     $update['is_new_today']     = (int) $data['is_new_today'];
		if ( isset($data['is_updated_today']) ) $update['is_updated_today'] = (int) $data['is_updated_today'];

		if ( empty($update) ) return false;

		// Store revision before updating.
		self::save_revision($id, $existing, 'updated', $user_id);

		return (bool) $wpdb->update(Schema::table('plan_items'), $update, ['id' => $id]);
	}

	public static function delete_item(int $id): void {
		global $wpdb;
		self::delete_item_revisions($id);
		$wpdb->delete(Schema::table('plan_items'), ['id' => $id]);
	}

	public static function delete_items_for_plan(int $plan_id): void {
		global $wpdb;
		$items = self::get_items($plan_id);
		foreach ( $items as $item ) {
			self::delete_item_revisions((int) $item->id);
		}
		$wpdb->delete(Schema::table('plan_items'), ['plan_id' => $plan_id]);
	}

	public static function reorder_items(int $plan_id, array $order): void {
		global $wpdb;
		$t = Schema::table('plan_items');
		foreach ( $order as $sort_order => $item_id ) {
			$wpdb->update($t, ['sort_order' => (int) $sort_order], ['id' => (int) $item_id, 'plan_id' => $plan_id]);
		}
	}

	public static function reset_daily_flags(int $plan_id): void {
		global $wpdb;
		$t = Schema::table('plan_items');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query($wpdb->prepare("UPDATE $t SET is_new_today = 0, is_updated_today = 0 WHERE plan_id = %d", $plan_id));
	}

	public static function has_matching_item(int $plan_id, array $data): bool {
		global $wpdb;
		$t = Schema::table('plan_items');

		$time_from = sanitize_text_field($data['time_from'] ?? '');
		$time_to   = sanitize_text_field($data['time_to'] ?? '');
		$title     = sanitize_text_field($data['title'] ?? '');
		$category  = sanitize_key($data['category'] ?? self::CAT_INNE);

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing_id = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM $t WHERE plan_id = %d AND time_from = %s AND time_to = %s AND title = %s AND category = %s LIMIT 1",
			$plan_id,
			$time_from,
			$time_to,
			$title,
			$category
		));

		return ! empty($existing_id);
	}

	public static function copy_items_from_plan(int $source_plan_id, int $target_plan_id, bool $replace = false, bool $skip_duplicates = false): int {
		if ( $replace ) {
			self::delete_items_for_plan($target_plan_id);
		}

		$added = 0;
		foreach ( self::get_items($source_plan_id) as $item ) {
			$payload = [
				'plan_id'          => $target_plan_id,
				'time_from'        => $item->time_from,
				'time_to'          => $item->time_to,
				'title'            => $item->title,
				'description'      => $item->description,
				'category'         => $item->category,
				'item_status'      => self::ITEM_ACTIVE,
				'is_mandatory'     => $item->is_mandatory,
				'sort_order'       => $item->sort_order,
				'is_new_today'     => 0,
				'is_updated_today' => 0,
			];

			if ( $skip_duplicates && self::has_matching_item($target_plan_id, $payload) ) {
				continue;
			}

			self::create_item($payload);
			$added++;
		}

		return $added;
	}

	// ── Copy plan from previous day ───────────────────────────────────────────

	/**
	 * Creates a new plan header for $to_date and copies all items from the latest
	 * plan for $from_date (or from the newest plan before $to_date if $from_date
	 * is empty). Returns new header id or 0 on failure.
	 */
	public static function copy_from_date(string $from_date, string $to_date, int $user_id): int {
		$source = self::get_header_for_date($from_date);
		if ( ! $source ) return 0;

		// Avoid duplicates.
		$existing = self::get_header_for_date($to_date);
		if ( $existing ) return (int) $existing->id;

		$new_id = self::create_header([
			'plan_date'  => $to_date,
			'title'      => '',
			'is_global'  => $source->is_global,
			'status'     => self::PLAN_ACTIVE,
			'created_by' => $user_id,
		]);

		if ( ! $new_id ) return 0;

		// Copy camp assignments.
		if ( ! $source->is_global ) {
			$camp_ids = self::get_assigned_camps((int) $source->id);
			if ( $camp_ids ) self::assign_camps($new_id, $camp_ids);
		}

		// Copy items (reset daily flags, reset status to active).
		foreach ( self::get_items((int) $source->id) as $item ) {
			self::create_item([
				'plan_id'          => $new_id,
				'time_from'        => $item->time_from,
				'time_to'          => $item->time_to,
				'title'            => $item->title,
				'description'      => $item->description,
				'category'         => $item->category,
				'item_status'      => self::ITEM_ACTIVE,
				'is_mandatory'     => $item->is_mandatory,
				'sort_order'       => $item->sort_order,
				'is_new_today'     => 0,
				'is_updated_today' => 0,
			]);
		}

		return $new_id;
	}

	// ── Frontend: get plans visible for a camp on a given date ────────────────

	/**
	 * Returns all plan headers visible to a camp (global + assigned) for the given date.
	 * Includes their items.
	 */
	public static function get_for_camp_date(int $camp_id, string $date): array {
		global $wpdb;
		$th = Schema::table('plan_headers');
		$tc = Schema::table('plan_camps');

		// Global plans for this date OR plans assigned to this camp.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$headers = $wpdb->get_results($wpdb->prepare(
			"SELECT DISTINCT h.* FROM $th h
			 LEFT JOIN $tc pc ON pc.plan_id = h.id AND pc.camp_id = %d
			 WHERE h.plan_date = %s AND h.status = 'active'
			   AND (h.is_global = 1 OR pc.camp_id IS NOT NULL)
			 ORDER BY h.id ASC",
			$camp_id, $date
		));

		foreach ( $headers as &$header ) {
			$header->items = self::get_items((int) $header->id);
		}

		return $headers;
	}

	/**
	 * Returns distinct dates that have active plans visible to a camp,
	 * within a date range (e.g. camp's stay period).
	 */
	public static function get_dates_for_camp(int $camp_id, string $date_from, string $date_to): array {
		global $wpdb;
		$th = Schema::table('plan_headers');
		$tc = Schema::table('plan_camps');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_col($wpdb->prepare(
			"SELECT DISTINCT h.plan_date FROM $th h
			 LEFT JOIN $tc pc ON pc.plan_id = h.id AND pc.camp_id = %d
			 WHERE h.plan_date BETWEEN %s AND %s AND h.status = 'active'
			   AND (h.is_global = 1 OR pc.camp_id IS NOT NULL)
			 ORDER BY h.plan_date ASC",
			$camp_id, $date_from, $date_to
		));
	}

	// ── Revisions ─────────────────────────────────────────────────────────────

	public static function get_revisions(int $item_id): array {
		global $wpdb;
		$t = Schema::table('plan_item_revisions');
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE item_id = %d ORDER BY changed_at DESC", $item_id));
	}

	private static function save_revision(int $item_id, object $old, string $change_type, int $user_id): void {
		global $wpdb;
		$wpdb->insert(Schema::table('plan_item_revisions'), [
			'item_id'     => $item_id,
			'change_type' => $change_type,
			'old_data'    => wp_json_encode($old),
			'changed_by'  => $user_id ?: get_current_user_id(),
			'changed_at'  => current_time('mysql', true),
		]);
	}

	private static function delete_item_revisions(int $item_id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('plan_item_revisions'), ['item_id' => $item_id]);
	}
}
