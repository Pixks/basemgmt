<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Admin page for managing Declaration Templates (Organizacja → Deklaracje).
 */
final class OrgDeclarationsPage {

	// ── Render ────────────────────────────────────────────────────────────────

	public function render(): void {
		Capabilities::require_admin();
		$action = sanitize_key($_GET['action'] ?? 'list');
		$id     = (int) ($_GET['id'] ?? 0);

		match ($action) {
			'edit', 'new' => $this->render_edit($id),
			default       => $this->render_list(),
		};
	}

	private function render_list(): void {
		global $wpdb;
		$table     = Schema::table('decl_templates');
		$templates = $table ? $wpdb->get_results("SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC") : [];
		include BASEMGMT_DIR . 'templates/admin/org/declarations/list.php';
	}

	private function render_edit(int $id): void {
		global $wpdb;
		$table    = Schema::table('decl_templates');
		$template = ($id > 0 && $table) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)) : null;
		include BASEMGMT_DIR . 'templates/admin/org/declarations/edit.php';
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_decl_template');

		global $wpdb;
		$table = Schema::table('decl_templates');
		$id    = (int) ($_POST['decl_tpl_id'] ?? 0);

		$title       = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
		$description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
		$html_content = wp_kses_post(wp_unslash($_POST['html_content'] ?? ''));
		$auto_add    = (int) ! empty($_POST['auto_add']);
		$sort_order  = (int) ($_POST['sort_order'] ?? 0);

		if ( empty($title) ) {
			AdminMenu::set_notice(__('Tytuł jest wymagany.', 'basemgmt'), 'error');
			$this->redirect($id ? "basemgmt-org-declarations&action=edit&id={$id}" : 'basemgmt-org-declarations&action=new');
			return;
		}

		$data = [
			'title'        => $title,
			'description'  => $description,
			'html_content' => $html_content,
			'auto_add'     => $auto_add,
			'sort_order'   => $sort_order,
		];

		if ( $id > 0 ) {
			$wpdb->update($table, $data, ['id' => $id]);
		} else {
			$data['created_by'] = get_current_user_id();
			$wpdb->insert($table, $data);
		}

		AdminMenu::set_notice(__('Szablon deklaracji zapisany.', 'basemgmt'));
		$this->redirect('basemgmt-org-declarations');
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_decl_template_{$id}");

		global $wpdb;
		$wpdb->delete(Schema::table('decl_templates'), ['id' => $id]);
		AdminMenu::set_notice(__('Szablon deklaracji usunięty.', 'basemgmt'));
		$this->redirect('basemgmt-org-declarations');
	}

	/**
	 * Returns all declaration templates (optionally only auto_add ones).
	 */
	public static function get_all(bool $auto_add_only = false): array {
		global $wpdb;
		$table = Schema::table('decl_templates');
		if ( ! $table ) return [];
		$where = $auto_add_only ? 'WHERE auto_add = 1' : '';
		return $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY sort_order ASC, id ASC") ?: [];
	}

	private function redirect(string $page_slug): void {
		wp_safe_redirect(admin_url('admin.php?page=' . $page_slug));
		exit;
	}
}
