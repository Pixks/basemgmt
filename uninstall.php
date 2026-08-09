<?php
/**
 * Runs only when the plugin is uninstalled via WordPress.
 * Drops all plugin tables and removes plugin options.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

$tables = [
	// Reservations (drop first – FK-like references)
	'bm_resource_blocks',
	'bm_resource_reservations',
	'bm_resources',
	// Extended camp case file
	'bm_camp_closures',
	'bm_camp_settlement_issues',
	'bm_camp_settlement_lines',
	'bm_camp_settlements',
	'bm_camp_pricing_rules',
	'bm_camp_pricing_tables',
	'bm_camp_service_usages',
	'bm_camp_actual_meals',
	'bm_camp_actual_stays',
	'bm_camp_payments',
	'bm_camp_payment_schedules',
	'bm_camp_document_versions',
	'bm_camp_documents',
	'bm_camp_prearrival',
	'bm_camp_checklist_items',
	'bm_camp_organizers',
	'bm_camp_case_history',
	'bm_camp_cases',
	// Schedule
	'bm_plan_camps',
	'bm_plan_item_revisions',
	'bm_plan_items',
	'bm_plan_headers',
	// Existing tables
	'bm_weather_alerts',
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
delete_option('basemgmt_weather_settings');
delete_option('basemgmt_imgw_settings');
delete_option('bm_weather_last_cache');
delete_option('bm_imgw_last_sync');
delete_option('bm_imgw_last_sync_log');
delete_transient('bm_weather_cache');

// Remove scheduled cron events.
wp_clear_scheduled_hook('bm_daily_reminders');
wp_clear_scheduled_hook('bm_expire_announcements');
wp_clear_scheduled_hook('bm_cleanup_sessions');
wp_clear_scheduled_hook('bm_refresh_weather');
wp_clear_scheduled_hook('bm_expire_weather_alerts');
wp_clear_scheduled_hook('bm_check_missing_reports');

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
