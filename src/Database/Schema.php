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
			'camps'                  => $wpdb->prefix . 'bm_camps',
			'staff'                  => $wpdb->prefix . 'bm_staff',
			'daily_counts'           => $wpdb->prefix . 'bm_daily_counts',
			'announcements'          => $wpdb->prefix . 'bm_announcements',
			'announcement_camps'     => $wpdb->prefix . 'bm_announcement_camps',
			'sessions'               => $wpdb->prefix . 'bm_sessions',
			'weather_alerts'         => $wpdb->prefix . 'bm_weather_alerts',
			// Schedule module
			'plan_headers'           => $wpdb->prefix . 'bm_plan_headers',
			'plan_items'             => $wpdb->prefix . 'bm_plan_items',
			'plan_item_revisions'    => $wpdb->prefix . 'bm_plan_item_revisions',
			'plan_camps'             => $wpdb->prefix . 'bm_plan_camps',
			// Reservations module
			'resources'              => $wpdb->prefix . 'bm_resources',
			'resource_reservations'  => $wpdb->prefix . 'bm_resource_reservations',
			'resource_blocks'        => $wpdb->prefix . 'bm_resource_blocks',
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

		// ── Daily Counts / Reports (extended with status workflow) ──────────────
		$sql[] = "CREATE TABLE {$p}bm_daily_counts (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id      BIGINT UNSIGNED NOT NULL,
			count_date   DATE            NOT NULL,
			participants INT UNSIGNED    NOT NULL DEFAULT 0,
			staff        INT UNSIGNED    NOT NULL DEFAULT 0,
			workers      INT UNSIGNED    NOT NULL DEFAULT 0,
			notes        TEXT            DEFAULT NULL,
			submitted_by BIGINT UNSIGNED DEFAULT NULL,
			status       VARCHAR(20)     NOT NULL DEFAULT 'none',
			submitted_at DATETIME        DEFAULT NULL,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_camp_date (camp_id, count_date),
			KEY idx_camp   (camp_id),
			KEY idx_date   (count_date),
			KEY idx_status (status)
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

		// ── Weather alerts (manual + IMGW sync) ─────────────────────────────
		$sql[] = "CREATE TABLE {$p}bm_weather_alerts (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title       VARCHAR(255)    NOT NULL,
			message     TEXT            NOT NULL,
			type        VARCHAR(20)     NOT NULL DEFAULT 'info',
			source      VARCHAR(20)     NOT NULL DEFAULT 'manual',
			external_id VARCHAR(100)    DEFAULT NULL,
			is_active   TINYINT(1)      NOT NULL DEFAULT 1,
			is_urgent   TINYINT(1)      NOT NULL DEFAULT 0,
			valid_from  DATETIME        DEFAULT NULL,
			valid_until DATETIME        DEFAULT NULL,
			created_by  BIGINT UNSIGNED DEFAULT NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_active      (is_active),
			KEY idx_until       (valid_until),
			KEY idx_source      (source),
			KEY idx_external_id (external_id)
		) $charset;";

		foreach ( $sql as $statement ) {
			dbDelta($statement);
		}

		// ── Plan dnia (Day Schedule) ──────────────────────────────────────────

		$schedule_sql = [];

		$schedule_sql[] = "CREATE TABLE {$p}bm_plan_headers (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			plan_date   DATE            NOT NULL,
			title       VARCHAR(255)    NOT NULL DEFAULT '',
			is_global   TINYINT(1)      NOT NULL DEFAULT 1,
			status      VARCHAR(20)     NOT NULL DEFAULT 'active',
			created_by  BIGINT UNSIGNED DEFAULT NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_date   (plan_date),
			KEY idx_status (status),
			KEY idx_global (is_global)
		) $charset;";

		$schedule_sql[] = "CREATE TABLE {$p}bm_plan_items (
			id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			plan_id          BIGINT UNSIGNED NOT NULL,
			time_from        VARCHAR(10)     NOT NULL DEFAULT '',
			time_to          VARCHAR(10)     NOT NULL DEFAULT '',
			title            VARCHAR(255)    NOT NULL,
			description      TEXT            DEFAULT NULL,
			category         VARCHAR(30)     NOT NULL DEFAULT 'inne',
			item_status      VARCHAR(20)     NOT NULL DEFAULT 'active',
			is_mandatory     TINYINT(1)      NOT NULL DEFAULT 0,
			sort_order       INT             NOT NULL DEFAULT 0,
			is_new_today     TINYINT(1)      NOT NULL DEFAULT 0,
			is_updated_today TINYINT(1)      NOT NULL DEFAULT 0,
			created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_plan     (plan_id),
			KEY idx_order    (plan_id, sort_order)
		) $charset;";

		$schedule_sql[] = "CREATE TABLE {$p}bm_plan_item_revisions (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			item_id     BIGINT UNSIGNED NOT NULL,
			change_type VARCHAR(20)     NOT NULL DEFAULT 'updated',
			old_data    LONGTEXT        DEFAULT NULL,
			changed_by  BIGINT UNSIGNED DEFAULT NULL,
			changed_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_item (item_id)
		) $charset;";

		$schedule_sql[] = "CREATE TABLE {$p}bm_plan_camps (
			plan_id  BIGINT UNSIGNED NOT NULL,
			camp_id  BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (plan_id, camp_id),
			KEY idx_camp (camp_id)
		) $charset;";

		foreach ( $schedule_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Rezerwacje (Reservations) ─────────────────────────────────────────

		$res_sql = [];

		$res_sql[] = "CREATE TABLE {$p}bm_resources (
			id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name                  VARCHAR(255)    NOT NULL,
			type                  VARCHAR(30)     NOT NULL DEFAULT 'inne',
			description           TEXT            DEFAULT NULL,
			status                VARCHAR(20)     NOT NULL DEFAULT 'active',
			rules                 TEXT            DEFAULT NULL,
			available_from        TIME            NOT NULL DEFAULT '06:00:00',
			available_to          TIME            NOT NULL DEFAULT '22:00:00',
			min_duration_minutes  INT UNSIGNED    NOT NULL DEFAULT 0,
			max_duration_minutes  INT UNSIGNED    NOT NULL DEFAULT 0,
			min_advance_hours     INT UNSIGNED    NOT NULL DEFAULT 0,
			max_advance_days      INT UNSIGNED    NOT NULL DEFAULT 30,
			cancel_advance_hours  INT UNSIGNED    NOT NULL DEFAULT 0,
			max_reservations_per_camp INT UNSIGNED NOT NULL DEFAULT 0,
			is_blocked            TINYINT(1)      NOT NULL DEFAULT 0,
			block_reason          VARCHAR(255)    NOT NULL DEFAULT '',
			block_from            DATETIME        DEFAULT NULL,
			block_to              DATETIME        DEFAULT NULL,
			created_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_status (status),
			KEY idx_type   (type)
		) $charset;";

		$res_sql[] = "CREATE TABLE {$p}bm_resource_reservations (
			id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			resource_id    BIGINT UNSIGNED NOT NULL,
			camp_id        BIGINT UNSIGNED NOT NULL,
			staff_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
			res_date       DATE            NOT NULL,
			start_time     TIME            NOT NULL,
			end_time       TIME            NOT NULL,
			purpose        TEXT            DEFAULT NULL,
			status         VARCHAR(20)     NOT NULL DEFAULT 'pending',
			admin_comment  TEXT            DEFAULT NULL,
			created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_resource (resource_id),
			KEY idx_camp     (camp_id),
			KEY idx_date     (res_date),
			KEY idx_status   (status),
			KEY idx_slot     (resource_id, res_date, start_time, end_time)
		) $charset;";

		$res_sql[] = "CREATE TABLE {$p}bm_resource_blocks (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			resource_id BIGINT UNSIGNED NOT NULL,
			reason      VARCHAR(255)    NOT NULL DEFAULT '',
			block_from  DATETIME        NOT NULL,
			block_to    DATETIME        NOT NULL,
			created_by  BIGINT UNSIGNED DEFAULT NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_resource (resource_id),
			KEY idx_range    (resource_id, block_from, block_to)
		) $charset;";

		foreach ( $res_sql as $statement ) {
			dbDelta($statement);
		}
	}
}
