<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

defined('ABSPATH') || exit;

/**
 * Global email service for the plugin.
 *
 * All plugin email notifications go through this class, ensuring consistent
 * branding (header colour, logo, footer) configurable by the administrator.
 *
 * Usage:
 *   EmailService::send(
 *       'recipient@example.com',
 *       'Temat',
 *       'reservation_created',        // template slug
 *       ['reservation' => $data, ...] // template variables
 *   );
 */
final class EmailService {

	private const SETTINGS_KEY = 'basemgmt_email_settings';

	// ── Settings ──────────────────────────────────────────────────────────────

	public static function get_settings(): array {
		$site_name = get_bloginfo('name');
		return wp_parse_args(get_option(self::SETTINGS_KEY, []), [
			'from_name'          => $site_name,
			'from_email'         => get_option('admin_email'),
			'header_color'       => '#2271b1',
			'logo_url'           => '',
			'header_title'       => $site_name,
			'header_html'        => '',
			'footer_text'        => sprintf(
				/* translators: %s site name */
				__('Wiadomość wysłana automatycznie przez system %s.', 'basemgmt'),
				$site_name
			),
			'admin_notify_email' => get_option('admin_email'),
		]);
	}

	public static function save_settings(array $data): void {
		update_option(self::SETTINGS_KEY, [
			'from_name'          => sanitize_text_field($data['from_name']          ?? ''),
			'from_email'         => sanitize_email($data['from_email']              ?? ''),
			'header_color'       => sanitize_hex_color($data['header_color']        ?? '#2271b1') ?? '#2271b1',
			'logo_url'           => esc_url_raw($data['logo_url']                   ?? ''),
			'header_title'       => sanitize_text_field($data['header_title']       ?? ''),
			'header_html'        => wp_kses_post(wp_unslash($data['header_html']    ?? '')),
			'footer_text'        => wp_kses_post(wp_unslash($data['footer_text']    ?? '')),
			'admin_notify_email' => sanitize_email($data['admin_notify_email']      ?? ''),
		]);
	}

	// ── Send ──────────────────────────────────────────────────────────────────

	/**
	 * Send a plugin email using a named template.
	 *
	 * @param string   $to       Recipient email.
	 * @param string   $subject  Email subject.
	 * @param string   $template Template slug (maps to templates/email/{slug}.php).
	 * @param array    $data     Variables available inside the template.
	 * @return bool
	 */
	public static function send(string $to, string $subject, string $template, array $data = []): bool {
		if ( ! is_email($to) ) return false;

		$body = self::render($template, $data);
		if ( ! $body ) return false;
		$subject = EmailTemplateRepository::get_subject_override($template, $data) ?: $subject;

		$settings = self::get_settings();

		// Store closure references so we can remove exactly these callbacks,
		// leaving any other plugin's wp_mail_* filters intact.
		$cb_content_type = static fn() => 'text/html';
		$cb_from         = static fn() => $settings['from_email'];
		$cb_from_name    = static fn() => $settings['from_name'];

		add_filter('wp_mail_content_type', $cb_content_type);
		add_filter('wp_mail_from',         $cb_from);
		add_filter('wp_mail_from_name',    $cb_from_name);

		$result = wp_mail($to, $subject, $body);

		remove_filter('wp_mail_content_type', $cb_content_type);
		remove_filter('wp_mail_from',         $cb_from);
		remove_filter('wp_mail_from_name',    $cb_from_name);

		return $result;
	}

	/**
	 * Send to multiple recipients.
	 *
	 * @param string[] $to_list
	 */
	public static function send_many(array $to_list, string $subject, string $template, array $data = []): void {
		foreach ( $to_list as $to ) {
			self::send($to, $subject, $template, $data);
		}
	}

	// ── Render ────────────────────────────────────────────────────────────────

	/**
	 * Renders template slug into full HTML (base layout + content).
	 *
	 * Priority: DB-saved custom template → PHP file template.
	 * Returns empty string if neither exists.
	 */
	public static function render(string $template_slug, array $data = []): string {
		$settings = self::get_settings();

		// 1. Try DB-stored custom template first.
		$custom_body = EmailTemplateRepository::render_body($template_slug, $data);

		if ( $custom_body !== null ) {
			$content = $custom_body;
		} else {
			// 2. Fall back to PHP file template.
			$template_file = BASEMGMT_DIR . 'templates/email/' . $template_slug . '.php';
			if ( ! is_readable($template_file) ) {
				return '';
			}
			$content = self::capture($template_file, $data + ['settings' => $settings]);
		}

		// Wrap in shared base layout (header / footer branding).
		$base_file = BASEMGMT_DIR . 'templates/email/base.php';
		if ( ! is_readable($base_file) ) {
			return $content;
		}

		return self::capture($base_file, [
			'content'  => $content,
			'subject'  => $data['subject'] ?? '',
			'settings' => $settings,
		]);
	}

	/**
	 * Captures template output into a string.
	 */
	private static function capture(string $file, array $vars): string {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract($vars, EXTR_SKIP);
		ob_start();
		include $file;
		return (string) ob_get_clean();
	}

	// ── Subject helpers ───────────────────────────────────────────────────────

	public static function subject(string $text): string {
		return sprintf('[%s] %s', get_bloginfo('name'), $text);
	}
}
