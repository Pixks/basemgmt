<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Schedule;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access for Plan Day Templates (Szablony planów dnia).
 *
 * Recurrence types:
 *   once    – one-off template (apply manually)
 *   daily   – repeat every day
 *   weekly  – repeat on selected days of week (days_of_week: "1,3,5" = Mon,Wed,Fri)
 */
final class PlanTemplateRepository {

	public const RECURRENCE_ONCE   = 'once';
	public const RECURRENCE_DAILY  = 'daily';
	public const RECURRENCE_WEEKLY = 'weekly';

	public const RECURRENCES = [
		self::RECURRENCE_ONCE   => 'Jednorazowy (stosuj ręcznie)',
		self::RECURRENCE_DAILY  => 'Codziennie',
		self::RECURRENCE_WEEKLY => 'Wybrane dni tygodnia',
	];

	public const DAY_NAMES = [
		1 => 'Poniedziałek',
		2 => 'Wtorek',
		3 => 'Środa',
		4 => 'Czwartek',
		5 => 'Piątek',
		6 => 'Sobota',
		7 => 'Niedziela',
	];

	// ── Templates ─────────────────────────────────────────────────────────────

	public static function get_all(): array {
		global $wpdb;
		$t = Schema::table('plan_templates');
		return $wpdb->get_results("SELECT * FROM {$t} ORDER BY name ASC") ?: [];
	}

	public static function get(int $id): ?object {
		global $wpdb;
		$t = Schema::table('plan_templates');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	public static function create(array $data): int {
		global $wpdb;
		$t = Schema::table('plan_templates');
		$wpdb->insert($t, [
			'name'         => sanitize_text_field($data['name']         ?? ''),
			'description'  => sanitize_textarea_field($data['description'] ?? ''),
			'recurrence'   => sanitize_key($data['recurrence']           ?? self::RECURRENCE_ONCE),
			'days_of_week' => sanitize_text_field($data['days_of_week']  ?? ''),
			'created_by'   => (int) ($data['created_by'] ?? get_current_user_id()),
		]);
		return (int) $wpdb->insert_id;
	}

	public static function update(int $id, array $data): void {
		global $wpdb;
		$t = Schema::table('plan_templates');
		$wpdb->update(
			$t,
			[
				'name'         => sanitize_text_field($data['name']         ?? ''),
				'description'  => sanitize_textarea_field($data['description'] ?? ''),
				'recurrence'   => sanitize_key($data['recurrence']           ?? self::RECURRENCE_ONCE),
				'days_of_week' => sanitize_text_field($data['days_of_week']  ?? ''),
			],
			['id' => $id]
		);
	}

	public static function delete(int $id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('plan_template_items'), ['template_id' => $id]);
		$wpdb->delete(Schema::table('plan_templates'), ['id' => $id]);
	}

	// ── Template Items ────────────────────────────────────────────────────────

	public static function get_items(int $template_id): array {
		global $wpdb;
		$t = Schema::table('plan_template_items');
		return $wpdb->get_results(
			$wpdb->prepare("SELECT * FROM {$t} WHERE template_id = %d ORDER BY sort_order ASC, id ASC", $template_id)
		) ?: [];
	}

	public static function get_item(int $id): ?object {
		global $wpdb;
		$t = Schema::table('plan_template_items');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	public static function save_item(array $data): int {
		global $wpdb;
		$t  = Schema::table('plan_template_items');
		$id = (int) ($data['id'] ?? 0);

		$payload = [
			'template_id'  => (int) $data['template_id'],
			'time_from'    => sanitize_text_field($data['time_from']    ?? ''),
			'time_to'      => sanitize_text_field($data['time_to']      ?? ''),
			'title'        => sanitize_text_field($data['title']        ?? ''),
			'description'  => sanitize_textarea_field($data['description'] ?? ''),
			'category'     => sanitize_key($data['category']            ?? ScheduleRepository::CAT_INNE),
			'is_mandatory' => (int) ($data['is_mandatory']              ?? 0),
			'sort_order'   => (int) ($data['sort_order']                ?? 0),
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
		$wpdb->delete(Schema::table('plan_template_items'), ['id' => $id]);
	}

	// ── Apply template to a plan ──────────────────────────────────────────────

	/**
	 * Apply a template to an existing plan header: copy all template items.
	 *
	 * @param int  $template_id   Source template.
	 * @param int  $plan_id       Target plan_headers ID.
	 * @param bool $replace       If true, delete existing items first.
	 * @return int Number of items added.
	 */
	public static function apply_to_plan(int $template_id, int $plan_id, bool $replace = false): int {
		if ( $replace ) {
			global $wpdb;
			$wpdb->delete(Schema::table('plan_items'), ['plan_id' => $plan_id]);
		}

		$items = self::get_items($template_id);
		foreach ( $items as $item ) {
			ScheduleRepository::create_item([
				'plan_id'      => $plan_id,
				'time_from'    => $item->time_from,
				'time_to'      => $item->time_to,
				'title'        => $item->title,
				'description'  => $item->description,
				'category'     => $item->category,
				'item_status'  => ScheduleRepository::ITEM_ACTIVE,
				'is_mandatory' => $item->is_mandatory,
				'sort_order'   => $item->sort_order,
			]);
		}
		return count($items);
	}

	/**
	 * Find templates that match a given date (for auto-apply).
	 * Returns templates whose recurrence matches the day of week of $date.
	 */
	public static function get_matching_for_date(string $date): array {
		$dow      = (int) gmdate('N', strtotime($date)); // 1=Mon … 7=Sun
		$all      = self::get_all();
		$matching = [];

		foreach ( $all as $tpl ) {
			if ( $tpl->recurrence === self::RECURRENCE_DAILY ) {
				$matching[] = $tpl;
			} elseif ( $tpl->recurrence === self::RECURRENCE_WEEKLY ) {
				$days = array_map('intval', explode(',', $tpl->days_of_week));
				if ( in_array($dow, $days, true) ) {
					$matching[] = $tpl;
				}
			}
		}
		return $matching;
	}
}
