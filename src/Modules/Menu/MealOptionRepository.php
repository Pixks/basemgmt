<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Menu;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Manages predefined meal diets and serving locations.
 *
 * Tables:
 *   bm_meal_diets     – diet options (e.g. wegetariańska, bezglutenowa)
 *   bm_meal_locations – serving location options (e.g. stołówka, wydawalnia A)
 */
final class MealOptionRepository {

	// ── Diets ─────────────────────────────────────────────────────────────────

	public static function get_all_diets(): array {
		global $wpdb;
		$t = Schema::table('meal_diets');
		return $wpdb->get_results("SELECT * FROM {$t} ORDER BY sort_order ASC, id ASC") ?: [];
	}

	public static function get_diet(int $id): ?object {
		global $wpdb;
		$t = Schema::table('meal_diets');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	public static function save_diet(array $data): int {
		global $wpdb;
		$t  = Schema::table('meal_diets');
		$id = (int) ($data['id'] ?? 0);

		$payload = [
			'name'       => sanitize_text_field($data['name']       ?? ''),
			'sort_order' => (int) ($data['sort_order'] ?? 0),
		];

		if ( $id ) {
			$wpdb->update($t, $payload, ['id' => $id]);
		} else {
			$wpdb->insert($t, $payload);
			$id = (int) $wpdb->insert_id;
		}
		return $id;
	}

	public static function delete_diet(int $id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('meal_diets'), ['id' => $id]);
	}

	/** Returns diet names indexed by id for use in select lists. */
	public static function get_diet_names(): array {
		$out = [];
		foreach ( self::get_all_diets() as $d ) {
			$out[(int) $d->id] = $d->name;
		}
		return $out;
	}

	// ── Locations ─────────────────────────────────────────────────────────────

	public static function get_all_locations(): array {
		global $wpdb;
		$t = Schema::table('meal_locations');
		return $wpdb->get_results("SELECT * FROM {$t} ORDER BY sort_order ASC, id ASC") ?: [];
	}

	public static function get_location(int $id): ?object {
		global $wpdb;
		$t = Schema::table('meal_locations');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	public static function save_location(array $data): int {
		global $wpdb;
		$t  = Schema::table('meal_locations');
		$id = (int) ($data['id'] ?? 0);

		$payload = [
			'name'       => sanitize_text_field($data['name']       ?? ''),
			'sort_order' => (int) ($data['sort_order'] ?? 0),
		];

		if ( $id ) {
			$wpdb->update($t, $payload, ['id' => $id]);
		} else {
			$wpdb->insert($t, $payload);
			$id = (int) $wpdb->insert_id;
		}
		return $id;
	}

	public static function delete_location(int $id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('meal_locations'), ['id' => $id]);
	}

	/** Returns location names indexed by id for use in select lists. */
	public static function get_location_names(): array {
		$out = [];
		foreach ( self::get_all_locations() as $l ) {
			$out[(int) $l->id] = $l->name;
		}
		return $out;
	}
}
