<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Core\OperationLogger;

defined('ABSPATH') || exit;

/**
 * Admin page for viewing operation logs.
 */
final class LogsPage {

	public function render(): void {
		if ( ! current_user_can('manage_options') && ! current_user_can('manage_basemgmt') ) {
			wp_die(esc_html__('Brak uprawnień.', 'basemgmt'), esc_html__('Błąd', 'basemgmt'), ['response' => 403]);
		}

		$action = sanitize_key($_GET['bm_action'] ?? '');

		if ( $action === 'clear' ) {
			$this->handle_clear();
			return;
		}

		$filter_action    = sanitize_key($_GET['filter_action'] ?? '');
		$filter_date_from = sanitize_text_field($_GET['filter_date_from'] ?? '');
		$filter_date_to   = sanitize_text_field($_GET['filter_date_to']   ?? '');
		$page             = max(1, (int) ($_GET['paged'] ?? 1));
		$per_page         = 50;

		$filters = [];
		if ( $filter_action )    $filters['action']    = $filter_action;
		if ( $filter_date_from ) $filters['date_from'] = $filter_date_from;
		if ( $filter_date_to )   $filters['date_to']   = $filter_date_to;

		$logs       = OperationLogger::get_all($filters, $per_page, $page);
		$total      = OperationLogger::count($filters);
		$pages      = (int) ceil($total / $per_page);
		$action_types = OperationLogger::get_action_types();

		include BASEMGMT_DIR . 'templates/admin/logs/list.php';
	}

	private function handle_clear(): void {
		if ( ! current_user_can('manage_options') && ! current_user_can('manage_basemgmt') ) {
			wp_die(esc_html__('Brak uprawnień.', 'basemgmt'), esc_html__('Błąd', 'basemgmt'), ['response' => 403]);
		}
		$days = max(1, (int) ($_GET['days'] ?? 90));
		check_admin_referer("bm_clear_logs_{$days}");
		$deleted = OperationLogger::delete_older_than_days($days);
		AdminMenu::set_notice(
			sprintf(__('Usunięto %d wpisów logów starszych niż %d dni.', 'basemgmt'), $deleted, $days)
		);
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-logs'));
		exit;
	}
}
