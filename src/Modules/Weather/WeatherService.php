<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Weather;

defined('ABSPATH') || exit;

/**
 * Cache layer for weather data.
 * Uses WP transients (30 min TTL) so no extra table is needed for the data itself.
 * Settings stored in wp_options.
 */
final class WeatherService {

	private const TRANSIENT_KEY  = 'bm_weather_cache';
	private const CACHE_TTL      = 30 * MINUTE_IN_SECONDS;
	private const SETTINGS_KEY   = 'basemgmt_weather_settings';

	private WeatherProviderInterface $provider;

	public function __construct(?WeatherProviderInterface $provider = null) {
		$this->provider = $provider ?? new OpenMeteoProvider();
	}

	// ── Settings ──────────────────────────────────────────────────────────────

	public static function get_settings(): array {
		$defaults = [
			'latitude'      => '',
			'longitude'     => '',
			'location_name' => '',
			'timezone'      => 'Europe/Warsaw',
		];
		return wp_parse_args(get_option(self::SETTINGS_KEY, []), $defaults);
	}

	public static function save_settings(array $data): void {
		$settings = [
			'latitude'      => (string) ($data['latitude']      ?? ''),
			'longitude'     => (string) ($data['longitude']     ?? ''),
			'location_name' => sanitize_text_field($data['location_name'] ?? ''),
			'timezone'      => sanitize_text_field($data['timezone']      ?? 'Europe/Warsaw'),
		];
		update_option(self::SETTINGS_KEY, $settings);
	}

	public static function is_configured(): bool {
		$s = self::get_settings();
		return $s['latitude'] !== '' && $s['longitude'] !== '';
	}

	// ── Fetch / cache ─────────────────────────────────────────────────────────

	/** Returns cached or fresh weather data. Falls back to stale cache if API fails. */
	public function get_weather(bool $force_refresh = false): ?array {
		if ( ! self::is_configured() ) {
			return null;
		}

		if ( ! $force_refresh ) {
			$cached = get_transient(self::TRANSIENT_KEY);
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$settings = self::get_settings();
		$data     = $this->provider->fetch(
			(float) $settings['latitude'],
			(float) $settings['longitude'],
			$settings['timezone']
		);

		if ( $data !== null ) {
			set_transient(self::TRANSIENT_KEY, $data, self::CACHE_TTL);
			// Also persist in option as stale fallback.
			update_option('bm_weather_last_cache', $data);
			return $data;
		}

		// API failed – return last known data if available.
		return get_option('bm_weather_last_cache', null);
	}

	/** Refresh weather data (called by WP-Cron). */
	public function refresh(): void {
		$this->get_weather(true);
	}

	/** Delete cached data (e.g., after location change). */
	public static function clear_cache(): void {
		delete_transient(self::TRANSIENT_KEY);
	}
}
