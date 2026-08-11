<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

final class OrgDocTemplatesPage {

	// ── Constants ─────────────────────────────────────────────────────────────

	public const DOC_TYPE_CONTRACT    = 'contract';
	public const DOC_TYPE_REGULATION  = 'regulation';
	public const DOC_TYPE_DECLARATION = 'declaration';

	public static function doc_types(): array {
		return [
			self::DOC_TYPE_CONTRACT    => __('Umowa', 'basemgmt'),
			self::DOC_TYPE_REGULATION  => __('Regulamin', 'basemgmt'),
			self::DOC_TYPE_DECLARATION => __('Deklaracja', 'basemgmt'),
		];
	}

	// ── Render ────────────────────────────────────────────────────────────────

	public function render(): void {
		Capabilities::require_admin();

		$action = sanitize_key($_GET['action'] ?? 'list');
		$id     = (int) ($_GET['id'] ?? 0);

		match($action) {
			'edit', 'new' => $this->render_edit($id),
			default       => $this->render_list(),
		};
	}

	private function render_list(): void {
		global $wpdb;
		$table     = Schema::table('doc_templates');
		$templates = $wpdb->get_results("SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC");
		$doc_types = self::doc_types();
		include BASEMGMT_DIR . 'templates/admin/org/doc-templates/list.php';
	}

	private function render_edit(int $id): void {
		global $wpdb;
		$table    = Schema::table('doc_templates');
		$template = $id > 0 ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)) : null;
		$doc_types = self::doc_types();
		include BASEMGMT_DIR . 'templates/admin/org/doc-templates/edit.php';
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_doc_template');

		global $wpdb;
		$table = Schema::table('doc_templates');
		$id    = (int) ($_POST['template_id'] ?? 0);

		$data = [
			'title'        => sanitize_text_field($_POST['title'] ?? ''),
			'doc_type'     => sanitize_key($_POST['doc_type'] ?? self::DOC_TYPE_CONTRACT),
			'html_content' => wp_kses_post(wp_unslash($_POST['html_content'] ?? '')),
			'auto_add'     => (int) ! empty($_POST['auto_add']),
			'sort_order'   => (int) ($_POST['sort_order'] ?? 0),
			'created_by'   => get_current_user_id(),
		];

		if ( empty($data['title']) ) {
			AdminMenu::set_notice(__('Podaj tytuł szablonu.', 'basemgmt'), 'error');
			$this->redirect_back($id);
			return;
		}

		if ( $id > 0 ) {
			unset($data['created_by']);
			$wpdb->update($table, $data, ['id' => $id]);
			AdminMenu::set_notice(__('Szablon zaktualizowany.', 'basemgmt'));
		} else {
			$wpdb->insert($table, $data);
			$id = (int) $wpdb->insert_id;
			AdminMenu::set_notice(__('Szablon utworzony.', 'basemgmt'));
		}

		wp_safe_redirect(admin_url("admin.php?page=basemgmt-org-templates&action=edit&id={$id}"));
		exit;
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_doc_template_{$id}");

		global $wpdb;
		$wpdb->delete(Schema::table('doc_templates'), ['id' => $id]);
		AdminMenu::set_notice(__('Szablon usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-org-templates'));
		exit;
	}

	private function redirect_back(int $id): void {
		if ( $id > 0 ) {
			wp_safe_redirect(admin_url("admin.php?page=basemgmt-org-templates&action=edit&id={$id}"));
		} else {
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-org-templates&action=new'));
		}
		exit;
	}
}
