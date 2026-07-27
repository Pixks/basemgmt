<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Camps\CampRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for managing camps (Obozy).
 * Handles list view, edit/create form, and form submissions.
 */
final class CampsPage {

	// ── Render ────────────────────────────────────────────────────────────────

	public function render(): void {
		Capabilities::require_admin();

		$action = sanitize_key($_GET['action'] ?? 'list');
		$id     = (int) ($_GET['id'] ?? 0);

		match ($action) {
			'new'    => $this->render_edit_form(null),
			'edit'   => $this->render_edit_form(CampRepository::get($id)),
			default  => $this->render_list(),
		};
	}

	private function render_list(): void {
		$status = sanitize_key($_GET['filter_status'] ?? '');
		$page   = max(1, (int) ($_GET['paged'] ?? 1));

		$args  = ['status' => $status, 'per_page' => 20, 'page' => $page];
		$camps = CampRepository::get_all($args);
		$total = CampRepository::count(['status' => $status]);
		$pages = (int) ceil($total / 20);

		include BASEMGMT_DIR . 'templates/admin/camps/list.php';
	}

	private function render_edit_form(?object $camp): void {
		include BASEMGMT_DIR . 'templates/admin/camps/edit.php';
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp');

		$id   = (int) ($_POST['camp_id'] ?? 0);
		$data = [
			'name'       => sanitize_text_field(wp_unslash($_POST['name']       ?? '')),
			'start_date' => sanitize_text_field(wp_unslash($_POST['start_date'] ?? '')),
			'end_date'   => sanitize_text_field(wp_unslash($_POST['end_date']   ?? '')),
			'status'     => sanitize_key(wp_unslash($_POST['status']            ?? 'active')),
		];

		if ( empty($data['name']) ) {
			AdminMenu::set_notice(__('Nazwa obozu jest wymagana.', 'basemgmt'), 'error');
			$this->redirect_back($id ? "basemgmt-camps&action=edit&id=$id" : 'basemgmt-camps&action=new');
			return;
		}

		if ( $id ) {
			CampRepository::update($id, $data);
			AdminMenu::set_notice(__('Obóz zaktualizowany.', 'basemgmt'));
		} else {
			$new_id = CampRepository::insert($data);
			AdminMenu::set_notice($new_id ? __('Obóz dodany.', 'basemgmt') : __('Błąd zapisu.', 'basemgmt'), $new_id ? 'success' : 'error');
		}

		$this->redirect_back('basemgmt-camps');
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_camp_{$id}");

		if ( $id ) {
			CampRepository::delete($id);
			AdminMenu::set_notice(__('Obóz usunięty.', 'basemgmt'));
		}
		$this->redirect_back('basemgmt-camps');
	}

	// ── Redirect ─────────────────────────────────────────────────────────────

	private function redirect_back(string $page): void {
		wp_safe_redirect(admin_url("admin.php?page=$page"));
		exit;
	}
}
