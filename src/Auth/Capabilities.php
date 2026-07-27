<?php

declare(strict_types=1);

namespace BaseMgmt\Auth;

defined('ABSPATH') || exit;

/**
 * Manages custom plugin capabilities.
 * Registered to the WP 'init' action so they exist for all requests.
 */
final class Capabilities {

	/** Custom capabilities used by this plugin. */
	public const CAPS = [
		'manage_basemgmt',
		'manage_bm_camps',
		'manage_bm_staff',
		'manage_bm_announcements',
	];

	/** Called on 'init' hook by Bootstrap. */
	public function register(): void {
		// Nothing extra at runtime; capabilities are added to roles on activation.
	}

	/** Called once during plugin activation. */
	public static function add_to_admin_role(): void {
		$role = get_role('administrator');
		if ( ! $role ) {
			return;
		}
		foreach ( self::CAPS as $cap ) {
			$role->add_cap($cap, true);
		}
	}

	/** Check if the current WP user has a given plugin capability. */
	public static function current_user_can(string $cap): bool {
		return current_user_can($cap);
	}

	/** Verify current user can manage plugin; wp_die() on failure. */
	public static function require_admin(): void {
		if ( ! current_user_can('manage_basemgmt') ) {
			wp_die(
				esc_html__('Brak uprawnień.', 'basemgmt'),
				esc_html__('Błąd', 'basemgmt'),
				['response' => 403]
			);
		}
	}
}
