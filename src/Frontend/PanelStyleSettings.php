<?php

declare(strict_types=1);

namespace BaseMgmt\Frontend;

defined('ABSPATH') || exit;

final class PanelStyleSettings {

	/** @return array<string,array<string,string>> */
	public static function presets(): array {
		return [
			'zhp' => [
				'label'          => __('ZHP klasyczny', 'basemgmt'),
				'primary_color'  => '#1B5E33',
				'primary_hover'  => '#2A7A4B',
				'surface_color'  => '#FFFFFF',
				'border_color'   => '#D0D8DC',
				'background'     => '#F6F8FA',
				'text_color'     => '#1A2530',
				'radius'         => '8',
			],
			'navy' => [
				'label'          => __('Granatowy', 'basemgmt'),
				'primary_color'  => '#1E3A8A',
				'primary_hover'  => '#1D4ED8',
				'surface_color'  => '#FFFFFF',
				'border_color'   => '#CBD5E1',
				'background'     => '#F1F5F9',
				'text_color'     => '#0F172A',
				'radius'         => '8',
			],
			'sand' => [
				'label'          => __('Piaskowy', 'basemgmt'),
				'primary_color'  => '#92400E',
				'primary_hover'  => '#B45309',
				'surface_color'  => '#FFFFFF',
				'border_color'   => '#E7D7B1',
				'background'     => '#FFF7E8',
				'text_color'     => '#3F2D1B',
				'radius'         => '10',
			],
		];
	}

	/** @return array<string,string> */
	public static function get_settings(): array {
		$presets = self::presets();
		$preset  = sanitize_key((string) get_option('bm_ui_style_preset', 'zhp'));
		if ( ! isset($presets[$preset]) ) {
			$preset = 'zhp';
		}

		$base = $presets[$preset];

		return [
			'preset'         => $preset,
			'primary_color'  => self::get_color_option('bm_ui_primary_color', $base['primary_color']),
			'primary_hover'  => self::get_color_option('bm_ui_primary_hover_color', $base['primary_hover']),
			'surface_color'  => self::get_color_option('bm_ui_surface_color', $base['surface_color']),
			'border_color'   => self::get_color_option('bm_ui_border_color', $base['border_color']),
			'background'     => self::get_color_option('bm_ui_background_color', $base['background']),
			'text_color'     => self::get_color_option('bm_ui_text_color', $base['text_color']),
			'radius'         => (string) self::get_radius_option('bm_ui_radius', (int) $base['radius']),
		];
	}

	/** @param array<string,mixed> $input */
	public static function save_settings(array $input): void {
		$current = self::get_settings();
		$presets = self::presets();

		$preset = sanitize_key((string) ($input['bm_ui_style_preset'] ?? $current['preset']));
		if ( ! isset($presets[$preset]) ) {
			$preset = 'zhp';
		}

		$set_color = static function (string $key, string $fallback) use ($input): string {
			if ( ! array_key_exists($key, $input) ) {
				return $fallback;
			}
			$value = sanitize_hex_color((string) $input[$key]);
			return $value ?: $fallback;
		};

		$radius = array_key_exists('bm_ui_radius', $input)
			? max(0, min(24, (int) $input['bm_ui_radius']))
			: (int) $current['radius'];

		update_option('bm_ui_style_preset', $preset);
		update_option('bm_ui_primary_color',  $set_color('bm_ui_primary_color', $current['primary_color']));
		update_option('bm_ui_primary_hover_color', $set_color('bm_ui_primary_hover_color', $current['primary_hover']));
		update_option('bm_ui_surface_color',  $set_color('bm_ui_surface_color', $current['surface_color']));
		update_option('bm_ui_border_color',   $set_color('bm_ui_border_color', $current['border_color']));
		update_option('bm_ui_background_color', $set_color('bm_ui_background_color', $current['background']));
		update_option('bm_ui_text_color',     $set_color('bm_ui_text_color', $current['text_color']));
		update_option('bm_ui_radius', $radius);
	}

	public static function build_inline_css(): string {
		$settings = self::get_settings();

		return sprintf(
			'.bm-ui{--bm-primary:%1$s;--bm-primary-hover:%2$s;--bm-surface:%3$s;--bm-border:%4$s;--bm-bg:%5$s;--bm-text:%6$s;--bm-radius:%7$spx;}',
			esc_attr($settings['primary_color']),
			esc_attr($settings['primary_hover']),
			esc_attr($settings['surface_color']),
			esc_attr($settings['border_color']),
			esc_attr($settings['background']),
			esc_attr($settings['text_color']),
			esc_attr($settings['radius'])
		);
	}

	private static function get_color_option(string $key, string $fallback): string {
		$color = sanitize_hex_color((string) get_option($key, $fallback));
		return $color ?: $fallback;
	}

	private static function get_radius_option(string $key, int $fallback): int {
		$val = (int) get_option($key, $fallback);
		return max(0, min(24, $val));
	}
}
