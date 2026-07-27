<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Camps\StaffRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for managing camp staff (Kadra).
 */
final class StaffPage {

	// ── Render ────────────────────────────────────────────────────────────────

	public function render(): void {
		Capabilities::require_admin();

		$action = sanitize_key($_GET['action'] ?? 'list');
		$id     = (int) ($_GET['id'] ?? 0);

		match ($action) {
			'new'   => $this->render_edit_form(null),
			'edit'  => $this->render_edit_form(StaffRepository::get($id)),
			default => $this->render_list(),
		};
	}

	private function render_list(): void {
		$camp_id = (int) ($_GET['filter_camp'] ?? 0);
		$page    = max(1, (int) ($_GET['paged'] ?? 1));

		$args    = ['per_page' => 20, 'page' => $page];
		if ( $camp_id ) {
			$args['camp_id'] = $camp_id;
		}

		$staff_list = StaffRepository::get_all($args);
		$total      = StaffRepository::count($camp_id ? ['camp_id' => $camp_id] : []);
		$pages      = (int) ceil($total / 20);
		$camps      = CampRepository::get_all();

		include BASEMGMT_DIR . 'templates/admin/staff/list.php';
	}

	private function render_edit_form(?object $member): void {
		$camps = CampRepository::get_all();
		include BASEMGMT_DIR . 'templates/admin/staff/edit.php';
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_staff');

		$id = (int) ($_POST['staff_id'] ?? 0);

		$data = [
			'camp_id'      => (int) ($_POST['camp_id']      ?? 0),
			'first_name'   => sanitize_text_field(wp_unslash($_POST['first_name']   ?? '')),
			'last_name'    => sanitize_text_field(wp_unslash($_POST['last_name']    ?? '')),
			'email'        => sanitize_email(wp_unslash($_POST['email']             ?? '')),
			'phone'        => sanitize_text_field(wp_unslash($_POST['phone']        ?? '')),
			'role_in_camp' => sanitize_text_field(wp_unslash($_POST['role_in_camp'] ?? '')),
			'is_active'    => isset($_POST['is_active']) ? 1 : 0,
		];

		$security_code = sanitize_text_field(wp_unslash($_POST['security_code'] ?? ''));

		if ( ! empty($security_code) ) {
			$data['security_code'] = $security_code;
		}

		if ( empty($data['first_name']) || empty($data['last_name']) || empty($data['camp_id']) ) {
			AdminMenu::set_notice(__('Imię, nazwisko i obóz są wymagane.', 'basemgmt'), 'error');
			$this->redirect_back($id ? "basemgmt-staff&action=edit&id=$id" : 'basemgmt-staff&action=new');
			return;
		}

		if ( $id ) {
			StaffRepository::update($id, $data);
			AdminMenu::set_notice(__('Osoba zaktualizowana.', 'basemgmt'));
		} else {
			if ( empty($security_code) ) {
				AdminMenu::set_notice(__('Kod bezpieczeństwa jest wymagany przy dodawaniu nowej osoby.', 'basemgmt'), 'error');
				$this->redirect_back('basemgmt-staff&action=new');
				return;
			}
			$new_id = StaffRepository::insert($data);
			AdminMenu::set_notice($new_id ? __('Osoba dodana.', 'basemgmt') : __('Błąd zapisu.', 'basemgmt'), $new_id ? 'success' : 'error');
		}

		$this->redirect_back('basemgmt-staff');
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_staff_{$id}");

		if ( $id ) {
			StaffRepository::delete($id);
			AdminMenu::set_notice(__('Osoba usunięta.', 'basemgmt'));
		}
		$this->redirect_back('basemgmt-staff');
	}

	public function handle_toggle_active(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_toggle_staff_{$id}");

		if ( $id ) {
			StaffRepository::toggle_active($id);
			AdminMenu::set_notice(__('Status zmieniony.', 'basemgmt'));
		}
		$this->redirect_back('basemgmt-staff');
	}

	public function handle_reset_code(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_reset_staff_code');

		$id   = (int) ($_POST['staff_id'] ?? 0);
		$code = sanitize_text_field(wp_unslash($_POST['new_code'] ?? ''));

		if ( ! $id || strlen($code) < 4 ) {
			AdminMenu::set_notice(__('Kod musi mieć co najmniej 4 znaki.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-staff&action=edit&id=$id");
			return;
		}

		StaffRepository::set_security_code($id, $code);
		AdminMenu::set_notice(__('Kod bezpieczeństwa zresetowany.', 'basemgmt'));
		$this->redirect_back("basemgmt-staff&action=edit&id=$id");
	}

	// ── Redirect ─────────────────────────────────────────────────────────────

	private function redirect_back(string $page): void {
		wp_safe_redirect(admin_url("admin.php?page=$page"));
		exit;
	}
}
