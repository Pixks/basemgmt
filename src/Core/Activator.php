<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Cron\Scheduler;
use BaseMgmt\Database\Schema;

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
		BreakdanceIntegration::create_directories();

		update_option('basemgmt_db_version', BASEMGMT_VERSION);

		// Flush rewrite rules so REST routes are available immediately.
		flush_rewrite_rules();
	}
}
