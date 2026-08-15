<?php
/**
 * Runs only when the plugin is uninstalled via WordPress.
 * Drops all plugin tables and removes plugin options.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

$tables = [
	// ── Leaf / child tables first (FK-like dependencies) ─────────────────────
	// Reservations
	'bm_resource_blocks',
	'bm_resource_reservations',
	'bm_resources',
	// Forms & submissions
	'bm_submission_history',
	'bm_submission_attachments',
	'bm_submissions',
	'bm_form_camps',
	'bm_form_fields',
	'bm_forms',
	// Document attachments (shared by doc_library + decl_docs)
	'bm_doc_attachments',
	// Declaration documents & templates
	'bm_camp_decl_docs',
	'bm_decl_templates',
	// Camp declarations (per-day lines first)
	'bm_camp_declaration_accommodation_lines',
	'bm_camp_declaration_diet_lines',
	'bm_camp_declaration_days',
	'bm_camp_declarations',
	// Camp equipment & damages
	'bm_camp_equipment',
	'bm_camp_damages',
	// Camp workflow
	'bm_camp_workflow_events',
	// Extended camp case / finance
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
	// ── Schedule ─────────────────────────────────────────────────────────────
	'bm_plan_camps',
	'bm_plan_item_revisions',
	'bm_plan_items',
	'bm_plan_headers',
	'bm_plan_template_items',
	'bm_plan_templates',
	// ── Menu ─────────────────────────────────────────────────────────────────
	'bm_meal_template_items',
	'bm_meal_templates',
	'bm_meal_diet_costs',
	'bm_meal_diets',
	'bm_meal_locations',
	'bm_meal_items',
	'bm_meal_days',
	// ── Communication ────────────────────────────────────────────────────────
	'bm_conv_messages',
	'bm_conv_threads',
	// ── Help ─────────────────────────────────────────────────────────────────
	'bm_help_articles',
	// ── Org module ───────────────────────────────────────────────────────────
	'bm_payment_pkg_diet_slots',
	'bm_payment_pkg_accom',
	'bm_payment_package_lines',
	'bm_payment_packages',
	'bm_task_templates',
	'bm_accommodation_types',
	'bm_doc_library',
	'bm_doc_templates',
	// ── Core ─────────────────────────────────────────────────────────────────
	'bm_announcement_camps',
	'bm_announcements',
	'bm_operation_logs',
	'bm_weather_alerts',
	'bm_sessions',
	'bm_daily_counts',
	'bm_staff',
	'bm_camps',
];

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}{$table}`" );
}

// ── Options ───────────────────────────────────────────────────────────────────
delete_option('basemgmt_db_version');
delete_option('basemgmt_settings');
delete_option('basemgmt_email_settings');
delete_option('basemgmt_pdf_settings');
delete_option('basemgmt_weather_settings');
delete_option('basemgmt_imgw_settings');
delete_option('basemgmt_license_key');
delete_option('basemgmt_license_api_url');
delete_option('bm_weather_last_cache');
delete_option('bm_imgw_last_sync');
delete_option('bm_imgw_last_sync_log');
delete_option('bm_missing_report_emails');
delete_option('bm_report_emails');
delete_option('bm_report_interval');
delete_option('bm_lockout_minutes');

// ── Transients ────────────────────────────────────────────────────────────────
delete_transient('bm_weather_cache');
delete_transient('basemgmt_license_status_cache');

// ── Cron events ───────────────────────────────────────────────────────────────
$cron_hooks = [
	'bm_daily_reminders',
	'bm_expire_announcements',
	'bm_cleanup_sessions',
	'bm_refresh_weather',
	'bm_expire_weather_alerts',
	'bm_check_missing_reports',
	'bm_sync_imgw_alerts',
	'bm_expire_reservations',
	'bm_periodic_staff_report',
	'bm_camp_workflow_check',
	'camplink_license_heartbeat',
];
foreach ( $cron_hooks as $hook ) {
	wp_clear_scheduled_hook($hook);
}

// ── Custom capabilities ───────────────────────────────────────────────────────
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
