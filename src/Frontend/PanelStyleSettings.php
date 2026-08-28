<?php

declare(strict_types=1);

namespace BaseMgmt\Frontend;

defined('ABSPATH') || exit;

final class PanelStyleSettings {

	/** Allowed font-family values (index = stored value, value = CSS string). */
	public const FONT_FAMILIES = [
		'lato'       => 'Lato, "Open Sans", system-ui, sans-serif',
		'open-sans'  => '"Open Sans", Lato, system-ui, sans-serif',
		'roboto'     => 'Roboto, "Open Sans", system-ui, sans-serif',
		'nunito'     => 'Nunito, "Open Sans", system-ui, sans-serif',
		'system'     => 'system-ui, -apple-system, sans-serif',
		'custom'     => 'sans-serif',  // overridden at runtime by custom_font_name
	];

	/** Allowed shadow values (index = stored value, value = CSS box-shadow). */
	public const SHADOWS = [
		'none' => 'none',
		'sm'   => '0 1px 4px rgba(0,0,0,.08)',
		'md'   => '0 2px 12px rgba(0,0,0,.10)',
		'lg'   => '0 4px 24px rgba(0,0,0,.14)',
	];

	/** @return array<string,array<string,string>> */
	public static function presets(): array {
		return [
			'zhp-modern' => [
				'label'           => __('ZHP nowoczesny', 'basemgmt'),
				'primary_color'   => '#6EA82E',
				'primary_hover'   => '#5A8D24',
				'surface_color'   => '#FFFFFF',
				'border_color'    => '#E0E6E0',
				'background'      => '#F4F7F0',
				'text_color'      => '#333333',
				'heading_color'   => '#1A1A1A',
				'btn_text_color'  => '#FFFFFF',
				'badge_color'     => '#6EA82E',
				'badge_text_color'=> '#FFFFFF',
				'link_color'      => '#5A8D24',
				'radius'          => '10',
				'btn_radius'      => '999',
				'shadow'          => 'md',
				'font_family'     => 'open-sans',
				'header_gradient' => '0',
			],
			'zhp' => [
				'label'           => __('ZHP klasyczny', 'basemgmt'),
				'primary_color'   => '#1B5E33',
				'primary_hover'   => '#2A7A4B',
				'surface_color'   => '#FFFFFF',
				'border_color'    => '#D0D8DC',
				'background'      => '#F6F8FA',
				'text_color'      => '#1A2530',
				'heading_color'   => '#0D1F18',
				'btn_text_color'  => '#FFFFFF',
				'badge_color'     => '#1B5E33',
				'badge_text_color'=> '#FFFFFF',
				'link_color'      => '#1B5E33',
				'radius'          => '8',
				'btn_radius'      => '999',
				'shadow'          => 'sm',
				'font_family'     => 'lato',
				'header_gradient' => '1',
			],
			'navy' => [
				'label'           => __('Granatowy', 'basemgmt'),
				'primary_color'   => '#1E3A8A',
				'primary_hover'   => '#1D4ED8',
				'surface_color'   => '#FFFFFF',
				'border_color'    => '#CBD5E1',
				'background'      => '#F1F5F9',
				'text_color'      => '#0F172A',
				'heading_color'   => '#0A1020',
				'btn_text_color'  => '#FFFFFF',
				'badge_color'     => '#1D4ED8',
				'badge_text_color'=> '#FFFFFF',
				'link_color'      => '#1D4ED8',
				'radius'          => '8',
				'btn_radius'      => '999',
				'shadow'          => 'sm',
				'font_family'     => 'roboto',
				'header_gradient' => '1',
			],
			'sand' => [
				'label'           => __('Piaskowy', 'basemgmt'),
				'primary_color'   => '#92400E',
				'primary_hover'   => '#B45309',
				'surface_color'   => '#FFFFFF',
				'border_color'    => '#E7D7B1',
				'background'      => '#FFF7E8',
				'text_color'      => '#3F2D1B',
				'heading_color'   => '#2A1A08',
				'btn_text_color'  => '#FFFFFF',
				'badge_color'     => '#B45309',
				'badge_text_color'=> '#FFFFFF',
				'link_color'      => '#92400E',
				'radius'          => '10',
				'btn_radius'      => '999',
				'shadow'          => 'md',
				'font_family'     => 'nunito',
				'header_gradient' => '0',
			],
		];
	}

	/** @return array<string,string> */
	public static function get_settings(): array {
		$presets = self::presets();
		$preset  = sanitize_key((string) get_option('bm_ui_style_preset', 'zhp-modern'));
		if ( ! isset($presets[$preset]) ) {
			$preset = 'zhp-modern';
		}

		$base = $presets[$preset];

		return [
			'preset'           => $preset,
			'primary_color'    => self::get_color_option('bm_ui_primary_color',        $base['primary_color']),
			'primary_hover'    => self::get_color_option('bm_ui_primary_hover_color',   $base['primary_hover']),
			'surface_color'    => self::get_color_option('bm_ui_surface_color',         $base['surface_color']),
			'border_color'     => self::get_color_option('bm_ui_border_color',          $base['border_color']),
			'background'       => self::get_color_option('bm_ui_background_color',      $base['background']),
			'text_color'       => self::get_color_option('bm_ui_text_color',            $base['text_color']),
			'heading_color'    => self::get_color_option('bm_ui_heading_color',         $base['heading_color']),
			'btn_text_color'   => self::get_color_option('bm_ui_btn_text_color',        $base['btn_text_color']),
			'badge_color'      => self::get_color_option('bm_ui_badge_color',           $base['badge_color']),
			'badge_text_color' => self::get_color_option('bm_ui_badge_text_color',      $base['badge_text_color']),
			'link_color'       => self::get_color_option('bm_ui_link_color',            $base['link_color']),
			'radius'           => (string) self::get_radius_option('bm_ui_radius', (int) $base['radius']),
			'btn_radius'       => (string) self::get_btn_radius_option('bm_ui_btn_radius', (int) ($base['btn_radius'] ?? 999)),
			'shadow'           => self::get_shadow_option('bm_ui_shadow', $base['shadow']),
			'font_family'      => self::get_font_option('bm_ui_font_family', $base['font_family']),
			'header_gradient'  => ((string) get_option('bm_ui_header_gradient', $base['header_gradient'])) === '1' ? '1' : '0',
			'custom_font_url'  => esc_url_raw((string) get_option('bm_ui_custom_font_url', '')),
			'custom_font_name' => sanitize_text_field((string) get_option('bm_ui_custom_font_name', '')),
		];
	}

	/** @param array<string,mixed> $input */
	public static function save_settings(array $input): void {
		$current = self::get_settings();
		$presets = self::presets();

		$preset = sanitize_key((string) ($input['bm_ui_style_preset'] ?? $current['preset']));
		if ( ! isset($presets[$preset]) ) {
			$preset = 'zhp-modern';
		}

		$set_color = static function (string $key, string $fallback) use ($input): string {
			if ( ! array_key_exists($key, $input) ) {
				return $fallback;
			}
			$value = sanitize_hex_color((string) $input[$key]);
			return $value ?: $fallback;
		};

		$radius = array_key_exists('bm_ui_radius', $input)
			? max(0, min(32, (int) $input['bm_ui_radius']))
			: (int) $current['radius'];

		$btn_radius = array_key_exists('bm_ui_btn_radius', $input)
			? max(0, min(999, (int) $input['bm_ui_btn_radius']))
			: (int) $current['btn_radius'];

		$shadow = array_key_exists('bm_ui_shadow', $input)
			? (array_key_exists($input['bm_ui_shadow'], self::SHADOWS) ? (string) $input['bm_ui_shadow'] : $current['shadow'])
			: $current['shadow'];

		$font = array_key_exists('bm_ui_font_family', $input)
			? (array_key_exists($input['bm_ui_font_family'], self::FONT_FAMILIES) ? (string) $input['bm_ui_font_family'] : $current['font_family'])
			: $current['font_family'];

		$gradient = ! empty($input['bm_ui_header_gradient']) ? '1' : '0';

		$custom_font_url  = esc_url_raw((string) ($input['bm_ui_custom_font_url'] ?? $current['custom_font_url']));
		$custom_font_name = sanitize_text_field((string) ($input['bm_ui_custom_font_name'] ?? $current['custom_font_name']));

		update_option('bm_ui_style_preset',        $preset);
		update_option('bm_ui_primary_color',        $set_color('bm_ui_primary_color',       $current['primary_color']));
		update_option('bm_ui_primary_hover_color',  $set_color('bm_ui_primary_hover_color',  $current['primary_hover']));
		update_option('bm_ui_surface_color',        $set_color('bm_ui_surface_color',        $current['surface_color']));
		update_option('bm_ui_border_color',         $set_color('bm_ui_border_color',         $current['border_color']));
		update_option('bm_ui_background_color',     $set_color('bm_ui_background_color',     $current['background']));
		update_option('bm_ui_text_color',           $set_color('bm_ui_text_color',           $current['text_color']));
		update_option('bm_ui_heading_color',        $set_color('bm_ui_heading_color',        $current['heading_color']));
		update_option('bm_ui_btn_text_color',       $set_color('bm_ui_btn_text_color',       $current['btn_text_color']));
		update_option('bm_ui_badge_color',          $set_color('bm_ui_badge_color',          $current['badge_color']));
		update_option('bm_ui_badge_text_color',     $set_color('bm_ui_badge_text_color',     $current['badge_text_color']));
		update_option('bm_ui_link_color',           $set_color('bm_ui_link_color',           $current['link_color']));
		update_option('bm_ui_radius',               $radius);
		update_option('bm_ui_btn_radius',           $btn_radius);
		update_option('bm_ui_shadow',               $shadow);
		update_option('bm_ui_font_family',          $font);
		update_option('bm_ui_header_gradient',      $gradient);
		update_option('bm_ui_custom_font_url',      $custom_font_url);
		update_option('bm_ui_custom_font_name',     $custom_font_name);
	}

	public static function build_inline_css(): string {
		$settings   = self::get_settings();
		$shadow_css = self::SHADOWS[$settings['shadow']] ?? self::SHADOWS['sm'];
		$font_key   = $settings['font_family'];
		$font_css   = self::FONT_FAMILIES[$font_key] ?? self::FONT_FAMILIES['lato'];

		// For custom font, build the CSS stack from the saved font name.
		if ( $font_key === 'custom' && $settings['custom_font_name'] !== '' ) {
			$name     = $settings['custom_font_name'];
			$font_css = '"' . addslashes($name) . '", sans-serif';
		}

		$gradient   = $settings['header_gradient'] === '1'
			? 'linear-gradient(135deg,var(--bm-primary),var(--bm-primary-hover))'
			: 'var(--bm-primary)';

		return sprintf(
			'.bm-ui{' .
				'--bm-primary:%1$s;' .
				'--bm-primary-hover:%2$s;' .
				'--bm-surface:%3$s;' .
				'--bm-border:%4$s;' .
				'--bm-bg:%5$s;' .
				'--bm-text:%6$s;' .
				'--bm-heading:%7$s;' .
				'--bm-btn-text:%8$s;' .
				'--bm-badge-bg:%9$s;' .
				'--bm-badge-text:%10$s;' .
				'--bm-link:%11$s;' .
				'--bm-radius:%12$spx;' .
				'--bm-radius-pill:%16$spx;' .
				// %13$s, %14$s, %15$s come from hardcoded internal constants
				// (self::SHADOWS / FONT_FAMILIES arrays) — never user input, no escaping needed.
				// %14$s may contain CSS double-quotes (e.g. "Open Sans") which esc_attr would break.
				'--bm-shadow:%13$s;' .
				'--bm-font:%14$s;' .
				'--bm-header-bg:%15$s;' .
			'}',
			esc_attr($settings['primary_color']),
			esc_attr($settings['primary_hover']),
			esc_attr($settings['surface_color']),
			esc_attr($settings['border_color']),
			esc_attr($settings['background']),
			esc_attr($settings['text_color']),
			esc_attr($settings['heading_color']),
			esc_attr($settings['btn_text_color']),
			esc_attr($settings['badge_color']),
			esc_attr($settings['badge_text_color']),
			esc_attr($settings['link_color']),
			esc_attr($settings['radius']),
			$shadow_css,
			$font_css,
			$gradient,
			esc_attr($settings['btn_radius'])
		);
	}

	private static function get_color_option(string $key, string $fallback): string {
		$color = sanitize_hex_color((string) get_option($key, $fallback));
		return $color ?: $fallback;
	}

	private static function get_radius_option(string $key, int $fallback): int {
		$val = (int) get_option($key, $fallback);
		return max(0, min(32, $val));
	}

	private static function get_btn_radius_option(string $key, int $fallback): int {
		$val = (int) get_option($key, $fallback);
		return max(0, min(999, $val));
	}

	/** Returns the stored custom font URL, or empty string if none set. */
	public static function get_custom_font_url(): string {
		return esc_url_raw((string) get_option('bm_ui_custom_font_url', ''));
	}

	private static function get_shadow_option(string $key, string $fallback): string {
		$val = sanitize_key((string) get_option($key, $fallback));
		return array_key_exists($val, self::SHADOWS) ? $val : $fallback;
	}

	private static function get_font_option(string $key, string $fallback): string {
		$val = sanitize_key((string) get_option($key, $fallback));
		return array_key_exists($val, self::FONT_FAMILIES) ? $val : $fallback;
	}
}
