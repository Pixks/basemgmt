<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Core\EmailService;
use BaseMgmt\Core\EmailTemplateRepository;

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

		EmailService::save_settings($_POST);

		AdminMenu::set_notice(__('Ustawienia zapisane.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-settings'));
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
		Capabilities::require_admin();
		check_admin_referer('bm_save_email_template');

		$slug     = sanitize_key($_POST['template_slug'] ?? '');
		$subject  = sanitize_text_field(wp_unslash($_POST['template_subject'] ?? ''));
		$html_body = wp_unslash($_POST['template_html'] ?? '');

		if ( ! $slug ) {
			AdminMenu::set_notice(__('Nieprawidłowy szablon.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-settings'));
			exit;
		}

		$saved = EmailTemplateRepository::save($slug, $subject, $html_body);

		AdminMenu::set_notice(
			$saved ? __('Szablon emaila zapisany.', 'basemgmt') : __('Błąd zapisu szablonu.', 'basemgmt'),
			$saved ? 'success' : 'error'
		);
		wp_safe_redirect(admin_url("admin.php?page=basemgmt-settings&edit_template=$slug"));
		exit;
	}

	public function handle_reset_template(): void {
		Capabilities::require_admin();
		$slug = sanitize_key($_POST['slug'] ?? '');
		check_admin_referer("bm_reset_template_{$slug}");

		EmailTemplateRepository::reset($slug);

		AdminMenu::set_notice(__('Szablon przywrócony do domyślnego.', 'basemgmt'));
		wp_safe_redirect(admin_url("admin.php?page=basemgmt-settings&edit_template=$slug"));
		exit;
	}

	// ── Private rendering ─────────────────────────────────────────────────────

	private function render_template_editor(string $slug): void {
		$registry = EmailTemplateRepository::get_registry();
		if ( ! array_key_exists($slug, $registry) ) {
			wp_die(esc_html__('Nieznany szablon emaila.', 'basemgmt'));
		}

		$tpl_def = $registry[$slug];
		$saved   = EmailTemplateRepository::get_saved($slug);

		$current_subject = $saved['subject']   ?? $tpl_def['default_subject'];
		$current_html    = $saved['html_body']  ?? $tpl_def['default_html'];
		$is_customised   = $saved !== null;

		include BASEMGMT_DIR . 'templates/admin/settings/email_template.php';
	}
}

