<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Announcements\AnnouncementRepository;
use BaseMgmt\Modules\Camps\CampRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for managing announcements (Ogłoszenia).
 */
final class AnnouncementsPage {

	// ── Render ────────────────────────────────────────────────────────────────

	public function render(): void {
		Capabilities::require_admin();

		$action = sanitize_key($_GET['action'] ?? 'list');
		$id     = (int) ($_GET['id'] ?? 0);

		match ($action) {
			'new'   => $this->render_edit_form(null),
			'edit'  => $this->render_edit_form(AnnouncementRepository::get($id)),
			default => $this->render_list(),
		};
	}

	private function render_list(): void {
		$status = sanitize_key($_GET['filter_status'] ?? '');
		$page   = max(1, (int) ($_GET['paged'] ?? 1));

		$args          = ['per_page' => 20, 'page' => $page];
		if ( $status ) {
			$args['status'] = $status;
		}

		$announcements = AnnouncementRepository::get_all($args);
		$total         = AnnouncementRepository::count($status ? ['status' => $status] : []);
		$pages         = (int) ceil($total / 20);

		include BASEMGMT_DIR . 'templates/admin/announcements/list.php';
	}

	private function render_edit_form(?object $announcement): void {
		$camps       = CampRepository::get_all();
		$camp_target = $announcement ? AnnouncementRepository::get_camp_targets((int) $announcement->id) : [];

		include BASEMGMT_DIR . 'templates/admin/announcements/edit.php';
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_announcement');

		$id        = (int) ($_POST['announcement_id'] ?? 0);
		$is_global = isset($_POST['is_global']) ? 1 : 0;

		$valid_from  = sanitize_text_field(wp_unslash($_POST['valid_from']  ?? ''));
		$valid_until = sanitize_text_field(wp_unslash($_POST['valid_until'] ?? ''));

		// Ensure proper datetime format.
		if ( $valid_from  && ! str_contains($valid_from,  ':') ) $valid_from  .= ' 00:00:00';
		if ( $valid_until && ! str_contains($valid_until, ':') ) $valid_until .= ' 23:59:59';

		$data = [
			'title'          => sanitize_text_field(wp_unslash($_POST['title']   ?? '')),
			'content'        => wp_kses_post(wp_unslash($_POST['content']        ?? '')),
			'status'         => sanitize_key(wp_unslash($_POST['status']         ?? 'active')),
			'is_urgent'      => isset($_POST['is_urgent'])  ? 1 : 0,
			'priority'       => max(0, min(10, (int) ($_POST['priority']         ?? 0))),
			'valid_from'     => $valid_from,
			'valid_until'    => $valid_until,
			'is_global'      => $is_global,
			'attachment_url' => esc_url_raw(wp_unslash($_POST['attachment_url']  ?? '')),
			'camp_ids'       => array_map('intval', (array) ($_POST['camp_ids']  ?? [])),
		];

		if ( empty($data['title']) || empty($valid_from) || empty($valid_until) ) {
			AdminMenu::set_notice(__('Tytuł, data od i data do są wymagane.', 'basemgmt'), 'error');
			$this->redirect_back($id ? "basemgmt-announcements&action=edit&id=$id" : 'basemgmt-announcements&action=new');
			return;
		}

		if ( $id ) {
			AnnouncementRepository::update($id, $data);
			AdminMenu::set_notice(__('Ogłoszenie zaktualizowane.', 'basemgmt'));
		} else {
			$new_id = AnnouncementRepository::insert($data);
			AdminMenu::set_notice($new_id ? __('Ogłoszenie dodane.', 'basemgmt') : __('Błąd zapisu.', 'basemgmt'), $new_id ? 'success' : 'error');
		}

		$this->redirect_back('basemgmt-announcements');
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_ann_{$id}");

		if ( $id ) {
			AnnouncementRepository::delete($id);
			AdminMenu::set_notice(__('Ogłoszenie usunięte.', 'basemgmt'));
		}
		$this->redirect_back('basemgmt-announcements');
	}

	public function handle_approve(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_approve_ann_{$id}");

		if ( $id ) {
			AnnouncementRepository::approve($id, get_current_user_id());
			AdminMenu::set_notice(__('Ogłoszenie zatwierdzone.', 'basemgmt'));

			// Notify the submitter camp (future hook – extensible).
			do_action('bm_announcement_approved', $id);
		}
		$this->redirect_back('basemgmt-announcements');
	}

	private function redirect_back(string $page): void {
		wp_safe_redirect(admin_url("admin.php?page=$page"));
		exit;
	}
}
