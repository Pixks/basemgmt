<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Help\HelpRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for Pomoc (Help) module.
 */
final class HelpPage {

	public function render(): void {
		Capabilities::require_admin();

		$action = sanitize_key($_GET['bm_action'] ?? '');
		$id     = (int) ($_GET['id'] ?? 0);

		match ($action) {
			'edit'  => $this->render_edit($id),
			'new'   => $this->render_edit(0),
			default => $this->render_list(),
		};
	}

	private function render_list(): void {
		$filter_type   = sanitize_key($_GET['filter_type']   ?? '');
		$filter_status = sanitize_key($_GET['filter_status'] ?? '');

		$filters = [];
		if ( $filter_type )   $filters['type']   = $filter_type;
		if ( $filter_status ) $filters['status']  = $filter_status;

		$articles = HelpRepository::get_all($filters);
		$types    = HelpRepository::TYPES;
		$statuses = HelpRepository::STATUSES;

		include BASEMGMT_DIR . 'templates/admin/help/list.php';
	}

	private function render_edit(int $id): void {
		$article = $id ? HelpRepository::get($id) : null;
		$types   = HelpRepository::TYPES;
		$statuses = HelpRepository::STATUSES;
		include BASEMGMT_DIR . 'templates/admin/help/edit.php';
	}

	// ── Form handlers ─────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_help');

		$id = (int) ($_POST['article_id'] ?? 0);

		if ( empty($_POST['title']) ) {
			AdminMenu::set_notice(__('Tytuł jest wymagany.', 'basemgmt'), 'error');
			wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=basemgmt-help&bm_action=new'));
			exit;
		}

		$saved_id = HelpRepository::save([
			'id'         => $id,
			'title'      => sanitize_text_field($_POST['title'] ?? ''),
			'content'    => wp_kses_post($_POST['content'] ?? ''),
			'excerpt'    => sanitize_textarea_field($_POST['excerpt'] ?? ''),
			'category'   => sanitize_text_field($_POST['category'] ?? ''),
			'type'       => sanitize_key($_POST['type'] ?? 'article'),
			'status'     => sanitize_key($_POST['status'] ?? 'published'),
			'is_pinned'  => (int) ($_POST['is_pinned'] ?? 0),
			'is_alarm'   => (int) ($_POST['is_alarm'] ?? 0),
			'sort_order' => (int) ($_POST['sort_order'] ?? 0),
		]);

		AdminMenu::set_notice(__('Wpis pomocy zapisany.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-help&bm_action=edit&id=' . $saved_id));
		exit;
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer('bm_delete_help_' . $id);
		HelpRepository::delete($id);
		AdminMenu::set_notice(__('Wpis pomocy usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-help'));
		exit;
	}
}
