<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Database\Schema;
use BaseMgmt\Modules\Camps\CampRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for managing Declarations (Organizacja → Deklaracje).
 * Declarations are regular documents (not templates) — they can have
 * an uploaded file and/or optional HTML content, without variable substitution.
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
		$table        = Schema::table('decl_templates');
		$declarations = $table ? $wpdb->get_results("SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC") : [];
		$camps        = CampRepository::get_all();
		include BASEMGMT_DIR . 'templates/admin/org/declarations/list.php';
	}

	private function render_edit(int $id): void {
		global $wpdb;
		$table       = Schema::table('decl_templates');
		$declaration = ($id > 0 && $table) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)) : null;

		// Load attachments for this declaration
		$attachments = [];
		if ( $id > 0 ) {
			$att_table   = Schema::table('doc_attachments');
			$attachments = $att_table ? $wpdb->get_results($wpdb->prepare(
				"SELECT * FROM {$att_table} WHERE parent_type = 'decl' AND parent_id = %d ORDER BY id ASC",
				$id
			)) : [];
		}

		include BASEMGMT_DIR . 'templates/admin/org/declarations/edit.php';
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_decl_template');

		global $wpdb;
		$table = Schema::table('decl_templates');
		$id    = (int) ($_POST['decl_tpl_id'] ?? 0);

		$title        = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
		$description  = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
		$html_content = wp_kses_post(wp_unslash($_POST['html_content'] ?? ''));
		$auto_add     = (int) ! empty($_POST['auto_add']);
		$sort_order   = (int) ($_POST['sort_order'] ?? 0);
		$file_id      = (int) ($_POST['file_id'] ?? 0);
		$file_url     = esc_url_raw(wp_unslash($_POST['file_url'] ?? ''));
		$file_name    = sanitize_file_name(wp_unslash($_POST['file_name'] ?? ''));

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
			'file_id'      => $file_id ?: null,
			'file_url'     => $file_url,
			'file_name'    => $file_name,
		];

		if ( $id > 0 ) {
			$wpdb->update($table, $data, ['id' => $id]);
		} else {
			$data['created_by'] = get_current_user_id();
			$wpdb->insert($table, $data);
			$id = (int) $wpdb->insert_id;
		}

		AdminMenu::set_notice(__('Deklaracja zapisana.', 'basemgmt'));
		$this->redirect("basemgmt-org-declarations&action=edit&id={$id}");
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_decl_template_{$id}");

		global $wpdb;
		$wpdb->delete(Schema::table('decl_templates'), ['id' => $id]);

		// Clean up attachments
		$wpdb->delete(Schema::table('doc_attachments'), ['parent_type' => 'decl', 'parent_id' => $id]);

		AdminMenu::set_notice(__('Deklaracja usunięta.', 'basemgmt'));
		$this->redirect('basemgmt-org-declarations');
	}

	public function handle_add_attachment(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_add_decl_attachment');

		$decl_id   = (int) ($_POST['decl_id'] ?? 0);
		$file_id   = (int) ($_POST['file_id'] ?? 0);
		$file_url  = esc_url_raw(wp_unslash($_POST['file_url'] ?? ''));
		$file_name = sanitize_file_name(wp_unslash($_POST['file_name'] ?? ''));

		if ( $decl_id <= 0 || empty($file_url) ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane załącznika.', 'basemgmt'), 'error');
			$this->redirect("basemgmt-org-declarations&action=edit&id={$decl_id}");
			return;
		}

		global $wpdb;
		$wpdb->insert(Schema::table('doc_attachments'), [
			'parent_type' => 'decl',
			'parent_id'   => $decl_id,
			'file_id'     => $file_id ?: null,
			'file_url'    => $file_url,
			'file_name'   => $file_name,
			'uploaded_by' => get_current_user_id(),
		]);

		AdminMenu::set_notice(__('Załącznik dodany.', 'basemgmt'));
		$this->redirect("basemgmt-org-declarations&action=edit&id={$decl_id}");
	}

	public function handle_delete_attachment(): void {
		Capabilities::require_admin();
		$att_id  = (int) ($_GET['att_id'] ?? 0);
		$decl_id = (int) ($_GET['decl_id'] ?? 0);
		check_admin_referer("bm_delete_decl_attachment_{$att_id}");

		global $wpdb;
		$wpdb->delete(Schema::table('doc_attachments'), ['id' => $att_id, 'parent_type' => 'decl', 'parent_id' => $decl_id]);

		AdminMenu::set_notice(__('Załącznik usunięty.', 'basemgmt'));
		$this->redirect("basemgmt-org-declarations&action=edit&id={$decl_id}");
	}

	public function handle_push_to_camp(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_push_decl_to_camp');

		$decl_id = (int) ($_POST['decl_id'] ?? 0);
		$camp_id = (int) ($_POST['camp_id'] ?? 0);

		if ( $decl_id <= 0 || $camp_id <= 0 ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane.', 'basemgmt'), 'error');
			$this->redirect('basemgmt-org-declarations');
			return;
		}

		global $wpdb;
		$tpl  = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Schema::table('decl_templates') . " WHERE id = %d", $decl_id));
		$camp = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Schema::table('camps') . " WHERE id = %d", $camp_id));

		if ( ! $tpl || ! $camp ) {
			AdminMenu::set_notice(__('Nie znaleziono deklaracji lub obozu.', 'basemgmt'), 'error');
			$this->redirect('basemgmt-org-declarations');
			return;
		}

		// Check if this template is already assigned to the camp
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM " . Schema::table('camp_decl_docs') . " WHERE camp_id = %d AND template_id = %d",
			$camp_id, $decl_id
		));

		if ( $existing ) {
			AdminMenu::set_notice(__('Deklaracja jest już przypisana do tego obozu.', 'basemgmt'), 'error');
			$this->redirect('basemgmt-org-declarations');
			return;
		}

		$wpdb->insert(Schema::table('camp_decl_docs'), [
			'camp_id'      => $camp_id,
			'template_id'  => $decl_id,
			'title'        => $tpl->title . ' — ' . $camp->name,
			'status'       => 'draft',
			'html_content' => $tpl->html_content ?? '',
			'file_url'     => $tpl->file_url ?? '',
		]);

		AdminMenu::set_notice(sprintf(__('Deklaracja „%s" wysłana do obozu „%s".', 'basemgmt'), $tpl->title, $camp->name));
		$this->redirect('basemgmt-org-declarations');
	}

	/**
	 * Returns all declarations (optionally only auto_add ones).
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
