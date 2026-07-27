<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Forms;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access for Formularze (Forms) module.
 *
 * Tables:
 *   bm_forms       – form definitions
 *   bm_form_fields – field definitions per form
 *   bm_form_camps  – camp visibility pivot (non-global forms)
 */
final class FormRepository {

	// ── Field type constants ──────────────────────────────────────────────────

	public const FIELD_TYPES = [
		'text'     => 'Tekst',
		'textarea' => 'Długi tekst',
		'number'   => 'Liczba',
		'email'    => 'E-mail',
		'tel'      => 'Telefon',
		'select'   => 'Lista wyboru',
		'radio'    => 'Radio',
		'checkbox' => 'Pola wyboru',
		'date'     => 'Data',
		'file'     => 'Plik',
	];

	// ── Category constants ────────────────────────────────────────────────────

	public const CATEGORIES = [
		'techniczne'     => 'Techniczne',
		'organizacyjne'  => 'Organizacyjne',
		'medyczne'       => 'Medyczne',
		'magazynowe'     => 'Magazynowe',
		'inne'           => 'Inne',
	];

	// ── Forms CRUD ────────────────────────────────────────────────────────────

	public static function get_all(array $filters = []): array {
		global $wpdb;
		$t     = Schema::table('forms');
		$where = ['1=1'];
		$vals  = [];

		if ( isset($filters['status']) ) {
			$where[] = 'status = %s';
			$vals[]  = $filters['status'];
		}
		if ( ! empty($filters['category']) ) {
			$where[] = 'category = %s';
			$vals[]  = $filters['category'];
		}

		$sql = "SELECT * FROM {$t} WHERE " . implode(' AND ', $where)
			. ' ORDER BY is_pinned DESC, sort_order ASC, id ASC';

		if ( ! empty($vals) ) {
			$sql = $wpdb->prepare($sql, ...$vals);
		}

		return $wpdb->get_results($sql) ?: [];
	}

	public static function get(int $id): ?object {
		global $wpdb;
		$t = Schema::table('forms');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	public static function save(array $data): int {
		global $wpdb;
		$t       = Schema::table('forms');
		$id      = (int) ($data['id'] ?? 0);
		$payload = [
			'name'        => sanitize_text_field($data['name'] ?? ''),
			'description' => sanitize_textarea_field($data['description'] ?? ''),
			'category'    => sanitize_key($data['category'] ?? 'inne'),
			'status'      => sanitize_key($data['status'] ?? 'active'),
			'is_global'   => (int) ($data['is_global'] ?? 1),
			'sort_order'  => (int) ($data['sort_order'] ?? 0),
			'is_pinned'   => (int) ($data['is_pinned'] ?? 0),
			'info_before' => wp_kses_post($data['info_before'] ?? ''),
			'info_after'  => wp_kses_post($data['info_after'] ?? ''),
		];

		if ( $id ) {
			$wpdb->update($t, $payload, ['id' => $id]);
		} else {
			$payload['created_by'] = get_current_user_id();
			$wpdb->insert($t, $payload);
			$id = (int) $wpdb->insert_id;
		}

		return $id;
	}

	public static function delete(int $id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('form_fields'), ['form_id' => $id]);
		$wpdb->delete(Schema::table('form_camps'), ['form_id' => $id]);
		$wpdb->delete(Schema::table('forms'), ['id' => $id]);
	}

	// ── Form visibility (camp assignments) ────────────────────────────────────

	/** @return int[] */
	public static function get_assigned_camps(int $form_id): array {
		global $wpdb;
		$t = Schema::table('form_camps');
		return array_map(
			'intval',
			$wpdb->get_col($wpdb->prepare("SELECT camp_id FROM {$t} WHERE form_id = %d", $form_id))
		);
	}

	public static function set_assigned_camps(int $form_id, array $camp_ids): void {
		global $wpdb;
		$t = Schema::table('form_camps');
		$wpdb->delete($t, ['form_id' => $form_id]);
		foreach ( array_map('intval', $camp_ids) as $camp_id ) {
			if ( $camp_id > 0 ) {
				$wpdb->insert($t, ['form_id' => $form_id, 'camp_id' => $camp_id]);
			}
		}
	}

	// ── Field CRUD ────────────────────────────────────────────────────────────

	public static function get_fields(int $form_id): array {
		global $wpdb;
		$t = Schema::table('form_fields');
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE form_id = %d ORDER BY sort_order ASC, id ASC",
				$form_id
			)
		) ?: [];
	}

	public static function save_field(array $data): int {
		global $wpdb;
		$t       = Schema::table('form_fields');
		$id      = (int) ($data['id'] ?? 0);
		$options = $data['options'] ?? [];
		if ( is_string($options) ) {
			$options = array_filter(array_map('trim', explode("\n", $options)));
		}

		$payload = [
			'form_id'       => (int) $data['form_id'],
			'label'         => sanitize_text_field($data['label'] ?? ''),
			'field_key'     => sanitize_key($data['field_key'] ?? ''),
			'type'          => sanitize_key($data['type'] ?? 'text'),
			'is_required'   => (int) ($data['is_required'] ?? 0),
			'placeholder'   => sanitize_text_field($data['placeholder'] ?? ''),
			'help_text'     => sanitize_text_field($data['help_text'] ?? ''),
			'options_json'  => wp_json_encode(array_values($options)),
			'default_value' => sanitize_text_field($data['default_value'] ?? ''),
			'validation'    => sanitize_text_field($data['validation'] ?? ''),
			'sort_order'    => (int) ($data['sort_order'] ?? 0),
		];

		if ( $id ) {
			$wpdb->update($t, $payload, ['id' => $id]);
		} else {
			$wpdb->insert($t, $payload);
			$id = (int) $wpdb->insert_id;
		}

		return $id;
	}

	public static function delete_field(int $id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('form_fields'), ['id' => $id]);
	}

	public static function delete_all_fields(int $form_id): void {
		global $wpdb;
		$wpdb->delete(Schema::table('form_fields'), ['form_id' => $form_id]);
	}

	// ── Frontend access ───────────────────────────────────────────────────────

	/**
	 * Get active forms visible to a specific camp.
	 * Returns global forms + forms explicitly assigned to this camp.
	 */
	public static function get_for_camp(int $camp_id): array {
		global $wpdb;
		$tf  = Schema::table('forms');
		$tfc = Schema::table('form_camps');

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.* FROM {$tf} f
				 WHERE f.status = 'active'
				   AND (
				         f.is_global = 1
				         OR EXISTS (
				               SELECT 1 FROM {$tfc} fc
				               WHERE fc.form_id = f.id AND fc.camp_id = %d
				             )
				       )
				 ORDER BY f.is_pinned DESC, f.sort_order ASC, f.id ASC",
				$camp_id
			)
		) ?: [];
	}

	/** Get a specific active form visible to a camp. Returns null if not accessible. */
	public static function get_for_camp_checked(int $form_id, int $camp_id): ?object {
		global $wpdb;
		$tf  = Schema::table('forms');
		$tfc = Schema::table('form_camps');

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT f.* FROM {$tf} f
				 WHERE f.id = %d AND f.status = 'active'
				   AND (
				         f.is_global = 1
				         OR EXISTS (
				               SELECT 1 FROM {$tfc} fc
				               WHERE fc.form_id = f.id AND fc.camp_id = %d
				             )
				       )",
				$form_id,
				$camp_id
			)
		) ?: null;
	}

	// ── Stats ─────────────────────────────────────────────────────────────────

	public static function count_active(): int {
		global $wpdb;
		$t = Schema::table('forms');
		return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'active'");
	}
}
