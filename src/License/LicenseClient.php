<?php

declare(strict_types=1);

namespace BaseMgmt\License;

defined('ABSPATH') || exit;

/**
 * HTTP client for the Pixks LicenseManager REST API.
 *
 * Wraps all API calls (activate, deactivate, check, heartbeat,
 * checkForUpdate, hookUpdates, registerCron) for the "camplink" product slug.
 * Based on the reference implementation in docs/wordpress-license-client.php
 * of the Pixks/licensemanager repository.
 */
final class LicenseClient {

	private const PRODUCT_SLUG = 'camplink';
	private const OPTION_KEY   = 'basemgmt_license_key';
	private const OPTION_URL   = 'basemgmt_license_api_url';
	private const CACHE_KEY    = 'basemgmt_license_status_cache';

	// ── Accessors ────────────────────────────────────────────────────────────

	public function get_license_key(): string {
		return (string) get_option(self::OPTION_KEY, '');
	}

	public function save_license_key(string $license_key): void {
		update_option(self::OPTION_KEY, sanitize_text_field($license_key), false);
	}

	public function get_api_base(): string {
		return rtrim((string) get_option(self::OPTION_URL, ''), '/');
	}

	public function save_api_base(string $url): void {
		update_option(self::OPTION_URL, esc_url_raw($url), false);
	}

	public function get_canonical_domain(): string {
		$host = wp_parse_url(home_url(), PHP_URL_HOST) ?: '';
		return trim((string) preg_replace('/^www\./', '', strtolower($host)), '.');
	}

	// ── API calls ────────────────────────────────────────────────────────────

	/** POST /api/v1/licenses/activate */
	public function activate(): array {
		delete_transient(self::CACHE_KEY);
		return $this->post('/api/v1/licenses/activate', [
			'product_slug' => self::PRODUCT_SLUG,
			'license_key'  => $this->get_license_key(),
			'domain'       => $this->get_canonical_domain(),
			'site_url'     => home_url(),
			'fingerprint'  => hash('sha256', home_url() . ABSPATH),
		]);
	}

	/** POST /api/v1/licenses/deactivate */
	public function deactivate(): array {
		delete_transient(self::CACHE_KEY);
		return $this->post('/api/v1/licenses/deactivate', [
			'product_slug' => self::PRODUCT_SLUG,
			'license_key'  => $this->get_license_key(),
			'domain'       => $this->get_canonical_domain(),
			'reason'       => 'wp_admin_request',
		]);
	}

	/**
	 * POST /api/v1/licenses/check
	 * Result is cached in a transient for the grace period (min. 6 h).
	 */
	public function check(bool $force = false): array {
		$cached = get_transient(self::CACHE_KEY);
		if ( ! $force && is_array($cached) && ! empty($cached['valid_until']) && $cached['valid_until'] > time() ) {
			return $cached;
		}

		$response = $this->post('/api/v1/licenses/check', [
			'product_slug' => self::PRODUCT_SLUG,
			'license_key'  => $this->get_license_key(),
			'domain'       => $this->get_canonical_domain(),
		]);

		if ( ! empty($response['success']) ) {
			$ttl                    = max(6 * HOUR_IN_SECONDS, ((int) ($response['data']['grace_period_days'] ?? 10)) * DAY_IN_SECONDS);
			$response['valid_until'] = time() + $ttl;
			set_transient(self::CACHE_KEY, $response, $ttl);
		}

		return $response;
	}

	/** POST /api/v1/licenses/heartbeat */
	public function heartbeat(): array {
		return $this->post('/api/v1/licenses/heartbeat', [
			'product_slug' => self::PRODUCT_SLUG,
			'license_key'  => $this->get_license_key(),
			'domain'       => $this->get_canonical_domain(),
		]);
	}

	/** POST /api/v1/updates/check */
	public function check_for_update(string $channel = 'stable'): array {
		return $this->post('/api/v1/updates/check', [
			'product_slug'    => self::PRODUCT_SLUG,
			'license_key'     => $this->get_license_key(),
			'domain'          => $this->get_canonical_domain(),
			'current_version' => BASEMGMT_VERSION,
			'channel'         => $channel,
		]);
	}

	/**
	 * Register WP-Cron heartbeat (twicedaily).
	 * Call from Bootstrap or Activator once.
	 */
	public function register_cron(): void {
		$hook = self::PRODUCT_SLUG . '_license_heartbeat';
		if ( ! wp_next_scheduled($hook) ) {
			wp_schedule_event(time() + HOUR_IN_SECONDS, 'twicedaily', $hook);
		}
		add_action($hook, [$this, 'heartbeat']);
	}

	/**
	 * Hook into WordPress update checks to deliver plugin updates.
	 *
	 * @param string $plugin_file The plugin's main file path relative to wp-content/plugins/
	 *                            (e.g. "basemgmt/basemgmt.php").
	 */
	public function hook_updates(string $plugin_file): void {
		add_filter('pre_set_site_transient_update_plugins', function ($transient) use ($plugin_file) {
			if ( ! is_object($transient) ) {
				$transient = new \stdClass();
			}

			$response = $this->check_for_update();
			if ( ! empty($response['success']) && ! empty($response['data']['update_available']) ) {
				$data                             = $response['data'];
				$transient->response[$plugin_file] = (object) [
					'slug'         => dirname($plugin_file),
					'plugin'       => $plugin_file,
					'new_version'  => $data['latest_version'],
					'package'      => $data['download_url'],
					'tested'       => $data['tested_up_to'] ?? $data['min_wordpress_version'] ?? '',
					'requires_php' => $data['min_php_version'] ?? '',
				];
			}

			return $transient;
		});
	}

	// ── HTTP transport ────────────────────────────────────────────────────────

	private function post(string $path, array $payload): array {
		$api_base = $this->get_api_base();
		if ( '' === $api_base ) {
			return [
				'success' => false,
				'error'   => [
					'code'    => 'invalid_request',
					'message' => __('Brak skonfigurowanego URL serwera licencji.', 'basemgmt'),
				],
			];
		}

		$response = wp_remote_post(
			$api_base . $path,
			[
				'timeout' => 15,
				'headers' => [
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode($payload),
			]
		);

		if ( is_wp_error($response) ) {
			return [
				'success' => false,
				'error'   => [
					'code'    => 'transport_error',
					'message' => $response->get_error_message(),
				],
			];
		}

		$body = json_decode((string) wp_remote_retrieve_body($response), true);

		return is_array($body)
			? $body
			: [
				'success' => false,
				'error'   => [
					'code'    => 'invalid_response',
					'message' => __('Nieoczekiwana odpowiedź serwera licencji.', 'basemgmt'),
				],
			];
	}
}
