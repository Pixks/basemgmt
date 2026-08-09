<?php
/**
 * Plugin Name:       CampLink
 * Plugin URI:        https://pixks.pl
 * Description:       Modularny system zarządzania obozami — rezerwacje, grafiki, wyżywienie, powiadomienia e-mail, dzienniki operacji i eksport PDF w jednej wtyczce WordPress.
 * Version:           2.0.0-PRE1
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Pixks - Jakub Boiński
 * Author URI:        https://pixks.pl
 * Text Domain:       basemgmt
 * Domain Path:       /languages
 * License:           MIT + Commons Clause
 * License URI:       https://commonsclause.com
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// ── Plugin constants ──────────────────────────────────────────────────────────
define('BASEMGMT_VERSION',        '2.0.0-PRE1');
define('BASEMGMT_FILE',           __FILE__);
define('BASEMGMT_DIR',            plugin_dir_path(__FILE__));
define('BASEMGMT_URL',            plugin_dir_url(__FILE__));
define('BASEMGMT_SESSION_COOKIE', 'bm_session');
define('BASEMGMT_SESSION_TTL',    8 * HOUR_IN_SECONDS);   // 8 h
define('BASEMGMT_MAX_ATTEMPTS',   3);
define('BASEMGMT_LOCKOUT_TTL',    15 * MINUTE_IN_SECONDS); // 15 min

// ── PSR-4 autoloader  (BaseMgmt\ → src/) ─────────────────────────────────────
spl_autoload_register(static function (string $class): void {
	if ( ! str_starts_with($class, 'BaseMgmt\\') ) {
		return;
	}
	$rel  = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 9));
	$file = BASEMGMT_DIR . 'src' . DIRECTORY_SEPARATOR . $rel . '.php';
	if ( is_readable($file) ) {
		require_once $file;
	}
});

// ── Lifecycle hooks ───────────────────────────────────────────────────────────
register_activation_hook(__FILE__,   ['BaseMgmt\\Core\\Activator',   'activate']);
register_deactivation_hook(__FILE__, ['BaseMgmt\\Core\\Deactivator', 'deactivate']);

// ── Boot ──────────────────────────────────────────────────────────────────────
add_action('plugins_loaded', static function (): void {
	BaseMgmt\Core\Bootstrap::instance()->init();
}, 0);
