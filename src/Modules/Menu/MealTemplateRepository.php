<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Menu;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access for Meal Menu Templates (Szablony jadłospisów).
 */
final class MealTemplateRepository {

	// ── Templates ─────────────────────────────────────────────────────────────

	public static function get_all(): array {
		global $wpdb;
		$t = Schema::table('meal_templates');
		return $wpdb->get_results("SELECT * FROM {$t} ORDER BY name ASC") ?: [];
	}

	public static function get(int $id): ?object {
		global $wpdb;
		$t = Schema::table('meal_templates');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	public static function create(array $data): int {
		global $wpdb;
		$t = Schema::table('meal_templates');
		$wpdb->insert($t, [
			'name'        => sanitize_text_field($data['name']        ?? ''),
			'description' => sanitize_textarea_field($data['description'] ?? ''),
			'created_by'  => (int) ($data['created_by'] ?? 0),
		]);
		return (int) $wpdb->insert_id;
	}

	public static function update(int $id, array $data): void {
		global $wpdb;
		$t = Schema::table('meal_templates');
		$wpdb->update(
			$t,
			[
				'name'        => sanitize_text_field($data['name']        ?? ''),
				'description' => sanitize_textarea_field($data['description'] ?? ''),
			],
			['id' => $id]
		);
	}

	public static function delete(int $id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('meal_template_items'), ['template_id' => $id]);
		$wpdb->delete(Schema::table('meal_templates'), ['id' => $id]);
	}

	// ── Template Items ────────────────────────────────────────────────────────

	public static function get_items(int $template_id): array {
		global $wpdb;
		$t = Schema::table('meal_template_items');
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE template_id = %d ORDER BY sort_order ASC, id ASC",
				$template_id
			)
		) ?: [];
	}

	public static function save_item(array $data): int {
		global $wpdb;
		$t  = Schema::table('meal_template_items');
		$id = (int) ($data['id'] ?? 0);

		$payload = [
			'template_id' => (int) $data['template_id'],
			'meal_type'   => sanitize_key($data['meal_type']    ?? 'inne'),
			'time_from'   => sanitize_text_field($data['time_from']    ?? ''),
			'title'       => sanitize_text_field($data['title']        ?? ''),
			'description' => sanitize_textarea_field($data['description'] ?? ''),
			'location'    => sanitize_text_field($data['location']     ?? ''),
			'diet_info'   => sanitize_text_field($data['diet_info']    ?? ''),
			'allergens'   => sanitize_text_field($data['allergens']    ?? ''),
			'sort_order'  => (int) ($data['sort_order'] ?? 0),
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
		$wpdb->delete(Schema::table('meal_template_items'), ['id' => $id]);
	}

	/**
	 * Apply template items to a meal day.
	 * If $replace is true, existing items on that day are removed first.
	 *
	 * @return int Number of items added.
	 */
	public static function apply_to_day(int $template_id, int $meal_day_id, bool $replace = false): int {
		if ( $replace ) {
			global $wpdb;
			$wpdb->delete(Schema::table('meal_items'), ['meal_day_id' => $meal_day_id]);
		}

		$items = self::get_items($template_id);
		$count = 0;

		foreach ( $items as $item ) {
			MealRepository::save_item([
				'id'               => 0,
				'meal_day_id'      => $meal_day_id,
				'meal_type'        => $item->meal_type,
				'time_from'        => $item->time_from,
				'title'            => $item->title,
				'description'      => $item->description,
				'location'         => $item->location,
				'diet_info'        => $item->diet_info,
				'allergens'        => $item->allergens,
				'sort_order'       => $item->sort_order,
				'is_new_today'     => 0,
				'is_updated_today' => 0,
			]);
			$count++;
		}

		return $count;
	}
}
