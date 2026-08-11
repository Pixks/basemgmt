<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

final class OrgDocumentsPage {

	// ── Constants ─────────────────────────────────────────────────────────────

	public const DOC_TYPE_DOCUMENT    = 'document';
	public const DOC_TYPE_CONTRACT    = 'contract';
	public const DOC_TYPE_REGULATION  = 'regulation';
	public const DOC_TYPE_DECLARATION = 'declaration';
	public const DOC_TYPE_OTHER       = 'other';

	public static function doc_types(): array {
		return [
			self::DOC_TYPE_DOCUMENT    => __('Dokument ogólny', 'basemgmt'),
			self::DOC_TYPE_CONTRACT    => __('Umowa', 'basemgmt'),
			self::DOC_TYPE_REGULATION  => __('Regulamin', 'basemgmt'),
			self::DOC_TYPE_DECLARATION => __('Deklaracja', 'basemgmt'),
			self::DOC_TYPE_OTHER       => __('Inny', 'basemgmt'),
		];
	}

	// ── Render ────────────────────────────────────────────────────────────────

	public function render(): void {
		Capabilities::require_admin();
		global $wpdb;
		$table     = Schema::table('doc_library');
		$documents = $wpdb->get_results("SELECT * FROM {$table} ORDER BY sort_order ASC, id DESC");
		$doc_types = self::doc_types();
		include BASEMGMT_DIR . 'templates/admin/org/documents/list.php';
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_doc_library');

		global $wpdb;
		$table = Schema::table('doc_library');
		$id    = (int) ($_POST['doc_id'] ?? 0);

		$title    = sanitize_text_field($_POST['title'] ?? '');
		$doc_type = sanitize_key($_POST['doc_type'] ?? self::DOC_TYPE_DOCUMENT);
		$auto_add = (int) ! empty($_POST['auto_add']);
		$file_id  = (int) ($_POST['file_id'] ?? 0);
		$file_url = esc_url_raw($_POST['file_url'] ?? '');
		$file_name = sanitize_file_name($_POST['file_name'] ?? '');

		if ( empty($title) ) {
			AdminMenu::set_notice(__('Podaj nazwę dokumentu.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-org-documents'));
			exit;
		}

		$data = [
			'title'      => $title,
			'doc_type'   => $doc_type,
			'auto_add'   => $auto_add,
			'file_id'    => $file_id ?: null,
			'file_url'   => $file_url,
			'file_name'  => $file_name,
			'created_by' => get_current_user_id(),
		];

		if ( $id > 0 ) {
			unset($data['created_by']);
			$wpdb->update($table, $data, ['id' => $id]);
			AdminMenu::set_notice(__('Dokument zaktualizowany.', 'basemgmt'));
		} else {
			$wpdb->insert($table, $data);
			AdminMenu::set_notice(__('Dokument dodany.', 'basemgmt'));
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-org-documents'));
		exit;
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_doc_library_{$id}");

		global $wpdb;
		$wpdb->delete(Schema::table('doc_library'), ['id' => $id]);
		AdminMenu::set_notice(__('Dokument usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-org-documents'));
		exit;
	}

	// ── Static helpers ────────────────────────────────────────────────────────

	public static function get_all(): array {
		global $wpdb;
		$table = Schema::table('doc_library');
		return $wpdb->get_results("SELECT * FROM {$table} ORDER BY sort_order ASC, id DESC") ?: [];
	}

	public static function get_auto_add(): array {
		global $wpdb;
		$table = Schema::table('doc_library');
		return $wpdb->get_results("SELECT * FROM {$table} WHERE auto_add = 1 ORDER BY sort_order ASC") ?: [];
	}
}
