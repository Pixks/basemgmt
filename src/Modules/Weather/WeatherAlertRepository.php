<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Weather;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * CRUD for weather alerts.
 * Source 'manual' = admin-created; source 'imgw' = synced from IMGW API.
 * Only manual alerts can be deleted or edited by admin via form.
 * IMGW alerts are managed exclusively by the sync service.
 */
final class WeatherAlertRepository {

	public const TYPE_INFO    = 'info';
	public const TYPE_WARNING = 'warning';
	public const TYPE_DANGER  = 'danger';

	public const SOURCE_MANUAL = 'manual';
	public const SOURCE_IMGW   = 'imgw';

	public static function get_active(): array {
		global $wpdb;
		$t   = Schema::table('weather_alerts');
		$now = gmdate('Y-m-d H:i:s');

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$t}`
				 WHERE is_active = 1
				   AND (valid_until IS NULL OR valid_until > %s)
				   AND (valid_from  IS NULL OR valid_from  <= %s)
				 ORDER BY is_urgent DESC, source DESC, created_at DESC",
				$now,
				$now
			)
		) ?: [];
	}

	public static function get_all(int $limit = 50, int $offset = 0): array {
		global $wpdb;
		$t = Schema::table('weather_alerts');

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$t}` ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		) ?: [];
	}

	public static function get_by_id(int $id): ?object {
		global $wpdb;
		$t = Schema::table('weather_alerts');
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM `{$t}` WHERE id = %d LIMIT 1", $id)
		) ?: null;
	}

	public static function get_by_external_id(string $external_id, string $source = self::SOURCE_IMGW): ?object {
		global $wpdb;
		$t = Schema::table('weather_alerts');
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$t}` WHERE external_id = %s AND source = %s LIMIT 1",
				$external_id,
				$source
			)
		) ?: null;
	}

	public static function create(array $data): int|false {
		global $wpdb;

		$result = $wpdb->insert(
			Schema::table('weather_alerts'),
			[
				'title'       => sanitize_text_field($data['title']    ?? ''),
				'message'     => wp_kses_post($data['message']         ?? ''),
				'type'        => sanitize_key($data['type']            ?? self::TYPE_INFO),
				'source'      => sanitize_key($data['source']         ?? self::SOURCE_MANUAL),
				'external_id' => isset($data['external_id']) ? sanitize_text_field($data['external_id']) : null,
				'is_active'   => (int) ($data['is_active']             ?? 1),
				'is_urgent'   => (int) ($data['is_urgent']             ?? 0),
				'valid_from'  => $data['valid_from']                   ?? null,
				'valid_until' => $data['valid_until']                  ?? null,
				'created_by'  => get_current_user_id()                 ?: null,
			],
			['%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d']
		);

		return $result ? (int) $wpdb->insert_id : false;
	}

	public static function update(int $id, array $data): bool {
		global $wpdb;

		return $wpdb->update(
			Schema::table('weather_alerts'),
			[
				'title'       => sanitize_text_field($data['title']    ?? ''),
				'message'     => wp_kses_post($data['message']         ?? ''),
				'type'        => sanitize_key($data['type']            ?? self::TYPE_INFO),
				'is_active'   => (int) ($data['is_active']             ?? 1),
				'is_urgent'   => (int) ($data['is_urgent']             ?? 0),
				'valid_from'  => $data['valid_from']                   ?? null,
				'valid_until' => $data['valid_until']                  ?? null,
				'updated_at'  => gmdate('Y-m-d H:i:s'),
			],
			['id' => $id],
			['%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s'],
			['%d']
		) !== false;
	}

	/**
	 * Upsert an IMGW-sourced alert by external_id.
	 * Creates if not exists; updates if data changed.
	 */
	public static function upsert_imgw(array $data): int|false {
		global $wpdb;

		$external_id = sanitize_text_field($data['external_id'] ?? '');
		if ( ! $external_id ) {
			return false;
		}

		$existing = self::get_by_external_id($external_id);
		$now      = gmdate('Y-m-d H:i:s');

		if ( $existing ) {
			$wpdb->update(
				Schema::table('weather_alerts'),
				[
					'title'       => sanitize_text_field($data['title']    ?? ''),
					'message'     => sanitize_textarea_field($data['message'] ?? ''),
					'type'        => sanitize_key($data['type']            ?? self::TYPE_INFO),
					'is_active'   => 1,
					'is_urgent'   => (int) ($data['is_urgent']             ?? 0),
					'valid_from'  => $data['valid_from']                   ?? null,
					'valid_until' => $data['valid_until']                  ?? null,
					'updated_at'  => $now,
				],
				['id' => (int) $existing->id],
				['%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s'],
				['%d']
			);
			return (int) $existing->id;
		}

		$result = $wpdb->insert(
			Schema::table('weather_alerts'),
			[
				'title'       => sanitize_text_field($data['title']    ?? ''),
				'message'     => sanitize_textarea_field($data['message'] ?? ''),
				'type'        => sanitize_key($data['type']            ?? self::TYPE_INFO),
				'source'      => self::SOURCE_IMGW,
				'external_id' => $external_id,
				'is_active'   => 1,
				'is_urgent'   => (int) ($data['is_urgent']             ?? 0),
				'valid_from'  => $data['valid_from']                   ?? null,
				'valid_until' => $data['valid_until']                  ?? null,
				'created_by'  => null,
			],
			['%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d']
		);

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Deactivate all IMGW alerts whose external_id is NOT in the given list.
	 * Called after a successful sync to remove stale warnings.
	 *
	 * @param string[] $active_external_ids
	 */
	public static function deactivate_stale_imgw(array $active_external_ids): void {
		global $wpdb;
		$t = Schema::table('weather_alerts');

		if ( empty($active_external_ids) ) {
			// Deactivate all IMGW alerts.
			$wpdb->query(
				"UPDATE `{$t}` SET is_active = 0, updated_at = NOW() WHERE source = 'imgw' AND is_active = 1"
			);
			return;
		}

		$placeholders = implode(',', array_fill(0, count($active_external_ids), '%s'));
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$t}` SET is_active = 0, updated_at = NOW()
				 WHERE source = 'imgw' AND is_active = 1
				   AND external_id NOT IN ({$placeholders})",
				...$active_external_ids
			)
		);
	}

	public static function delete(int $id): bool {
		global $wpdb;
		return (bool) $wpdb->delete(Schema::table('weather_alerts'), ['id' => $id], ['%d']);
	}

	/** Deactivate expired alerts (called by WP-Cron). */
	public static function deactivate_expired(): int {
		global $wpdb;
		$t   = Schema::table('weather_alerts');
		$now = gmdate('Y-m-d H:i:s');

		return (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$t}` SET is_active = 0 WHERE is_active = 1 AND valid_until IS NOT NULL AND valid_until <= %s",
				$now
			)
		);
	}
}
