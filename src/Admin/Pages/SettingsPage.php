<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Core\EmailService;
use BaseMgmt\Core\EmailTemplateRepository;
use BaseMgmt\Core\PdfSettings;
use BaseMgmt\Frontend\PanelStyleSettings;

defined('ABSPATH') || exit;

/**
 * Global plugin settings page (email settings, email template editor, etc.)
 */
final class SettingsPage {

	public function render(): void {
		Capabilities::require_admin();

		$slug = sanitize_key($_GET['edit_template'] ?? '');
		if ( $slug ) {
			$this->render_template_editor($slug);
		} else {
			include BASEMGMT_DIR . 'templates/admin/settings/index.php';
		}
	}

	// ── Email general settings ────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_settings');

		EmailService::save_settings(wp_parse_args($_POST, EmailService::get_settings()));
		$pdf_settings = PdfSettings::get_settings();
		PdfSettings::save_settings(wp_parse_args($_POST, [
			'pdf_header_title'    => $pdf_settings['header_title'],
			'pdf_header_subtitle' => $pdf_settings['header_subtitle'],
			'pdf_accent_color'    => $pdf_settings['accent_color'],
			'pdf_logo_url'        => $pdf_settings['logo_url'],
			'pdf_footer_text'     => $pdf_settings['footer_text'],
		]));

		// Notification settings.
		update_option('bm_missing_report_emails', sanitize_text_field(wp_unslash($_POST['missing_report_emails'] ?? '')));
		update_option('bm_report_emails',         sanitize_text_field(wp_unslash($_POST['report_emails'] ?? '')));
		update_option('bm_report_interval',       sanitize_key($_POST['report_interval'] ?? 'daily'));
		update_option('bm_lockout_minutes',        max(1, (int) ($_POST['lockout_minutes'] ?? 15)));
		update_option('bm_notify_task_added', ! empty($_POST['bm_notify_task_added']) ? '1' : '0');
		update_option('bm_notify_doc_sent',   ! empty($_POST['bm_notify_doc_sent'])   ? '1' : '0');
		update_option('bm_notify_task_email', sanitize_email($_POST['bm_notify_task_email'] ?? ''));
		update_option('bm_notify_doc_email',  sanitize_email($_POST['bm_notify_doc_email'] ?? ''));
		PanelStyleSettings::save_settings(wp_unslash($_POST));

		// Reschedule periodic report if config changed.
		\BaseMgmt\Cron\Scheduler::reschedule_staff_report();

		AdminMenu::set_notice(__('Ustawienia zapisane.', 'basemgmt'));
		$tab = sanitize_key($_POST['_bm_current_tab'] ?? '');
		$tab = in_array($tab, ['email', 'pdf', 'wyglad', 'powiadomienia', 'dane', 'info'], true) ? $tab : 'email';
		wp_safe_redirect(admin_url("admin.php?page=basemgmt-settings&tab=$tab"));
		exit;
	}

	public function handle_send_test(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_send_test_email');

		$to = sanitize_email($_POST['test_email'] ?? get_option('admin_email'));
		$ok = EmailService::send(
			$to,
			EmailService::subject(__('Test powiadomień email', 'basemgmt')),
			'reservation_created',
			[
				'reservation'   => [
					'res_date'   => gmdate('Y-m-d'),
					'start_time' => '10:00',
					'end_time'   => '12:00',
					'purpose'    => __('Testowa rezerwacja', 'basemgmt'),
				],
				'resource_name' => __('Boisko (TEST)', 'basemgmt'),
				'camp_name'     => __('Obóz Testowy', 'basemgmt'),
				'is_admin'      => false,
				'subject'       => '',
			]
		);

		$msg = $ok
			? sprintf(__('Testowy email wysłany na %s.', 'basemgmt'), $to)
			: __('Wysyłka nie powiodła się – sprawdź konfigurację serwera pocztowego.', 'basemgmt');
		AdminMenu::set_notice($msg, $ok ? 'success' : 'error');
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-settings'));
		exit;
	}

	// ── Email template actions ────────────────────────────────────────────────

	public function handle_save_template(): void {
		// SEC-01: Edycja szablonów e-mail wymaga uprawnień administratora WP (manage_options),
		// ponieważ szablony zawierają surowy HTML wysyłany do zewnętrznych odbiorców.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Edycja szablonów e-mail wymaga uprawnień administratora.', 'basemgmt' ),
				esc_html__( 'Brak uprawnień', 'basemgmt' ),
				[ 'response' => 403 ]
			);
		}
		check_admin_referer('bm_save_email_template');

		$slug      = sanitize_key($_POST['template_slug'] ?? '');
		$subject   = sanitize_text_field(wp_unslash($_POST['template_subject'] ?? ''));
		$html_body = wp_unslash($_POST['template_html'] ?? '');
		$enabled   = ! empty($_POST['template_enabled']);

		if ( ! $slug ) {
			AdminMenu::set_notice(__('Nieprawidłowy szablon.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-settings'));
			exit;
		}

		EmailTemplateRepository::set_enabled($slug, $enabled);
		$saved = EmailTemplateRepository::save($slug, $subject, $html_body);

		AdminMenu::set_notice(
			$saved ? __('Szablon emaila zapisany.', 'basemgmt') : __('Błąd zapisu szablonu.', 'basemgmt'),
			$saved ? 'success' : 'error'
		);
		wp_safe_redirect(admin_url("admin.php?page=basemgmt-settings&edit_template=$slug"));
		exit;
	}

	public function handle_reset_template(): void {
		// SEC-01: Spójnie z handle_save_template – tylko administrator WP.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Edycja szablonów e-mail wymaga uprawnień administratora.', 'basemgmt' ),
				esc_html__( 'Brak uprawnień', 'basemgmt' ),
				[ 'response' => 403 ]
			);
		}
		$slug = sanitize_key($_POST['slug'] ?? '');
		check_admin_referer("bm_reset_template_{$slug}");

		EmailTemplateRepository::reset($slug);

		AdminMenu::set_notice(__('Szablon przywrócony do domyślnego.', 'basemgmt'));
		wp_safe_redirect(admin_url("admin.php?page=basemgmt-settings&edit_template=$slug"));
		exit;
	}

	// ── Translations ─────────────────────────────────────────────────────────

	public function handle_compile_mo(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_compile_mo');

		$results = \BaseMgmt\Core\MoCompiler::compile_all();
		$ok      = array_filter( $results );
		$fail    = array_diff_key( $results, $ok );

		if ( $fail ) {
			$msg = sprintf(
				/* translators: %s: comma-separated list of failed .po filenames */
				__( 'Nie udało się skompilować: %s', 'basemgmt' ),
				implode( ', ', array_keys( $fail ) )
			);
			AdminMenu::set_notice( $msg, 'warning' );
		} else {
			AdminMenu::set_notice(
				sprintf(
					/* translators: %d: number of compiled files */
					__( 'Skompilowano %d plików tłumaczeń (.mo).', 'basemgmt' ),
					count( $ok )
				)
			);
		}
		wp_safe_redirect( admin_url( 'admin.php?page=basemgmt-settings&tab=dane' ) );
		exit;
	}

	// ── Backup / Import / Clear ───────────────────────────────────────────────

	public function handle_backup(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_backup_data');

		global $wpdb;
		$tables = \BaseMgmt\Database\Schema::table_names();
		$backup = [
			'version'    => BASEMGMT_VERSION,
			'created_at' => gmdate('Y-m-d H:i:s'),
			'tables'     => [],
		];

		// SEC-009: Kolumny wrażliwe zawsze wykluczone z eksportu.
		$sensitive_columns = [
			'staff' => [ 'security_code_hash', 'failed_attempts', 'locked_until', 'permanent_lock', 'last_login' ],
		];

		foreach ( $tables as $key => $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( "SELECT * FROM `$table`", ARRAY_A );
			$rows = $rows ?: [];

			if ( ! empty( $sensitive_columns[ $key ] ) ) {
				$drop = $sensitive_columns[ $key ];
				$rows = array_map(
					static fn( array $row ) => array_diff_key( $row, array_flip( $drop ) ),
					$rows
				);
			}

			$backup['tables'][ $key ] = $rows;
		}

		$filename = 'camplink-backup-' . gmdate('Y-m-d-His') . '.json';
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_json_encode( $backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	public function handle_import(): void {
		// SEC-08: Import danych (TRUNCATE + re-insert) wymaga uprawnień administratora WP.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Import danych wymaga uprawnień administratora.', 'basemgmt' ),
				esc_html__( 'Brak uprawnień', 'basemgmt' ),
				[ 'response' => 403 ]
			);
		}
		check_admin_referer('bm_import_data');

		if ( empty( $_FILES['backup_file']['tmp_name'] ) ) {
			AdminMenu::set_notice( __( 'Nie wybrano pliku.', 'basemgmt' ), 'error' );
			wp_safe_redirect( admin_url( 'admin.php?page=basemgmt-settings&tab=dane' ) );
			exit;
		}

		// SEC-03: Walidacja rozmiaru pliku – max 5 MB, aby uniknąć DoS przez wyczerpanie pamięci.
		// Używamy filesize() na pliku tymczasowym zamiast $_FILES['size'], ponieważ
		// wartość $_FILES['size'] pochodzi od klienta HTTP i może być sfałszowana.
		$max_bytes = 5 * 1024 * 1024;
		if ( filesize( $_FILES['backup_file']['tmp_name'] ) > $max_bytes ) {
			AdminMenu::set_notice( __( 'Plik backupu jest za duży (maksymalnie 5 MB).', 'basemgmt' ), 'error' );
			wp_safe_redirect( admin_url( 'admin.php?page=basemgmt-settings&tab=dane' ) );
			exit;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw = file_get_contents( $_FILES['backup_file']['tmp_name'] );
		if ( ! $raw ) {
			AdminMenu::set_notice( __( 'Nie można odczytać pliku backupu.', 'basemgmt' ), 'error' );
			wp_safe_redirect( admin_url( 'admin.php?page=basemgmt-settings&tab=dane' ) );
			exit;
		}

		$data = json_decode( $raw, true );
		if ( ! $data || empty( $data['tables'] ) ) {
			AdminMenu::set_notice( __( 'Nieprawidłowy format pliku backupu.', 'basemgmt' ), 'error' );
			wp_safe_redirect( admin_url( 'admin.php?page=basemgmt-settings&tab=dane' ) );
			exit;
		}

		global $wpdb;
		$tables  = \BaseMgmt\Database\Schema::table_names();
		$count   = 0;

		// SEC-003: Kolumny wrażliwe tabeli bm_staff są zawsze wykluczone z importu,
		// niezależnie od zawartości pliku backup. Zapobiega to nadpisaniu hashy PIN-ów,
		// flag blokad i liczników przez spreparowany plik backup.
		$import_excluded = [
			'staff' => [ 'security_code_hash', 'failed_attempts', 'locked_until', 'permanent_lock', 'last_login' ],
		];

		// SEC-03: Limit wierszy na tabelę zapobiega wyczerpaniu zasobów przez duże backupy.
		$max_rows_per_table = 10000;

		foreach ( $data['tables'] as $key => $rows ) {
			if ( empty( $tables[ $key ] ) || ! is_array( $rows ) ) {
				continue;
			}
			$table = $tables[ $key ];

			// Pobierz dozwolone kolumny z rzeczywistego schematu tabeli.
			// Kolumna 'id' jest celowo wyłączona – AUTO_INCREMENT baz danych.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$describe = $wpdb->get_results( "DESCRIBE `{$table}`", ARRAY_A );
			if ( ! $describe ) {
				continue;
			}
			$allowed_columns = array_column( $describe, 'Field' );
			$excluded        = array_merge( [ 'id' ], $import_excluded[ $key ] ?? [] );
			$allowed_columns = array_diff( $allowed_columns, $excluded );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "TRUNCATE TABLE `{$table}`" );

			// SEC-03: Ogranicz liczbę importowanych wierszy na tabelę.
			$rows_to_import = array_slice( $rows, 0, $max_rows_per_table );

			foreach ( $rows_to_import as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				// Filtruj wiersz – tylko kolumny istniejące w tabeli, bez 'id'.
				$filtered = array_intersect_key( $row, array_flip( $allowed_columns ) );
				if ( empty( $filtered ) ) {
					continue;
				}
				$wpdb->insert( $table, $filtered ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$count++;
			}
		}

		AdminMenu::set_notice( sprintf( __( 'Import zakończony – przywrócono %d rekordów.', 'basemgmt' ), $count ) );
		wp_safe_redirect( admin_url( 'admin.php?page=basemgmt-settings&tab=dane' ) );
		exit;
	}

	public function handle_clear(): void {
		// SEC-08: Wyczyszczenie wszystkich danych wymaga uprawnień administratora WP.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Wyczyszczenie danych wymaga uprawnień administratora.', 'basemgmt' ),
				esc_html__( 'Brak uprawnień', 'basemgmt' ),
				[ 'response' => 403 ]
			);
		}
		check_admin_referer('bm_clear_data');

		global $wpdb;
		$tables = \BaseMgmt\Database\Schema::table_names();

		// Disable FK checks temporarily so truncate works regardless of order.
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "TRUNCATE TABLE `$table`" );
		}
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );

		AdminMenu::set_notice( __( 'Wszystkie dane wtyczki zostały wyczyszczone.', 'basemgmt' ), 'success' );
		wp_safe_redirect( admin_url( 'admin.php?page=basemgmt-settings&tab=dane' ) );
		exit;
	}

	// ── Private rendering ─────────────────────────────────────────────────────

	private function render_template_editor(string $slug): void {
		$registry = EmailTemplateRepository::get_registry();
		if ( ! array_key_exists($slug, $registry) ) {
			wp_die(esc_html__('Nieznany szablon emaila.', 'basemgmt'));
		}

		$tpl_def     = $registry[$slug];
		$saved       = EmailTemplateRepository::get_saved($slug);

		$current_subject = $saved['subject']   ?? $tpl_def['default_subject'];
		$current_html    = $saved['html_body']  ?? $tpl_def['default_html'];
		$is_customised   = $saved !== null;
		$is_enabled      = EmailTemplateRepository::is_enabled($slug);

		include BASEMGMT_DIR . 'templates/admin/settings/email_template.php';
	}
}
