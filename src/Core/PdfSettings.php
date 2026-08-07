<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

defined('ABSPATH') || exit;

/**
 * Shared branding settings for printable report views.
 */
final class PdfSettings {

	private const SETTINGS_KEY = 'basemgmt_pdf_settings';

	public static function get_settings(): array {
		$site_name = get_bloginfo('name');

		return wp_parse_args(get_option(self::SETTINGS_KEY, []), [
			'header_title'    => $site_name,
			'header_subtitle' => '',
			'accent_color'    => '#2271b1',
			'logo_url'        => '',
			'footer_text'     => $site_name,
		]);
	}

	public static function save_settings(array $data): void {
		update_option(self::SETTINGS_KEY, [
			'header_title'    => sanitize_text_field($data['pdf_header_title'] ?? ''),
			'header_subtitle' => sanitize_text_field($data['pdf_header_subtitle'] ?? ''),
			'accent_color'    => sanitize_hex_color($data['pdf_accent_color'] ?? '#2271b1') ?? '#2271b1',
			'logo_url'        => esc_url_raw($data['pdf_logo_url'] ?? ''),
			'footer_text'     => sanitize_text_field($data['pdf_footer_text'] ?? ''),
		]);
	}
}
