<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

use BaseMgmt\Cron\Scheduler;

defined('ABSPATH') || exit;

/**
 * Runs when the plugin is deactivated (NOT uninstalled).
 * Tables and data are preserved; only cron events are cleared.
 */
final class Deactivator {

	public static function deactivate(): void {
		Scheduler::clear_schedules();
		flush_rewrite_rules();
	}
}
