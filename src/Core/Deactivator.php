<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

use BaseMgmt\Cron\Scheduler;
use BaseMgmt\License\LicenseClient;

defined('ABSPATH') || exit;

/**
 * Runs when the plugin is deactivated (NOT uninstalled).
 * Tables and data are preserved; only cron events are cleared.
 */
final class Deactivator {

	public static function deactivate(): void {
		// Deactivate the license on the server to free the activation slot.
		$lc = new LicenseClient();
		if ( '' !== $lc->get_license_key() && '' !== $lc->get_api_base() ) {
			$lc->deactivate();
		}

		Scheduler::clear_schedules();

		// Clear the license heartbeat cron.
		$hook = 'camplink_license_heartbeat';
		$ts   = wp_next_scheduled($hook);
		if ( $ts ) {
			wp_unschedule_event($ts, $hook);
		}

		flush_rewrite_rules();
	}
}
