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
			'camp_cases'             => $wpdb->prefix . 'bm_camp_cases',
			'camp_case_history'      => $wpdb->prefix . 'bm_camp_case_history',
			'camp_organizers'        => $wpdb->prefix . 'bm_camp_organizers',
			'camp_checklist_items'   => $wpdb->prefix . 'bm_camp_checklist_items',
			'camp_workflow_events'   => $wpdb->prefix . 'bm_camp_workflow_events',
			'camp_prearrival'        => $wpdb->prefix . 'bm_camp_prearrival',
			'camp_documents'         => $wpdb->prefix . 'bm_camp_documents',
			'camp_document_versions' => $wpdb->prefix . 'bm_camp_document_versions',
			'camp_payment_schedules' => $wpdb->prefix . 'bm_camp_payment_schedules',
			'camp_payments'          => $wpdb->prefix . 'bm_camp_payments',
			'camp_actual_stays'      => $wpdb->prefix . 'bm_camp_actual_stays',
			'camp_actual_meals'      => $wpdb->prefix . 'bm_camp_actual_meals',
			'camp_service_usages'    => $wpdb->prefix . 'bm_camp_service_usages',
			'camp_pricing_tables'    => $wpdb->prefix . 'bm_camp_pricing_tables',
			'camp_pricing_rules'     => $wpdb->prefix . 'bm_camp_pricing_rules',
			'camp_settlements'       => $wpdb->prefix . 'bm_camp_settlements',
			'camp_settlement_lines'  => $wpdb->prefix . 'bm_camp_settlement_lines',
			'camp_settlement_issues' => $wpdb->prefix . 'bm_camp_settlement_issues',
			'camp_closures'          => $wpdb->prefix . 'bm_camp_closures',
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
			// Meal menu module
			'meal_days'              => $wpdb->prefix . 'bm_meal_days',
			'meal_items'             => $wpdb->prefix . 'bm_meal_items',
			// Communication module
			'conv_threads'           => $wpdb->prefix . 'bm_conv_threads',
			'conv_messages'          => $wpdb->prefix . 'bm_conv_messages',
			// Help module
			'help_articles'          => $wpdb->prefix . 'bm_help_articles',
			// Forms & Submissions module
			'forms'                  => $wpdb->prefix . 'bm_forms',
			'form_fields'            => $wpdb->prefix . 'bm_form_fields',
			'form_camps'             => $wpdb->prefix . 'bm_form_camps',
			'submissions'            => $wpdb->prefix . 'bm_submissions',
			'submission_attachments' => $wpdb->prefix . 'bm_submission_attachments',
			'submission_history'     => $wpdb->prefix . 'bm_submission_history',
			// Operation Logs
			'operation_logs'         => $wpdb->prefix . 'bm_operation_logs',
			// Daily Plan Templates
			'plan_templates'         => $wpdb->prefix . 'bm_plan_templates',
			'plan_template_items'    => $wpdb->prefix . 'bm_plan_template_items',
			// Meal Options
			'meal_diets'             => $wpdb->prefix . 'bm_meal_diets',
			'meal_diet_costs'        => $wpdb->prefix . 'bm_meal_diet_costs',
			'meal_locations'         => $wpdb->prefix . 'bm_meal_locations',
			// Meal Templates
			'meal_templates'         => $wpdb->prefix . 'bm_meal_templates',
			'meal_template_items'    => $wpdb->prefix . 'bm_meal_template_items',
			// Organizacja module
			'doc_templates'          => $wpdb->prefix . 'bm_doc_templates',
			'doc_library'            => $wpdb->prefix . 'bm_doc_library',
			'payment_packages'       => $wpdb->prefix . 'bm_payment_packages',
			'payment_package_lines'  => $wpdb->prefix . 'bm_payment_package_lines',
			'payment_pkg_accom'      => $wpdb->prefix . 'bm_payment_pkg_accom',
			'payment_pkg_diet_slots' => $wpdb->prefix . 'bm_payment_pkg_diet_slots',
			// Task & declaration tables
			'task_templates'                    => $wpdb->prefix . 'bm_task_templates',
			'camp_declarations'                 => $wpdb->prefix . 'bm_camp_declarations',
			'camp_damages'                      => $wpdb->prefix . 'bm_camp_damages',
			// Accommodation types & per-day declarations
			'accommodation_types'               => $wpdb->prefix . 'bm_accommodation_types',
			'camp_declaration_days'             => $wpdb->prefix . 'bm_camp_declaration_days',
			'camp_declaration_diet_lines'       => $wpdb->prefix . 'bm_camp_declaration_diet_lines',
			'camp_declaration_accommodation_lines' => $wpdb->prefix . 'bm_camp_declaration_accommodation_lines',
			// Equipment issued to camps
			'camp_equipment'                    => $wpdb->prefix . 'bm_camp_equipment',
			// Declaration documents (Org → Deklaracje)
			'decl_templates'                    => $wpdb->prefix . 'bm_decl_templates',
			'camp_decl_docs'                    => $wpdb->prefix . 'bm_camp_decl_docs',
			// Document attachments (all parent types)
			'doc_attachments'                   => $wpdb->prefix . 'bm_doc_attachments',
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

		$sql[] = "CREATE TABLE {$p}bm_camp_cases (
			id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id              BIGINT UNSIGNED NOT NULL,
			process_stage        VARCHAR(40)     NOT NULL DEFAULT 'inquiry',
			needs_attention      TINYINT(1)      NOT NULL DEFAULT 0,
			manual_attention     TINYINT(1)      NOT NULL DEFAULT 0,
			risk_level           VARCHAR(20)     NOT NULL DEFAULT 'low',
			owner_user_id        BIGINT UNSIGNED DEFAULT NULL,
			next_action_due_date DATE            DEFAULT NULL,
			notes                TEXT            DEFAULT NULL,
			readiness_notes      TEXT            DEFAULT NULL,
			created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_camp (camp_id),
			KEY idx_stage (process_stage),
			KEY idx_attention (needs_attention),
			KEY idx_risk (risk_level)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_case_history (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id     BIGINT UNSIGNED NOT NULL,
			old_stage   VARCHAR(40)     NOT NULL DEFAULT '',
			new_stage   VARCHAR(40)     NOT NULL,
			changed_by  BIGINT UNSIGNED DEFAULT NULL,
			change_note TEXT            DEFAULT NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id),
			KEY idx_stage (new_stage)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_organizers (
			id                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id                  BIGINT UNSIGNED NOT NULL,
			organization_name        VARCHAR(255)    NOT NULL DEFAULT '',
			contact_person           VARCHAR(255)    NOT NULL DEFAULT '',
			contact_email            VARCHAR(255)    NOT NULL DEFAULT '',
			contact_phone            VARCHAR(50)     NOT NULL DEFAULT '',
			billing_name             VARCHAR(255)    NOT NULL DEFAULT '',
			billing_tax_id           VARCHAR(50)     NOT NULL DEFAULT '',
			billing_address          TEXT            DEFAULT NULL,
			settlement_contact_name  VARCHAR(255)    NOT NULL DEFAULT '',
			settlement_contact_email VARCHAR(255)    NOT NULL DEFAULT '',
			settlement_contact_phone VARCHAR(50)     NOT NULL DEFAULT '',
			notes                    TEXT            DEFAULT NULL,
			created_at               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_camp (camp_id),
			KEY idx_org_name (organization_name)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_checklist_items (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id      BIGINT UNSIGNED NOT NULL,
			party        VARCHAR(20)     NOT NULL DEFAULT 'shared',
			label        VARCHAR(255)    NOT NULL,
			description  TEXT            DEFAULT NULL,
			status       VARCHAR(20)     NOT NULL DEFAULT 'pending',
			priority     VARCHAR(20)     NOT NULL DEFAULT 'normal',
			assigned_to  VARCHAR(255)    NOT NULL DEFAULT '',
			due_date     DATE            DEFAULT NULL,
			comment      TEXT            DEFAULT NULL,
			sort_order   INT             NOT NULL DEFAULT 0,
			completed_at DATETIME        DEFAULT NULL,
			completed_by BIGINT UNSIGNED DEFAULT NULL,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id),
			KEY idx_status (status),
			KEY idx_priority (priority),
			KEY idx_due_date (due_date)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_workflow_events (
			id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id          BIGINT UNSIGNED NOT NULL,
			event_key        VARCHAR(120)    NOT NULL,
			event_type       VARCHAR(40)     NOT NULL,
			severity         VARCHAR(20)     NOT NULL DEFAULT 'warning',
			status           VARCHAR(20)     NOT NULL DEFAULT 'open',
			title            VARCHAR(255)    NOT NULL,
			description      TEXT            DEFAULT NULL,
			suggested_action TEXT            DEFAULT NULL,
			draft_message    TEXT            DEFAULT NULL,
			reminder_date    DATE            DEFAULT NULL,
			source_stage     VARCHAR(40)     NOT NULL DEFAULT '',
			metadata_json    LONGTEXT        DEFAULT NULL,
			resolved_at      DATETIME        DEFAULT NULL,
			created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_camp_event (camp_id, event_key),
			KEY idx_camp (camp_id),
			KEY idx_status (status),
			KEY idx_type (event_type),
			KEY idx_reminder (reminder_date)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_prearrival (
			id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id               BIGINT UNSIGNED NOT NULL,
			arrival_date          DATE            DEFAULT NULL,
			arrival_time          VARCHAR(5)      DEFAULT NULL,
			departure_date        DATE            DEFAULT NULL,
			departure_time        VARCHAR(5)      DEFAULT NULL,
			declared_participants INT UNSIGNED    NOT NULL DEFAULT 0,
			declared_staff        INT UNSIGNED    NOT NULL DEFAULT 0,
			declared_support      INT UNSIGNED    NOT NULL DEFAULT 0,
			dietary_requirements  TEXT            DEFAULT NULL,
			allergens             TEXT            DEFAULT NULL,
			infrastructure_plan   TEXT            DEFAULT NULL,
			additional_needs      TEXT            DEFAULT NULL,
			invoice_details       TEXT            DEFAULT NULL,
			authorized_contacts   TEXT            DEFAULT NULL,
			created_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_camp (camp_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_documents (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id         BIGINT UNSIGNED NOT NULL,
			document_type   VARCHAR(40)     NOT NULL DEFAULT 'contract',
			doc_category    VARCHAR(20)     NOT NULL DEFAULT 'document',
			title           VARCHAR(255)    NOT NULL,
			status          VARCHAR(20)     NOT NULL DEFAULT 'draft',
			template_id     BIGINT UNSIGNED DEFAULT NULL,
			html_content    LONGTEXT        DEFAULT NULL,
			file_id         BIGINT UNSIGNED DEFAULT NULL,
			file_url        VARCHAR(500)    NOT NULL DEFAULT '',
			sent_at         DATETIME        DEFAULT NULL,
			sent_token      VARCHAR(64)     NOT NULL DEFAULT '',
			signed_at       DATETIME        DEFAULT NULL,
			locked          TINYINT(1)      NOT NULL DEFAULT 0,
			responsible_user BIGINT UNSIGNED DEFAULT NULL,
			due_date        DATE            DEFAULT NULL,
			current_version INT UNSIGNED    NOT NULL DEFAULT 1,
			created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id),
			KEY idx_status (status),
			KEY idx_category (doc_category)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_document_versions (
			id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			document_id    BIGINT UNSIGNED NOT NULL,
			camp_id        BIGINT UNSIGNED NOT NULL,
			version_number INT UNSIGNED    NOT NULL DEFAULT 1,
			file_url       VARCHAR(500)    NOT NULL DEFAULT '',
			change_summary TEXT            DEFAULT NULL,
			created_by     BIGINT UNSIGNED DEFAULT NULL,
			created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_document (document_id),
			KEY idx_camp (camp_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_payment_schedules (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id         BIGINT UNSIGNED NOT NULL,
			payment_type    VARCHAR(30)     NOT NULL DEFAULT 'deposit',
			label           VARCHAR(255)    NOT NULL,
			amount          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			amount_type     VARCHAR(10)     NOT NULL DEFAULT 'fixed',
			discount        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			discount_type   VARCHAR(10)     NOT NULL DEFAULT 'fixed',
			due_date        DATE            DEFAULT NULL,
			status          VARCHAR(20)     NOT NULL DEFAULT 'expected',
			description     TEXT            DEFAULT NULL,
			created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id),
			KEY idx_status (status),
			KEY idx_due_date (due_date)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_payments (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id         BIGINT UNSIGNED NOT NULL,
			schedule_id     BIGINT UNSIGNED DEFAULT NULL,
			payment_date    DATE            DEFAULT NULL,
			amount          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			method          VARCHAR(30)     NOT NULL DEFAULT 'transfer',
			reference       VARCHAR(255)    NOT NULL DEFAULT '',
			notes           TEXT            DEFAULT NULL,
			created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id),
			KEY idx_schedule (schedule_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_actual_stays (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id       BIGINT UNSIGNED NOT NULL,
			stay_date     DATE            NOT NULL,
			participants  INT UNSIGNED    NOT NULL DEFAULT 0,
			staff         INT UNSIGNED    NOT NULL DEFAULT 0,
			other_groups  INT UNSIGNED    NOT NULL DEFAULT 0,
			person_nights INT UNSIGNED    NOT NULL DEFAULT 0,
			notes         TEXT            DEFAULT NULL,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_camp_date (camp_id, stay_date),
			KEY idx_camp (camp_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_actual_meals (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id      BIGINT UNSIGNED NOT NULL,
			meal_date    DATE            NOT NULL,
			meal_type    VARCHAR(30)     NOT NULL DEFAULT 'other',
			quantity     INT UNSIGNED    NOT NULL DEFAULT 0,
			diet_type    VARCHAR(60)     NOT NULL DEFAULT '',
			notes        TEXT            DEFAULT NULL,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id),
			KEY idx_meal_date (meal_date)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_service_usages (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id       BIGINT UNSIGNED NOT NULL,
			service_type  VARCHAR(40)     NOT NULL DEFAULT 'resource',
			resource_name VARCHAR(255)    NOT NULL DEFAULT '',
			usage_date    DATE            DEFAULT NULL,
			quantity      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			unit          VARCHAR(30)     NOT NULL DEFAULT 'unit',
			notes         TEXT            DEFAULT NULL,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id),
			KEY idx_usage_date (usage_date)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_pricing_tables (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name        VARCHAR(255)    NOT NULL,
			status      VARCHAR(20)     NOT NULL DEFAULT 'active',
			valid_from  DATE            DEFAULT NULL,
			valid_to    DATE            DEFAULT NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_pricing_rules (
			id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id            BIGINT UNSIGNED NOT NULL,
			pricing_table_id   BIGINT UNSIGNED DEFAULT NULL,
			rule_type          VARCHAR(40)     NOT NULL DEFAULT 'person_night',
			participant_group  VARCHAR(40)     NOT NULL DEFAULT '',
			season_name        VARCHAR(60)     NOT NULL DEFAULT '',
			weekday_mask       VARCHAR(20)     NOT NULL DEFAULT '',
			diet_type          VARCHAR(60)     NOT NULL DEFAULT '',
			resource_name      VARCHAR(255)    NOT NULL DEFAULT '',
			rate               DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			adjustment_reason  TEXT            DEFAULT NULL,
			created_by         BIGINT UNSIGNED DEFAULT NULL,
			created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id),
			KEY idx_table (pricing_table_id),
			KEY idx_rule_type (rule_type)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_settlements (
			id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id            BIGINT UNSIGNED NOT NULL,
			status             VARCHAR(20)     NOT NULL DEFAULT 'draft',
			period_start       DATE            DEFAULT NULL,
			period_end         DATE            DEFAULT NULL,
			total_net          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			total_vat          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			total_gross        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			outstanding_amount DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			notes              TEXT            DEFAULT NULL,
			created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id),
			KEY idx_status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_settlement_lines (
			id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			settlement_id     BIGINT UNSIGNED NOT NULL,
			camp_id           BIGINT UNSIGNED NOT NULL,
			line_type         VARCHAR(40)     NOT NULL DEFAULT 'service',
			description       VARCHAR(255)    NOT NULL,
			quantity          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			unit_price        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			total_amount      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			manual_adjustment TINYINT(1)      NOT NULL DEFAULT 0,
			adjustment_reason TEXT            DEFAULT NULL,
			created_by        BIGINT UNSIGNED DEFAULT NULL,
			created_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_settlement (settlement_id),
			KEY idx_camp (camp_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$p}bm_camp_settlement_issues (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id       BIGINT UNSIGNED NOT NULL,
			settlement_id BIGINT UNSIGNED DEFAULT NULL,
			line_id       BIGINT UNSIGNED DEFAULT NULL,
			status        VARCHAR(20)     NOT NULL DEFAULT 'new',
			title         VARCHAR(255)    NOT NULL,
			description   TEXT            DEFAULT NULL,
			attachments   LONGTEXT        DEFAULT NULL,
			resolved_note TEXT            DEFAULT NULL,
			created_by    BIGINT UNSIGNED DEFAULT NULL,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id),
			KEY idx_status (status)
		) $charset;";

		// satisfaction_score is intended for bounded positive scales (for example 0-10 or 0-100).
		// nps_score remains signed on purpose because valid NPS values range from -100 to 100.
		$sql[] = "CREATE TABLE {$p}bm_camp_closures (
			id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id             BIGINT UNSIGNED NOT NULL,
			status              VARCHAR(20)     NOT NULL DEFAULT 'draft',
			satisfaction_score  TINYINT UNSIGNED NOT NULL DEFAULT 0,
			nps_score           TINYINT NOT NULL DEFAULT 0,
			handover_protocol   TEXT            DEFAULT NULL,
			damage_register     TEXT            DEFAULT NULL,
			follow_up_actions   TEXT            DEFAULT NULL,
			closed_by           BIGINT UNSIGNED DEFAULT NULL,
			closed_at           DATETIME        DEFAULT NULL,
			created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_camp (camp_id),
			KEY idx_status (status)
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
			permanent_lock     TINYINT(1)      NOT NULL DEFAULT 0,
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
			cost_per_reservation  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
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

		// ── Jadłospis (Meal Menu) ─────────────────────────────────────────────────

		$menu_sql = [];

		$menu_sql[] = "CREATE TABLE {$p}bm_meal_days (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			meal_date  DATE            NOT NULL,
			notes      TEXT            DEFAULT NULL,
			status     VARCHAR(20)     NOT NULL DEFAULT 'published',
			created_by BIGINT UNSIGNED DEFAULT NULL,
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_date (meal_date),
			KEY idx_status (status)
		) $charset;";

		$menu_sql[] = "CREATE TABLE {$p}bm_meal_items (
			id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			meal_day_id      BIGINT UNSIGNED NOT NULL,
			meal_type        VARCHAR(30)     NOT NULL DEFAULT 'inne',
			time_from        VARCHAR(10)     NOT NULL DEFAULT '',
			title            VARCHAR(255)    NOT NULL,
			description      TEXT            DEFAULT NULL,
			location         VARCHAR(255)    NOT NULL DEFAULT '',
			diet_info        VARCHAR(255)    NOT NULL DEFAULT '',
			allergens        VARCHAR(255)    NOT NULL DEFAULT '',
			sort_order       INT             NOT NULL DEFAULT 0,
			is_new_today     TINYINT(1)      NOT NULL DEFAULT 0,
			is_updated_today TINYINT(1)      NOT NULL DEFAULT 0,
			created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_day   (meal_day_id),
			KEY idx_order (meal_day_id, sort_order)
		) $charset;";

		foreach ( $menu_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Komunikacja (Communication) ───────────────────────────────────────────

		$comm_sql = [];

		$comm_sql[] = "CREATE TABLE {$p}bm_conv_threads (
			id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id              BIGINT UNSIGNED NOT NULL,
			subject              VARCHAR(255)    NOT NULL,
			status               VARCHAR(20)     NOT NULL DEFAULT 'open',
			priority             VARCHAR(20)     NOT NULL DEFAULT 'normal',
			is_urgent            TINYINT(1)      NOT NULL DEFAULT 0,
			assigned_to          BIGINT UNSIGNED DEFAULT NULL,
			last_message_at      DATETIME        DEFAULT NULL,
			unread_admin         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			unread_camp          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			created_by_staff_id  BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp     (camp_id),
			KEY idx_status   (status),
			KEY idx_priority (priority),
			KEY idx_urgent   (is_urgent),
			KEY idx_last_msg (last_message_at)
		) $charset;";

		$comm_sql[] = "CREATE TABLE {$p}bm_conv_messages (
			id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			thread_id      BIGINT UNSIGNED NOT NULL,
			author_type    VARCHAR(10)     NOT NULL DEFAULT 'staff',
			author_id      BIGINT UNSIGNED NOT NULL DEFAULT 0,
			content        LONGTEXT        NOT NULL,
			is_system      TINYINT(1)      NOT NULL DEFAULT 0,
			attachment_url VARCHAR(500)    NOT NULL DEFAULT '',
			created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_thread (thread_id),
			KEY idx_date   (created_at)
		) $charset;";

		foreach ( $comm_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Pomoc (Help / Knowledge Base) ─────────────────────────────────────────

		$help_sql[] = "CREATE TABLE {$p}bm_help_articles (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title       VARCHAR(255)    NOT NULL,
			content     LONGTEXT        NOT NULL DEFAULT '',
			excerpt     TEXT            NOT NULL DEFAULT '',
			category    VARCHAR(100)    NOT NULL DEFAULT '',
			type        VARCHAR(20)     NOT NULL DEFAULT 'article',
			status      VARCHAR(20)     NOT NULL DEFAULT 'published',
			is_pinned   TINYINT(1)      NOT NULL DEFAULT 0,
			is_alarm    TINYINT(1)      NOT NULL DEFAULT 0,
			sort_order  INT             NOT NULL DEFAULT 0,
			created_by  BIGINT UNSIGNED DEFAULT NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_type     (type),
			KEY idx_status   (status),
			KEY idx_pinned   (is_pinned),
			KEY idx_alarm    (is_alarm),
			KEY idx_category (category),
			KEY idx_order    (sort_order)
		) $charset;";

		foreach ( $help_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Formularze i Zgłoszenia (Forms & Submissions) ─────────────────────────

		$forms_sql = [];

		$forms_sql[] = "CREATE TABLE {$p}bm_forms (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name        VARCHAR(255)    NOT NULL,
			description TEXT            DEFAULT NULL,
			category    VARCHAR(50)     NOT NULL DEFAULT 'inne',
			status      VARCHAR(20)     NOT NULL DEFAULT 'active',
			is_global   TINYINT(1)      NOT NULL DEFAULT 1,
			is_pinned   TINYINT(1)      NOT NULL DEFAULT 0,
			sort_order  INT             NOT NULL DEFAULT 0,
			info_before TEXT            DEFAULT NULL,
			info_after  TEXT            DEFAULT NULL,
			created_by  BIGINT UNSIGNED DEFAULT NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_status   (status),
			KEY idx_global   (is_global),
			KEY idx_pinned   (is_pinned),
			KEY idx_order    (sort_order)
		) $charset;";

		$forms_sql[] = "CREATE TABLE {$p}bm_form_fields (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id       BIGINT UNSIGNED NOT NULL,
			label         VARCHAR(255)    NOT NULL,
			field_key     VARCHAR(100)    NOT NULL,
			type          VARCHAR(20)     NOT NULL DEFAULT 'text',
			is_required   TINYINT(1)      NOT NULL DEFAULT 0,
			placeholder   VARCHAR(255)    NOT NULL DEFAULT '',
			help_text     VARCHAR(500)    NOT NULL DEFAULT '',
			options_json  LONGTEXT        DEFAULT NULL,
			default_value VARCHAR(255)    NOT NULL DEFAULT '',
			validation    VARCHAR(100)    NOT NULL DEFAULT '',
			sort_order    INT             NOT NULL DEFAULT 0,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_form  (form_id),
			KEY idx_order (form_id, sort_order)
		) $charset;";

		$forms_sql[] = "CREATE TABLE {$p}bm_form_camps (
			form_id  BIGINT UNSIGNED NOT NULL,
			camp_id  BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (form_id, camp_id),
			KEY idx_camp (camp_id)
		) $charset;";

		$forms_sql[] = "CREATE TABLE {$p}bm_submissions (
			id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id          BIGINT UNSIGNED NOT NULL,
			camp_id          BIGINT UNSIGNED NOT NULL,
			staff_id         BIGINT UNSIGNED NOT NULL DEFAULT 0,
			category         VARCHAR(50)     NOT NULL DEFAULT 'inne',
			status           VARCHAR(20)     NOT NULL DEFAULT 'new',
			priority         VARCHAR(20)     NOT NULL DEFAULT 'normal',
			admin_comment    TEXT            DEFAULT NULL,
			assigned_to      BIGINT UNSIGNED DEFAULT NULL,
			form_snapshot    LONGTEXT        DEFAULT NULL,
			submission_data  LONGTEXT        DEFAULT NULL,
			created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_form     (form_id),
			KEY idx_camp     (camp_id),
			KEY idx_staff    (staff_id),
			KEY idx_status   (status),
			KEY idx_priority (priority),
			KEY idx_category (category),
			KEY idx_assigned (assigned_to),
			KEY idx_date     (created_at)
		) $charset;";

		$forms_sql[] = "CREATE TABLE {$p}bm_submission_attachments (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			submission_id BIGINT UNSIGNED NOT NULL,
			original_name VARCHAR(255)    NOT NULL,
			stored_name   VARCHAR(255)    NOT NULL,
			mime_type     VARCHAR(100)    NOT NULL,
			file_size     BIGINT UNSIGNED NOT NULL DEFAULT 0,
			file_path     VARCHAR(1000)   NOT NULL,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_submission (submission_id)
		) $charset;";

		$forms_sql[] = "CREATE TABLE {$p}bm_submission_history (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			submission_id BIGINT UNSIGNED NOT NULL,
			changed_by    BIGINT UNSIGNED NOT NULL,
			from_status   VARCHAR(20)     NOT NULL DEFAULT '',
			to_status     VARCHAR(20)     NOT NULL DEFAULT '',
			note          TEXT            DEFAULT NULL,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_submission (submission_id),
			KEY idx_date       (created_at)
		) $charset;";

		foreach ( $forms_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Logi operacji (Operation Logs) ────────────────────────────────────────

		$logs_sql = [];

		$logs_sql[] = "CREATE TABLE {$p}bm_operation_logs (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			staff_id   BIGINT UNSIGNED DEFAULT NULL,
			action     VARCHAR(100)    NOT NULL,
			object_type VARCHAR(50)   NOT NULL DEFAULT '',
			object_id  BIGINT UNSIGNED DEFAULT NULL,
			details    LONGTEXT        DEFAULT NULL,
			ip_address VARCHAR(45)     NOT NULL DEFAULT '',
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user   (user_id),
			KEY idx_staff  (staff_id),
			KEY idx_action (action),
			KEY idx_date   (created_at)
		) $charset;";

		foreach ( $logs_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Szablony planów dnia (Daily Plan Templates) ───────────────────────────

		$tpl_sql = [];

		$tpl_sql[] = "CREATE TABLE {$p}bm_plan_templates (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name         VARCHAR(255)    NOT NULL,
			description  TEXT            DEFAULT NULL,
			recurrence   VARCHAR(20)     NOT NULL DEFAULT 'once',
			days_of_week VARCHAR(20)     NOT NULL DEFAULT '',
			created_by   BIGINT UNSIGNED DEFAULT NULL,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset;";

		$tpl_sql[] = "CREATE TABLE {$p}bm_plan_template_items (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			template_id  BIGINT UNSIGNED NOT NULL,
			time_from    VARCHAR(10)     NOT NULL DEFAULT '',
			time_to      VARCHAR(10)     NOT NULL DEFAULT '',
			title        VARCHAR(255)    NOT NULL,
			description  TEXT            DEFAULT NULL,
			category     VARCHAR(30)     NOT NULL DEFAULT 'inne',
			is_mandatory TINYINT(1)      NOT NULL DEFAULT 0,
			sort_order   INT             NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY idx_template (template_id)
		) $charset;";

		foreach ( $tpl_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Opcje jadłospisu: diety i miejsca (Meal Options) ─────────────────────

		$mopts_sql = [];

		$mopts_sql[] = "CREATE TABLE {$p}bm_meal_diets (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name       VARCHAR(255)    NOT NULL,
			diet_info  TEXT            DEFAULT NULL,
			sort_order INT             NOT NULL DEFAULT 0,
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset;";

		$mopts_sql[] = "CREATE TABLE {$p}bm_meal_locations (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name       VARCHAR(255)    NOT NULL,
			sort_order INT             NOT NULL DEFAULT 0,
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset;";

		$mopts_sql[] = "CREATE TABLE {$p}bm_meal_diet_costs (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			diet_id    BIGINT UNSIGNED NOT NULL,
			meal_slot  VARCHAR(40)     NOT NULL DEFAULT 'sniadanie',
			cost_netto DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			vat_rate   DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
			PRIMARY KEY (id),
			UNIQUE KEY idx_diet_slot (diet_id, meal_slot),
			KEY idx_diet (diet_id)
		) $charset;";

		foreach ( $mopts_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Szablony jadłospisów (Meal Menu Templates) ────────────────────────────

		$mtpl_sql = [];

		$mtpl_sql[] = "CREATE TABLE {$p}bm_meal_templates (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name         VARCHAR(255)    NOT NULL,
			description  TEXT            DEFAULT NULL,
			created_by   BIGINT UNSIGNED DEFAULT NULL,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset;";

		$mtpl_sql[] = "CREATE TABLE {$p}bm_meal_template_items (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			template_id  BIGINT UNSIGNED NOT NULL,
			meal_type    VARCHAR(30)     NOT NULL DEFAULT 'inne',
			time_from    VARCHAR(10)     NOT NULL DEFAULT '',
			title        VARCHAR(255)    NOT NULL,
			description  TEXT            DEFAULT NULL,
			location     VARCHAR(255)    NOT NULL DEFAULT '',
			diet_info    VARCHAR(255)    NOT NULL DEFAULT '',
			allergens    VARCHAR(255)    NOT NULL DEFAULT '',
			sort_order   INT             NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY idx_template (template_id)
		) $charset;";

		foreach ( $mtpl_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Trwałe blokady kadry (Permanent locks) ────────────────────────────────
		// Add column to bm_staff if not already present.
		// dbDelta cannot add columns to existing tables, so we use ALTER.
		$existing = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_staff");
		if ( ! in_array('permanent_lock', $existing, true) ) {
			$wpdb->query("ALTER TABLE {$p}bm_staff ADD COLUMN permanent_lock TINYINT(1) NOT NULL DEFAULT 0 AFTER locked_until");
		}

		// ── Organizacja: szablony dokumentów, biblioteka, pakiety finansowe ──────

		$org_sql = [];

		$org_sql[] = "CREATE TABLE {$p}bm_doc_templates (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title        VARCHAR(255)    NOT NULL,
			doc_type     VARCHAR(30)     NOT NULL DEFAULT 'contract',
			html_content LONGTEXT        NOT NULL DEFAULT '',
			auto_add     TINYINT(1)      NOT NULL DEFAULT 0,
			sort_order   INT             NOT NULL DEFAULT 0,
			created_by   BIGINT UNSIGNED DEFAULT NULL,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_type     (doc_type),
			KEY idx_auto_add (auto_add),
			KEY idx_order    (sort_order)
		) $charset;";

		$org_sql[] = "CREATE TABLE {$p}bm_doc_library (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title        VARCHAR(255)    NOT NULL,
			doc_type     VARCHAR(30)     NOT NULL DEFAULT 'document',
			file_id      BIGINT UNSIGNED DEFAULT NULL,
			file_url     VARCHAR(500)    NOT NULL DEFAULT '',
			file_name    VARCHAR(255)    NOT NULL DEFAULT '',
			auto_add     TINYINT(1)      NOT NULL DEFAULT 0,
			sort_order   INT             NOT NULL DEFAULT 0,
			created_by   BIGINT UNSIGNED DEFAULT NULL,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_type     (doc_type),
			KEY idx_auto_add (auto_add),
			KEY idx_order    (sort_order)
		) $charset;";

		$org_sql[] = "CREATE TABLE {$p}bm_payment_packages (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name         VARCHAR(255)    NOT NULL,
			description  TEXT            DEFAULT NULL,
			currency     VARCHAR(3)      NOT NULL DEFAULT 'PLN',
			is_default   TINYINT(1)      NOT NULL DEFAULT 0,
			created_by   BIGINT UNSIGNED DEFAULT NULL,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_default (is_default)
		) $charset;";

		$org_sql[] = "CREATE TABLE {$p}bm_payment_package_lines (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			package_id      BIGINT UNSIGNED NOT NULL,
			line_type       VARCHAR(30)     NOT NULL DEFAULT 'accommodation',
			label           VARCHAR(255)    NOT NULL,
			unit_price      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			unit            VARCHAR(30)     NOT NULL DEFAULT 'person_night',
			vat_rate        DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
			days_before     INT             NOT NULL DEFAULT 0,
			is_deposit      TINYINT(1)      NOT NULL DEFAULT 0,
			sort_order      INT             NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY idx_package (package_id),
			KEY idx_type    (line_type)
		) $charset;";

		$org_sql[] = "CREATE TABLE {$p}bm_payment_pkg_accom (
			id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			package_id            BIGINT UNSIGNED NOT NULL,
			accommodation_type_id BIGINT UNSIGNED NOT NULL,
			price_netto           DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			vat_rate              DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
			days_before           INT             NOT NULL DEFAULT 30,
			sort_order            INT             NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY idx_package (package_id)
		) $charset;";

		$org_sql[] = "CREATE TABLE {$p}bm_payment_pkg_diet_slots (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			package_id  BIGINT UNSIGNED NOT NULL,
			diet_id     BIGINT UNSIGNED NOT NULL,
			meal_slot   VARCHAR(40)     NOT NULL DEFAULT 'sniadanie',
			cost_netto  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			vat_rate    DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
			enabled     TINYINT(1)      NOT NULL DEFAULT 1,
			days_before INT             NOT NULL DEFAULT 30,
			PRIMARY KEY (id),
			UNIQUE KEY idx_pkg_diet_slot (package_id, diet_id, meal_slot),
			KEY idx_package (package_id)
		) $charset;";

		foreach ( $org_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Szablony zadań (Task Templates) ──────────────────────────────────────

		$task_tpl_sql = [];

		$task_tpl_sql[] = "CREATE TABLE {$p}bm_task_templates (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title         VARCHAR(255)    NOT NULL,
			description   TEXT            DEFAULT NULL,
			priority      VARCHAR(20)     NOT NULL DEFAULT 'normal',
			auto_add      TINYINT(1)      NOT NULL DEFAULT 0,
			sort_order    INT             NOT NULL DEFAULT 0,
			email_subject VARCHAR(255)    DEFAULT NULL,
			email_body    TEXT            DEFAULT NULL,
			created_by    BIGINT UNSIGNED DEFAULT NULL,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_auto_add (auto_add),
			KEY idx_order    (sort_order)
		) $charset;";

		foreach ( $task_tpl_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Deklaracja obozu (Camp Declarations) ─────────────────────────────────

		$decl_sql = [];

		$decl_sql[] = "CREATE TABLE {$p}bm_camp_declarations (
			id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id           BIGINT UNSIGNED NOT NULL,
			declared_persons  INT             NOT NULL DEFAULT 0,
			declared_diets    INT             NOT NULL DEFAULT 0,
			arrival_time      VARCHAR(10)     NOT NULL DEFAULT '',
			departure_time    VARCHAR(10)     NOT NULL DEFAULT '',
			is_active         TINYINT(1)      NOT NULL DEFAULT 1,
			submitted_at      DATETIME        DEFAULT NULL,
			submitted_token   VARCHAR(64)     NOT NULL DEFAULT '',
			signed_at         DATETIME        DEFAULT NULL,
			notes             TEXT            DEFAULT NULL,
			updated_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_camp (camp_id)
		) $charset;";

		foreach ( $decl_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Szkody obozu (Camp Damages) ───────────────────────────────────────────

		$dmg_sql = [];

		$dmg_sql[] = "CREATE TABLE {$p}bm_camp_damages (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id     BIGINT UNSIGNED NOT NULL,
			name        VARCHAR(255)    NOT NULL,
			description TEXT            DEFAULT NULL,
			cost        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			status      VARCHAR(20)     NOT NULL DEFAULT 'reported',
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id)
		) $charset;";

		foreach ( $dmg_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Typy noclegów (Accommodation Types) ──────────────────────────────────

		$accom_sql = [];

		$accom_sql[] = "CREATE TABLE {$p}bm_accommodation_types (
			id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name           VARCHAR(255)    NOT NULL,
			rate_per_night DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
			default_vat    DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
			description    TEXT            DEFAULT NULL,
			sort_order     INT             NOT NULL DEFAULT 0,
			created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_order (sort_order)
		) $charset;";

		foreach ( $accom_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Deklaracje per dzień (Camp Declaration Days) ──────────────────────────

		$decl_day_sql = [];

		$decl_day_sql[] = "CREATE TABLE {$p}bm_camp_declaration_days (
			id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id          BIGINT UNSIGNED NOT NULL,
			declaration_date DATE            NOT NULL,
			declared_persons INT             NOT NULL DEFAULT 0,
			arrival_time     VARCHAR(10)     NOT NULL DEFAULT '',
			departure_time   VARCHAR(10)     NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			UNIQUE KEY idx_camp_date (camp_id, declaration_date),
			KEY idx_camp (camp_id)
		) $charset;";

		$decl_day_sql[] = "CREATE TABLE {$p}bm_camp_declaration_diet_lines (
			id      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			day_id  BIGINT UNSIGNED NOT NULL,
			diet_id BIGINT UNSIGNED NOT NULL,
			count   INT             NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY idx_day_diet (day_id, diet_id)
		) $charset;";

		$decl_day_sql[] = "CREATE TABLE {$p}bm_camp_declaration_accommodation_lines (
			id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			day_id                BIGINT UNSIGNED NOT NULL,
			accommodation_type_id BIGINT UNSIGNED NOT NULL,
			count                 INT             NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY idx_day_type (day_id, accommodation_type_id)
		) $charset;";

		foreach ( $decl_day_sql as $statement ) {
			dbDelta($statement);
		}

		// Add new columns to bm_camp_organizers if they don't exist yet (ALTER for existing installs)
		$org_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_camp_organizers");
		if ( $org_cols ) {
			$new_org_cols = [
				'billing_regon'  => "ALTER TABLE {$p}bm_camp_organizers ADD COLUMN billing_regon  VARCHAR(20) NOT NULL DEFAULT '' AFTER billing_tax_id",
				'billing_krs'    => "ALTER TABLE {$p}bm_camp_organizers ADD COLUMN billing_krs    VARCHAR(30) NOT NULL DEFAULT '' AFTER billing_regon",
				'billing_street' => "ALTER TABLE {$p}bm_camp_organizers ADD COLUMN billing_street VARCHAR(255) NOT NULL DEFAULT '' AFTER billing_krs",
				'billing_city'   => "ALTER TABLE {$p}bm_camp_organizers ADD COLUMN billing_city   VARCHAR(100) NOT NULL DEFAULT '' AFTER billing_street",
				'billing_zip'    => "ALTER TABLE {$p}bm_camp_organizers ADD COLUMN billing_zip    VARCHAR(20) NOT NULL DEFAULT '' AFTER billing_city",
				'bank_name'      => "ALTER TABLE {$p}bm_camp_organizers ADD COLUMN bank_name      VARCHAR(255) NOT NULL DEFAULT '' AFTER billing_zip",
				'bank_account'   => "ALTER TABLE {$p}bm_camp_organizers ADD COLUMN bank_account   VARCHAR(50) NOT NULL DEFAULT '' AFTER bank_name",
			];
			foreach ( $new_org_cols as $col => $alter ) {
				if ( ! in_array($col, $org_cols, true) ) {
					$wpdb->query($alter); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
			}
		}

		// Add new columns to bm_camp_documents if they don't exist yet (ALTER for existing installs)
		$doc_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_camp_documents");
		if ( $doc_cols ) {
			$new_doc_cols = [
				'doc_category' => "ALTER TABLE {$p}bm_camp_documents ADD COLUMN doc_category VARCHAR(20) NOT NULL DEFAULT 'document' AFTER document_type",
				'template_id'  => "ALTER TABLE {$p}bm_camp_documents ADD COLUMN template_id BIGINT UNSIGNED DEFAULT NULL AFTER status",
				'html_content' => "ALTER TABLE {$p}bm_camp_documents ADD COLUMN html_content LONGTEXT DEFAULT NULL AFTER template_id",
				'file_id'      => "ALTER TABLE {$p}bm_camp_documents ADD COLUMN file_id BIGINT UNSIGNED DEFAULT NULL AFTER html_content",
				'file_url'     => "ALTER TABLE {$p}bm_camp_documents ADD COLUMN file_url VARCHAR(500) NOT NULL DEFAULT '' AFTER file_id",
				'sent_at'      => "ALTER TABLE {$p}bm_camp_documents ADD COLUMN sent_at DATETIME DEFAULT NULL AFTER file_url",
				'sent_token'   => "ALTER TABLE {$p}bm_camp_documents ADD COLUMN sent_token VARCHAR(64) NOT NULL DEFAULT '' AFTER sent_at",
				'signed_at'    => "ALTER TABLE {$p}bm_camp_documents ADD COLUMN signed_at DATETIME DEFAULT NULL AFTER sent_token",
				'locked'       => "ALTER TABLE {$p}bm_camp_documents ADD COLUMN locked TINYINT(1) NOT NULL DEFAULT 0 AFTER signed_at",
			];
			foreach ( $new_doc_cols as $col => $alter ) {
				if ( ! in_array($col, $doc_cols, true) ) {
					$wpdb->query($alter); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
			}
		}

		// Add email_subject / email_body to bm_task_templates if they don't exist
		$tpl_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_task_templates");
		if ( $tpl_cols ) {
			$new_tpl_cols = [
				'email_subject' => "ALTER TABLE {$p}bm_task_templates ADD COLUMN email_subject VARCHAR(255) DEFAULT NULL AFTER sort_order",
				'email_body'    => "ALTER TABLE {$p}bm_task_templates ADD COLUMN email_body TEXT DEFAULT NULL AFTER email_subject",
			];
			foreach ( $new_tpl_cols as $col => $alter ) {
				if ( ! in_array($col, $tpl_cols, true) ) {
					$wpdb->query($alter); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
			}
		}

		// Add cost_per_reservation to bm_resources if it doesn't exist
		$res_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_resources");
		if ( $res_cols && ! in_array('cost_per_reservation', $res_cols, true) ) {
			$wpdb->query("ALTER TABLE {$p}bm_resources ADD COLUMN cost_per_reservation DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER max_reservations_per_camp"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Add amount_type to bm_camp_payment_schedules if it doesn't exist
		$sched_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_camp_payment_schedules");
		if ( $sched_cols && ! in_array('amount_type', $sched_cols, true) ) {
			$wpdb->query("ALTER TABLE {$p}bm_camp_payment_schedules ADD COLUMN amount_type VARCHAR(10) NOT NULL DEFAULT 'fixed' AFTER amount"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		if ( $sched_cols && ! in_array('discount', $sched_cols, true) ) {
			$wpdb->query("ALTER TABLE {$p}bm_camp_payment_schedules ADD COLUMN discount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER amount_type"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		if ( $sched_cols && ! in_array('discount_type', $sched_cols, true) ) {
			$wpdb->query("ALTER TABLE {$p}bm_camp_payment_schedules ADD COLUMN discount_type VARCHAR(10) NOT NULL DEFAULT 'fixed' AFTER discount"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// ── Sprzęt obozu (Camp Equipment) ────────────────────────────────────────

		$equip_sql = [];

		$equip_sql[] = "CREATE TABLE {$p}bm_camp_equipment (
			id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id          BIGINT UNSIGNED NOT NULL,
			equipment_type   VARCHAR(60)     NOT NULL DEFAULT '',
			name             VARCHAR(255)    NOT NULL,
			issued_qty       INT             NOT NULL DEFAULT 0,
			returned_qty     INT             NOT NULL DEFAULT 0,
			notes            TEXT            DEFAULT NULL,
			created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp (camp_id)
		) $charset;";

		foreach ( $equip_sql as $statement ) {
			dbDelta($statement);
		}

		// ── Szablony deklaracji (Declaration Templates) ───────────────────────────

		$decl_tpl_sql = [];

		$decl_tpl_sql[] = "CREATE TABLE {$p}bm_decl_templates (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title        VARCHAR(255)    NOT NULL,
			description  TEXT            DEFAULT NULL,
			html_content LONGTEXT        NOT NULL DEFAULT '',
			auto_add     TINYINT(1)      NOT NULL DEFAULT 0,
			sort_order   INT             NOT NULL DEFAULT 0,
			created_by   BIGINT UNSIGNED DEFAULT NULL,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_auto_add (auto_add),
			KEY idx_order    (sort_order)
		) $charset;";

		$decl_tpl_sql[] = "CREATE TABLE {$p}bm_camp_decl_docs (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id       BIGINT UNSIGNED NOT NULL,
			template_id   BIGINT UNSIGNED DEFAULT NULL,
			title         VARCHAR(255)    NOT NULL,
			status        VARCHAR(30)     NOT NULL DEFAULT 'draft',
			html_content  LONGTEXT        DEFAULT NULL,
			file_url      VARCHAR(500)    NOT NULL DEFAULT '',
			signed_method VARCHAR(20)     NOT NULL DEFAULT '',
			signed_at     DATETIME        DEFAULT NULL,
			uploaded_at   DATETIME        DEFAULT NULL,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_camp     (camp_id),
			KEY idx_status   (status),
			KEY idx_template (template_id)
		) $charset;";

		foreach ( $decl_tpl_sql as $statement ) {
			dbDelta($statement);
		}

		// ── ALTER: pricing_mode + total_units on bm_resources ────────────────────
		$res_cols2 = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_resources");
		if ( $res_cols2 ) {
			if ( ! in_array('pricing_mode', $res_cols2, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_resources ADD COLUMN pricing_mode VARCHAR(10) NOT NULL DEFAULT 'flat' AFTER cost_per_reservation"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('total_units', $res_cols2, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_resources ADD COLUMN total_units INT UNSIGNED NOT NULL DEFAULT 0 AFTER pricing_mode"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		// ── ALTER: reserved_units on bm_resource_reservations ────────────────────
		$resv_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_resource_reservations");
		if ( $resv_cols && ! in_array('reserved_units', $resv_cols, true) ) {
			$wpdb->query("ALTER TABLE {$p}bm_resource_reservations ADD COLUMN reserved_units INT UNSIGNED NOT NULL DEFAULT 1 AFTER purpose"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// ── ALTER: approved_by + approved_at on bm_camp_decl_docs ────────────────
		$decl_doc_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_camp_decl_docs");
		if ( $decl_doc_cols ) {
			if ( ! in_array('approved_by', $decl_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_decl_docs ADD COLUMN approved_by BIGINT UNSIGNED DEFAULT NULL AFTER status"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('approved_at', $decl_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_decl_docs ADD COLUMN approved_at DATETIME DEFAULT NULL AFTER approved_by"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('sent_at', $decl_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_decl_docs ADD COLUMN sent_at DATETIME DEFAULT NULL AFTER approved_at"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('sent_token', $decl_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_decl_docs ADD COLUMN sent_token VARCHAR(64) NOT NULL DEFAULT '' AFTER sent_at"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('locked', $decl_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_decl_docs ADD COLUMN locked TINYINT(1) NOT NULL DEFAULT 0 AFTER sent_token"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('sent_to_camp', $decl_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_decl_docs ADD COLUMN sent_to_camp TINYINT(1) NOT NULL DEFAULT 0 AFTER locked"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('camp_approved_at', $decl_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_decl_docs ADD COLUMN camp_approved_at DATETIME DEFAULT NULL AFTER sent_to_camp"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		// ── ALTER: file columns on bm_decl_templates ─────────────────────────────
		$decl_tpl_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_decl_templates");
		if ( $decl_tpl_cols ) {
			if ( ! in_array('file_id', $decl_tpl_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_decl_templates ADD COLUMN file_id BIGINT UNSIGNED DEFAULT NULL AFTER html_content"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('file_url', $decl_tpl_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_decl_templates ADD COLUMN file_url VARCHAR(500) NOT NULL DEFAULT '' AFTER file_id"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('file_name', $decl_tpl_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_decl_templates ADD COLUMN file_name VARCHAR(255) NOT NULL DEFAULT '' AFTER file_url"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		// ── Dokument-attachments (all parent types) ───────────────────────────────
		// parent_type: doc_library | decl | camp_doc | camp_decl_doc
		$att_sql = [];
		$att_sql[] = "CREATE TABLE {$p}bm_doc_attachments (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			parent_type VARCHAR(20)     NOT NULL DEFAULT 'doc_library',
			parent_id   BIGINT UNSIGNED NOT NULL,
			file_id     BIGINT UNSIGNED DEFAULT NULL,
			file_url    VARCHAR(500)    NOT NULL DEFAULT '',
			file_name   VARCHAR(255)    NOT NULL DEFAULT '',
			uploaded_by BIGINT UNSIGNED DEFAULT NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_parent (parent_type, parent_id)
		) $charset;";
		foreach ( $att_sql as $statement ) {
			dbDelta($statement);
		}

		// ── ALTER: signing fields on bm_camp_documents ───────────────────────────
		$camp_doc_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_camp_documents");
		if ( $camp_doc_cols ) {
			if ( ! in_array('signed_method', $camp_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_documents ADD COLUMN signed_method VARCHAR(20) NOT NULL DEFAULT '' AFTER status"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('signed_at', $camp_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_documents ADD COLUMN signed_at DATETIME DEFAULT NULL AFTER signed_method"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('signed_by', $camp_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_documents ADD COLUMN signed_by BIGINT UNSIGNED DEFAULT NULL AFTER signed_at"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( ! in_array('signed_file_url', $camp_doc_cols, true) ) {
				$wpdb->query("ALTER TABLE {$p}bm_camp_documents ADD COLUMN signed_file_url VARCHAR(500) NOT NULL DEFAULT '' AFTER signed_by"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		// ── ALTER: extended settlement columns on bm_camp_settlements ────────────
		$sett_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_camp_settlements");
		if ( $sett_cols ) {
			$new_sett_cols = [
				'document_number'      => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN document_number      VARCHAR(60)    NOT NULL DEFAULT '' AFTER camp_id",
				'issue_date'           => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN issue_date           DATE           DEFAULT NULL AFTER document_number",
				'due_date'             => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN due_date             DATE           DEFAULT NULL AFTER issue_date",
				'payment_terms'        => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN payment_terms        TEXT           DEFAULT NULL AFTER due_date",
				'global_discount'      => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN global_discount      DECIMAL(12,2)  NOT NULL DEFAULT 0.00 AFTER payment_terms",
				'global_discount_type' => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN global_discount_type VARCHAR(10)    NOT NULL DEFAULT 'fixed' AFTER global_discount",
				'total_discounts'      => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN total_discounts      DECIMAL(12,2)  NOT NULL DEFAULT 0.00 AFTER total_gross",
				'total_damages'        => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN total_damages        DECIMAL(12,2)  NOT NULL DEFAULT 0.00 AFTER total_discounts",
				'amount_paid'          => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN amount_paid          DECIMAL(12,2)  NOT NULL DEFAULT 0.00 AFTER total_damages",
				'organizer_snapshot'   => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN organizer_snapshot   LONGTEXT       DEFAULT NULL AFTER notes",
				'stay_summary_snapshot'=> "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN stay_summary_snapshot LONGTEXT      DEFAULT NULL AFTER organizer_snapshot",
				'created_by'           => "ALTER TABLE {$p}bm_camp_settlements ADD COLUMN created_by           BIGINT UNSIGNED DEFAULT NULL AFTER stay_summary_snapshot",
			];
			foreach ( $new_sett_cols as $col => $alter ) {
				if ( ! in_array($col, $sett_cols, true) ) {
					$wpdb->query($alter); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
			}
		}

		// ── ALTER: extended columns on bm_camp_settlement_lines ──────────────────
		$sett_line_cols = $wpdb->get_col("SHOW COLUMNS FROM {$p}bm_camp_settlement_lines");
		if ( $sett_line_cols ) {
			$new_sline_cols = [
				'discount'              => "ALTER TABLE {$p}bm_camp_settlement_lines ADD COLUMN discount              DECIMAL(12,2)  NOT NULL DEFAULT 0.00 AFTER total_amount",
				'discount_type'         => "ALTER TABLE {$p}bm_camp_settlement_lines ADD COLUMN discount_type         VARCHAR(10)    NOT NULL DEFAULT 'fixed' AFTER discount",
				'sort_order'            => "ALTER TABLE {$p}bm_camp_settlement_lines ADD COLUMN sort_order            INT            NOT NULL DEFAULT 0 AFTER discount_type",
				'include_in_settlement' => "ALTER TABLE {$p}bm_camp_settlement_lines ADD COLUMN include_in_settlement TINYINT(1)     NOT NULL DEFAULT 1 AFTER sort_order",
				'payment_status'        => "ALTER TABLE {$p}bm_camp_settlement_lines ADD COLUMN payment_status        VARCHAR(20)    NOT NULL DEFAULT 'expected' AFTER include_in_settlement",
				'source_schedule_id'    => "ALTER TABLE {$p}bm_camp_settlement_lines ADD COLUMN source_schedule_id    BIGINT UNSIGNED DEFAULT NULL AFTER payment_status",
				'source_damage_id'      => "ALTER TABLE {$p}bm_camp_settlement_lines ADD COLUMN source_damage_id      BIGINT UNSIGNED DEFAULT NULL AFTER source_schedule_id",
				'source_equipment_id'   => "ALTER TABLE {$p}bm_camp_settlement_lines ADD COLUMN source_equipment_id   BIGINT UNSIGNED DEFAULT NULL AFTER source_damage_id",
			];
			foreach ( $new_sline_cols as $col => $alter ) {
				if ( ! in_array($col, $sett_line_cols, true) ) {
					$wpdb->query($alter); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
			}
		}
	}
}
