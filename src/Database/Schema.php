<?php

declare(strict_types=1);

namespace BaseMgmt\Database;

defined('ABSPATH') || exit;

/**
 * Manages all plugin database tables.
 * Use dbDelta() so the schema is always up to date on activation / upgrade.
 */
final class Schema {

	/** @return array<string> Fully-qualified table names */
	public static function table_names(): array {
		global $wpdb;
		return [
			'camps'               => $wpdb->prefix . 'bm_camps',
			'staff'               => $wpdb->prefix . 'bm_staff',
			'daily_counts'        => $wpdb->prefix . 'bm_daily_counts',
			'announcements'       => $wpdb->prefix . 'bm_announcements',
			'announcement_camps'  => $wpdb->prefix . 'bm_announcement_camps',
			'sessions'            => $wpdb->prefix . 'bm_sessions',
		];
	}

	public static function table(string $key): string {
		return self::table_names()[$key] ?? '';
	}

	public static function create_tables(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$p = $wpdb->prefix;

		$sql = [];

		// ── Camps ─────────────────────────────────────────────────────────────
		$sql[] = "CREATE TABLE {$p}bm_camps (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name        VARCHAR(255)    NOT NULL,
			start_date  DATE            NOT NULL,
			end_date    DATE            NOT NULL,
			status      VARCHAR(20)     NOT NULL DEFAULT 'active',
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_status (status),
			KEY idx_dates  (start_date, end_date)
		) $charset;";

		// ── Staff (Kadra) ─────────────────────────────────────────────────────
		$sql[] = "CREATE TABLE {$p}bm_staff (
			id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id            BIGINT UNSIGNED NOT NULL,
			first_name         VARCHAR(100)    NOT NULL,
			last_name          VARCHAR(100)    NOT NULL,
			email              VARCHAR(255)    DEFAULT NULL,
			phone              VARCHAR(50)     DEFAULT NULL,
			role_in_camp       VARCHAR(100)    DEFAULT NULL,
			security_code_hash VARCHAR(255)    NOT NULL DEFAULT '',
			is_active          TINYINT(1)      NOT NULL DEFAULT 1,
			failed_attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
			locked_until       DATETIME        DEFAULT NULL,
			last_login         DATETIME        DEFAULT NULL,
			created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp   (camp_id),
			KEY idx_active (is_active)
		) $charset;";

		// ── Daily Counts (history, foundation for Meldunki module) ───────────
		$sql[] = "CREATE TABLE {$p}bm_daily_counts (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id      BIGINT UNSIGNED NOT NULL,
			count_date   DATE            NOT NULL,
			participants INT UNSIGNED    NOT NULL DEFAULT 0,
			staff        INT UNSIGNED    NOT NULL DEFAULT 0,
			workers      INT UNSIGNED    NOT NULL DEFAULT 0,
			notes        TEXT            DEFAULT NULL,
			submitted_by BIGINT UNSIGNED DEFAULT NULL,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_camp_date (camp_id, count_date),
			KEY idx_camp (camp_id),
			KEY idx_date (count_date)
		) $charset;";

		// ── Announcements ─────────────────────────────────────────────────────
		// status: active | pending | expired | archived | draft
		// submitted_camp_id / submitted_staff_id: filled when camp submits
		$sql[] = "CREATE TABLE {$p}bm_announcements (
			id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title                VARCHAR(255)    NOT NULL,
			content              LONGTEXT        NOT NULL,
			status               VARCHAR(20)     NOT NULL DEFAULT 'active',
			is_urgent            TINYINT(1)      NOT NULL DEFAULT 0,
			priority             TINYINT         NOT NULL DEFAULT 0,
			valid_from           DATETIME        NOT NULL,
			valid_until          DATETIME        NOT NULL,
			is_global            TINYINT(1)      NOT NULL DEFAULT 1,
			attachment_url       VARCHAR(500)    DEFAULT NULL,
			submitted_camp_id    BIGINT UNSIGNED DEFAULT NULL,
			submitted_staff_id   BIGINT UNSIGNED DEFAULT NULL,
			approved_by_user_id  BIGINT UNSIGNED DEFAULT NULL,
			approved_at          DATETIME        DEFAULT NULL,
			created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_status   (status),
			KEY idx_global   (is_global),
			KEY idx_valid    (valid_from, valid_until),
			KEY idx_priority (priority)
		) $charset;";

		// ── Announcement → Camp (non-global targeting) ────────────────────────
		$sql[] = "CREATE TABLE {$p}bm_announcement_camps (
			announcement_id BIGINT UNSIGNED NOT NULL,
			camp_id         BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (announcement_id, camp_id),
			KEY idx_camp (camp_id)
		) $charset;";

		// ── Frontend sessions ─────────────────────────────────────────────────
		$sql[] = "CREATE TABLE {$p}bm_sessions (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			token      VARCHAR(64)     NOT NULL,
			staff_id   BIGINT UNSIGNED NOT NULL,
			camp_id    BIGINT UNSIGNED NOT NULL,
			ip_address VARCHAR(45)     DEFAULT NULL,
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			expires_at DATETIME        NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_token (token),
			KEY idx_expires (expires_at),
			KEY idx_staff   (staff_id)
		) $charset;";

		foreach ( $sql as $statement ) {
			dbDelta($statement);
		}
	}
}
