<?php
/**
 * Runs only when the plugin is uninstalled via WordPress.
 * Drops all plugin tables and removes plugin options.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

$tables = [
	'bm_sessions',
	'bm_announcement_camps',
	'bm_announcements',
	'bm_daily_counts',
	'bm_staff',
	'bm_camps',
];

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}{$table}`" );
}

delete_option('basemgmt_db_version');
delete_option('basemgmt_settings');

// Remove scheduled cron events.
wp_clear_scheduled_hook('bm_daily_reminders');
wp_clear_scheduled_hook('bm_expire_announcements');
wp_clear_scheduled_hook('bm_cleanup_sessions');

// Remove custom capabilities from all roles.
$caps = [
	'manage_basemgmt',
	'manage_bm_camps',
	'manage_bm_staff',
	'manage_bm_announcements',
];
foreach ( wp_roles()->roles as $role_name => $role_data ) {
	$role = get_role($role_name);
	if ( $role ) {
		foreach ( $caps as $cap ) {
			$role->remove_cap($cap);
		}
	}
}
