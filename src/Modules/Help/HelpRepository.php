<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Help;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access for Pomoc (Help) module.
 *
 * Single table bm_help_articles holds all content types.
 * Filtering by type/category/status/pinned covers all admin and frontend views.
 */
final class HelpRepository {

	// ── Type constants ────────────────────────────────────────────────────────

	public const TYPES = [
		'article'     => 'Artykuł',
		'faq'         => 'FAQ',
		'contact'     => 'Kontakt',
		'procedure'   => 'Procedura',
		'instruction' => 'Instrukcja',
	];

	public const STATUS_PUBLISHED = 'published';
	public const STATUS_DRAFT     = 'draft';

	// ── CRUD ──────────────────────────────────────────────────────────────────

	public static function get_all(array $filters = []): array {
		global $wpdb;
		$t     = Schema::table('help_articles');
		$where = ['1=1'];
		$vals  = [];

		if ( ! empty($filters['type']) ) {
			$where[] = 'type = %s';
			$vals[]  = $filters['type'];
		}
		if ( ! empty($filters['category']) ) {
			$where[] = 'category = %s';
			$vals[]  = $filters['category'];
		}
		if ( ! empty($filters['status']) ) {
			$where[] = 'status = %s';
			$vals[]  = $filters['status'];
		}
		if ( ! empty($filters['is_pinned']) ) {
			$where[] = 'is_pinned = 1';
		}
		if ( ! empty($filters['search']) ) {
			$like    = '%' . $wpdb->esc_like($filters['search']) . '%';
			$where[] = '(title LIKE %s OR content LIKE %s OR excerpt LIKE %s)';
			$vals[]  = $like;
			$vals[]  = $like;
			$vals[]  = $like;
		}

		$sql = "SELECT * FROM {$t} WHERE " . implode(' AND ', $where)
			. ' ORDER BY is_pinned DESC, is_alarm DESC, sort_order ASC, id ASC';

		if ( ! empty($vals) ) {
			$sql = $wpdb->prepare($sql, ...$vals);
		}

		return $wpdb->get_results($sql) ?: [];
	}

	public static function get(int $id): ?object {
		global $wpdb;
		$t = Schema::table('help_articles');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	public static function save(array $data): int {
		global $wpdb;
		$t       = Schema::table('help_articles');
		$id      = (int) ($data['id'] ?? 0);
		$payload = [
			'title'      => sanitize_text_field($data['title'] ?? ''),
			'content'    => wp_kses_post($data['content'] ?? ''),
			'excerpt'    => sanitize_textarea_field($data['excerpt'] ?? ''),
			'category'   => sanitize_text_field($data['category'] ?? ''),
			'type'       => sanitize_key($data['type'] ?? 'article'),
			'status'     => sanitize_key($data['status'] ?? self::STATUS_PUBLISHED),
			'is_pinned'  => (int) ($data['is_pinned'] ?? 0),
			'is_alarm'   => (int) ($data['is_alarm'] ?? 0),
			'sort_order' => (int) ($data['sort_order'] ?? 0),
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
		$wpdb->delete(Schema::table('help_articles'), ['id' => $id]);
	}

	// ── Convenience helpers ───────────────────────────────────────────────────

	public static function get_published(array $filters = []): array {
		return self::get_all(array_merge($filters, ['status' => self::STATUS_PUBLISHED]));
	}

	public static function count_important(): int {
		global $wpdb;
		$t = Schema::table('help_articles');
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$t} WHERE status = 'published' AND (is_pinned = 1 OR is_alarm = 1)"
		);
	}

	/** All distinct category values used in published articles. */
	public static function get_categories(): array {
		global $wpdb;
		$t = Schema::table('help_articles');
		return $wpdb->get_col(
			"SELECT DISTINCT category FROM {$t} WHERE category != '' AND status = 'published' ORDER BY category ASC"
		) ?: [];
	}
}
