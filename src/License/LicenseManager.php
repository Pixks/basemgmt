<?php

declare(strict_types=1);

namespace BaseMgmt\License;

defined('ABSPATH') || exit;

/**
 * High-level license state manager – singleton.
 *
 * Usage:
 *   LicenseManager::instance()->is_valid()   // true if license is active
 *   LicenseManager::instance()->get_status() // full status array from API/cache
 *
 * Per docs/wordpress-integration.md:
 * - Cache the last good result for up to 7-14 days (grace period from API).
 * - A temporary API outage must NOT block premium features.
 * - On confirmed license_expired / license_revoked / license_suspended:
 *   block updates and premium features, but never delete user data.
 */
final class LicenseManager {

	private static ?self $instance = null;
	private LicenseClient $client;

	/** Definitive "bad" error codes that disable premium features. */
	private const BLOCKING_CODES = [
		'license_expired',
		'license_revoked',
		'license_suspended',
	];

	private function __construct() {
		$this->client = new LicenseClient();
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// ── Bootstrap ─────────────────────────────────────────────────────────────

	/**
	 * Wire up cron heartbeat and WordPress update checks.
	 * Called from Bootstrap::init().
	 */
	public function init(): void {
		$this->client->register_cron();
		$this->client->hook_updates(plugin_basename(BASEMGMT_FILE));
	}

	// ── Status API ────────────────────────────────────────────────────────────

	/**
	 * Returns true when the license is considered valid.
	 *
	 * The result is based on the cached API response.
	 * A transport/network error is treated as "valid" (grace-period logic) to
	 * avoid locking out customers during API downtime.
	 * Only a confirmed blocking error code marks the license as invalid.
	 */
	public function is_valid(): bool {
		$status = $this->get_status();

		// API returned a confirmed blocking error.
		if ( isset($status['error']['code']) && in_array($status['error']['code'], self::BLOCKING_CODES, true) ) {
			return false;
		}

		// Explicit success from API.
		if ( ! empty($status['success']) ) {
			return true;
		}

		// Transport error or empty key – assume not valid (but don't block data).
		if ( '' === $this->client->get_license_key() ) {
			return false;
		}

		// Unknown failure: be lenient (cached grace period).
		return true;
	}

	/**
	 * Returns the full license status array from the API (or cache).
	 * Forces a fresh API call when $force is true.
	 */
	public function get_status(bool $force = false): array {
		if ( '' === $this->client->get_license_key() ) {
			return [
				'success' => false,
				'error'   => [
					'code'    => 'invalid_license_key',
					'message' => __('Nie skonfigurowano klucza licencji.', 'basemgmt'),
				],
			];
		}

		return $this->client->check($force);
	}

	/**
	 * Returns the blocking error code (e.g. 'license_expired') or empty string.
	 */
	public function get_blocking_error(): string {
		$status = $this->get_status();
		$code   = $status['error']['code'] ?? '';
		return in_array($code, self::BLOCKING_CODES, true) ? $code : '';
	}

	/**
	 * Returns the plan name from the last API response (e.g. 'starter', 'pro').
	 */
	public function get_plan(): string {
		return $this->client->get_plan();
	}

	/**
	 * Returns whether updates are allowed according to the last API response.
	 * Falls back to true when no cached data is available (grace period).
	 */
	public function updates_allowed(): bool {
		$status = $this->get_status();
		if ( isset($status['data']['updates_allowed']) ) {
			return (bool) $status['data']['updates_allowed'];
		}
		return ! empty($status['success']);
	}

	/**
	 * Returns whether support is active according to the last API response.
	 */
	public function support_active(): bool {
		$status = $this->get_status();
		if ( isset($status['data']['support_active']) ) {
			return (bool) $status['data']['support_active'];
		}
		return ! empty($status['success']);
	}

	/**
	 * Returns the channel the license server has locked this license to.
	 * 'beta' once switched, otherwise 'stable' or ''.
	 */
	public function get_active_channel(): string {
		return $this->client->get_active_channel();
	}

	/**
	 * Returns the comma-separated allowed channels from the API ('stable,beta').
	 */
	public function get_allowed_channels(): string {
		return $this->client->get_allowed_channels();
	}

	/** Expose the underlying client for direct API calls (activate/deactivate). */
	public function client(): LicenseClient {
		return $this->client;
	}
}
