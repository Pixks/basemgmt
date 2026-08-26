<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Cron\Scheduler;
use BaseMgmt\Database\Schema;
use BaseMgmt\License\LicenseClient;

defined('ABSPATH') || exit;

/**
 * Runs once when the plugin is activated.
 * Creates database tables, registers capabilities and schedules cron events.
 */
final class Activator {

	public static function activate(): void {
		Schema::create_tables();
		Capabilities::add_to_admin_role();
		Scheduler::register_schedules();
		MoCompiler::compile_all();

		update_option('basemgmt_db_version', BASEMGMT_VERSION);

		// If a license key is already stored (e.g. re-activation), re-activate it.
		$lc = new LicenseClient();
		if ( '' !== $lc->get_license_key() && '' !== $lc->get_api_base() ) {
			$lc->activate();
		}

		// Flush rewrite rules so REST routes are available immediately.
		flush_rewrite_rules();
	}
}
