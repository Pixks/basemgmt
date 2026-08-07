<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Database\Schema;
use BaseMgmt\Modules\Forms\FormRepository;
use BaseMgmt\Modules\Forms\SubmissionRepository;
use BaseMgmt\Modules\Camps\CampRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for Formularze i Zgłoszenia module.
 * Provides:
 *   - Form list / create / edit (with field builder)
 *   - Submission list / view / manage
 */
final class FormsPage {

	// ── Router ────────────────────────────────────────────────────────────────

	public function render(): void {
		Capabilities::require_admin();

		$view = sanitize_key($_GET['view'] ?? 'forms');

		// Allow inline table creation without plugin reactivation.
		if ( isset($_GET['bm_create_tables']) ) {
			check_admin_referer('bm_create_tables');
			\BaseMgmt\Database\Schema::create_tables();
			AdminMenu::set_notice(__('Tabele zostały utworzone / zaktualizowane.', 'basemgmt'), 'success');
			wp_safe_redirect(add_query_arg(['page' => 'basemgmt-forms'], admin_url('admin.php')));
			exit;
		}

		match ($view) {
			'edit_form'       => $this->render_edit_form(),
			'submissions'     => $this->render_submissions_list(),
			'view_submission' => $this->render_submission_view(),
			default           => $this->render_forms_list(),
		};
	}

	// ── Forms list ────────────────────────────────────────────────────────────

	private function render_forms_list(): void {
		$forms = FormRepository::get_all();
		include BASEMGMT_DIR . 'templates/admin/forms/list.php';
	}

	// ── Edit form (create / update + field builder) ───────────────────────────

	private function render_edit_form(): void {
		$id   = (int) ($_GET['id'] ?? 0);
		$form = $id ? FormRepository::get($id) : null;

		$assigned_camps = $id ? FormRepository::get_assigned_camps($id) : [];
		$fields         = $id ? FormRepository::get_fields($id) : [];
		$camps          = CampRepository::get_all(['status' => 'active']);
		$categories     = FormRepository::CATEGORIES;
		$field_types    = FormRepository::FIELD_TYPES;

		include BASEMGMT_DIR . 'templates/admin/forms/edit.php';
	}

	// ── Submissions list ──────────────────────────────────────────────────────

	private function render_submissions_list(): void {
		$filters = [
			'form_id'   => (int)    ($_GET['filter_form']     ?? 0),
			'camp_id'   => (int)    ($_GET['filter_camp']     ?? 0),
			'status'    => sanitize_key($_GET['filter_status']    ?? ''),
			'priority'  => sanitize_key($_GET['filter_priority']  ?? ''),
			'category'  => sanitize_key($_GET['filter_category']  ?? ''),
			'date_from' => sanitize_text_field($_GET['filter_date_from'] ?? ''),
			'date_to'   => sanitize_text_field($_GET['filter_date_to']   ?? ''),
		];
		// Remove empty values so get_all() doesn't filter on them.
		$filters = array_filter($filters);

		$submissions = SubmissionRepository::get_all($filters);
		$forms       = FormRepository::get_all();
		$camps       = CampRepository::get_all();
		$statuses    = SubmissionRepository::STATUSES;
		$priorities  = SubmissionRepository::PRIORITIES;
		$categories  = FormRepository::CATEGORIES;
		$wp_users    = get_users(['fields' => ['ID', 'display_name']]);

		include BASEMGMT_DIR . 'templates/admin/forms/submissions_list.php';
	}

	// ── Submission view ───────────────────────────────────────────────────────

	private function render_submission_view(): void {
		$id         = (int) ($_GET['id'] ?? 0);
		$submission = $id ? SubmissionRepository::get($id) : null;

		if ( ! $submission ) {
			wp_die(__('Zgłoszenie nie istnieje.', 'basemgmt'));
		}

		$attachments    = SubmissionRepository::get_attachments($id);
		$history        = SubmissionRepository::get_history($id);
		$statuses       = SubmissionRepository::STATUSES;
		$priorities     = SubmissionRepository::PRIORITIES;
		$wp_users       = get_users(['fields' => ['ID', 'display_name']]);
		$form_snapshot  = json_decode($submission->form_snapshot ?? '{}', true);
		$submission_data= json_decode($submission->submission_data ?? '{}', true);

		include BASEMGMT_DIR . 'templates/admin/forms/submission_view.php';
	}

	// ── admin_post handlers ───────────────────────────────────────────────────

	public function handle_save_form(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_form');

		$id   = (int) ($_POST['form_id'] ?? 0);
		$data = [
			'id'          => $id,
			'name'        => $_POST['name'] ?? '',
			'description' => $_POST['description'] ?? '',
			'category'    => $_POST['category'] ?? 'inne',
			'status'      => $_POST['status'] ?? 'active',
			'is_global'   => (int) isset($_POST['is_global']),
			'sort_order'  => $_POST['sort_order'] ?? 0,
			'is_pinned'   => (int) isset($_POST['is_pinned']),
			'info_before' => $_POST['info_before'] ?? '',
			'info_after'  => $_POST['info_after'] ?? '',
		];

		$saved_id = FormRepository::save($data);

		if ( ! $saved_id ) {
			global $wpdb;
			AdminMenu::set_notice(
				__('Błąd zapisu formularza. Upewnij się, że tabele pluginu zostały utworzone (dezaktywuj i aktywuj wtyczkę).', 'basemgmt') .
				( $wpdb->last_error ? ' DB: ' . esc_html($wpdb->last_error) : '' ),
				'error'
			);
			wp_safe_redirect(add_query_arg(['page' => 'basemgmt-forms'], admin_url('admin.php')));
			exit;
		}

		// Update camp visibility.
		if ( empty($data['is_global']) ) {
			$camp_ids = array_map('intval', (array) ($_POST['assigned_camps'] ?? []));
			FormRepository::set_assigned_camps($saved_id, $camp_ids);
		} else {
			FormRepository::set_assigned_camps($saved_id, []);
		}

		AdminMenu::set_notice(__('Formularz zapisany.', 'basemgmt'), 'success');
		wp_safe_redirect(add_query_arg(
			['page' => 'basemgmt-forms', 'view' => 'edit_form', 'id' => $saved_id],
			admin_url('admin.php')
		));
		exit;
	}

	public function handle_delete_form(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_delete_form');

		$id = (int) ($_POST['form_id'] ?? 0);
		if ( $id ) {
			FormRepository::delete($id);
		}

		AdminMenu::set_notice(__('Formularz usunięty.', 'basemgmt'), 'success');
		wp_safe_redirect(add_query_arg(['page' => 'basemgmt-forms'], admin_url('admin.php')));
		exit;
	}

	// ── Field builder handlers ────────────────────────────────────────────────

	public function handle_save_field(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_form_field');

		$form_id    = (int) ($_POST['form_id'] ?? 0);
		$field_data = [
			'id'            => (int) ($_POST['field_id'] ?? 0),
			'form_id'       => $form_id,
			'label'         => $_POST['label'] ?? '',
			'field_key'     => $_POST['field_key'] ?? '',
			'type'          => $_POST['type'] ?? 'text',
			'is_required'   => (int) isset($_POST['is_required']),
			'placeholder'   => $_POST['placeholder'] ?? '',
			'help_text'     => $_POST['help_text'] ?? '',
			'options'       => $_POST['options'] ?? '',
			'default_value' => $_POST['default_value'] ?? '',
			'validation'    => $_POST['validation'] ?? '',
			'sort_order'    => (int) ($_POST['sort_order'] ?? 0),
		];

		// Auto-generate field_key if empty.
		if ( empty($field_data['field_key']) ) {
			$field_data['field_key'] = 'field_' . sanitize_key($field_data['label']);
		}

		FormRepository::save_field($field_data);

		AdminMenu::set_notice(__('Pole zapisane.', 'basemgmt'), 'success');
		wp_safe_redirect(add_query_arg(
			['page' => 'basemgmt-forms', 'view' => 'edit_form', 'id' => $form_id],
			admin_url('admin.php')
		));
		exit;
	}

	public function handle_delete_field(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_delete_form_field');

		$field_id = (int) ($_POST['field_id'] ?? 0);
		$form_id  = (int) ($_POST['form_id']  ?? 0);

		if ( $field_id ) {
			FormRepository::delete_field($field_id);
		}

		AdminMenu::set_notice(__('Pole usunięte.', 'basemgmt'), 'success');
		wp_safe_redirect(add_query_arg(
			['page' => 'basemgmt-forms', 'view' => 'edit_form', 'id' => $form_id],
			admin_url('admin.php')
		));
		exit;
	}

	// ── Submission management handlers ────────────────────────────────────────

	public function handle_update_submission(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_update_submission');

		$sub_id     = (int) ($_POST['submission_id'] ?? 0);
		$new_status = sanitize_key($_POST['status'] ?? '');
		$old        = SubmissionRepository::get($sub_id);

		if ( ! $old ) {
			wp_die(__('Zgłoszenie nie istnieje.', 'basemgmt'));
		}

		$admin_id = get_current_user_id();
		$note     = sanitize_textarea_field($_POST['status_note'] ?? '');

		// Log status change if it changed.
		if ( $new_status && $new_status !== $old->status ) {
			SubmissionRepository::update_status($sub_id, $new_status, $admin_id, $note);
		}

		// Update other admin fields.
		SubmissionRepository::update_admin_fields($sub_id, [
			'status'        => $new_status ?: $old->status,
			'priority'      => sanitize_key($_POST['priority'] ?? $old->priority),
			'admin_comment' => $_POST['admin_comment'] ?? $old->admin_comment,
			'assigned_to'   => $_POST['assigned_to'] ?? null,
		]);

		AdminMenu::set_notice(__('Zgłoszenie zaktualizowane.', 'basemgmt'), 'success');
		wp_safe_redirect(add_query_arg(
			['page' => 'basemgmt-forms', 'view' => 'view_submission', 'id' => $sub_id],
			admin_url('admin.php')
		));
		exit;
	}

	/**
	 * Serve a submission attachment to an admin.
	 * Called via GET /wp-admin/admin-post.php?action=bm_download_attachment&...
	 */
	public function handle_download_attachment(): void {
		Capabilities::require_admin();

		$att_id = (int) ($_GET['att_id'] ?? 0);
		$att    = $att_id ? SubmissionRepository::get_attachment($att_id) : null;

		if ( ! $att || ! file_exists($att->file_path) ) {
			wp_die(__('Plik nie istnieje.', 'basemgmt'), 404);
		}

		nocache_headers();
		header('Content-Type: ' . $att->mime_type);
		header('Content-Disposition: attachment; filename="' . esc_attr($att->original_name) . '"');
		header('Content-Length: ' . filesize($att->file_path));
		readfile($att->file_path);
		exit;
	}

	/**
	 * Create a communication thread from a form submission.
	 * Triggered by the "Utwórz wątek konwersacji" button on the submission view page.
	 */
	public function handle_create_thread_from_submission(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_create_thread_from_submission');

		$sub_id = (int) ($_POST['submission_id'] ?? 0);
		$sub    = $sub_id ? SubmissionRepository::get($sub_id) : null;

		if ( ! $sub ) {
			wp_die(__('Zgłoszenie nie istnieje.', 'basemgmt'));
		}

		$form_snapshot   = json_decode($sub->form_snapshot ?? '{}', true);
		$submission_data = json_decode($sub->submission_data ?? '{}', true);
		$form_name       = $form_snapshot['form']['name'] ?? ('Form #' . $sub->form_id);

		// Build message content from submission data.
		$message_lines = [
			sprintf(__('Zgłoszenie #%d z formularza: %s', 'basemgmt'), $sub_id, $form_name),
			'',
		];
		foreach ( ($form_snapshot['fields'] ?? []) as $fld ) {
			$val = $submission_data[$fld['field_key']] ?? '';
			if ( is_array($val) ) {
				$val = implode(', ', $val);
			}
			if ( (string) $val !== '' ) {
				$message_lines[] = $fld['label'] . ': ' . $val;
			}
		}
		$message_body = implode("\n", $message_lines);

		$subject = sprintf(
			__('Zgłoszenie #%d – %s', 'basemgmt'),
			$sub_id,
			$form_name
		);

		global $wpdb;
		$threads_table  = \BaseMgmt\Database\Schema::table('conv_threads');
		$messages_table = \BaseMgmt\Database\Schema::table('conv_messages');

		$wpdb->insert(
			$threads_table,
			[
				'camp_id'            => (int) $sub->camp_id,
				'subject'            => $subject,
				'status'             => \BaseMgmt\Modules\Communication\ConversationRepository::STATUS_OPEN,
				'priority'           => \BaseMgmt\Modules\Communication\ConversationRepository::PRIORITY_NORMAL,
				'is_urgent'          => 0,
				'last_message_at'    => current_time('mysql'),
				'unread_admin'       => 0,
				'unread_camp'        => 1,
				'created_by_staff_id'=> 0,
			]
		);
		$thread_id = (int) $wpdb->insert_id;

		if ( $thread_id ) {
			$wpdb->insert(
				$messages_table,
				[
					'thread_id'   => $thread_id,
					'author_type' => 'admin',
					'author_id'   => get_current_user_id(),
					'content'     => $message_body,
					'is_system'   => 1,
				]
			);

			\BaseMgmt\Core\OperationLogger::log(
				\BaseMgmt\Core\OperationLogger::ACTION_THREAD_CREATED,
				'submission',
				$sub_id,
				"thread_id={$thread_id}"
			);
		}

		AdminMenu::set_notice(
			$thread_id
				? sprintf(__('Wątek konwersacji #%d utworzony.', 'basemgmt'), $thread_id)
				: __('Błąd tworzenia wątku.', 'basemgmt'),
			$thread_id ? 'success' : 'error'
		);
		wp_safe_redirect(add_query_arg(
			['page' => 'basemgmt-forms', 'view' => 'view_submission', 'id' => $sub_id],
			admin_url('admin.php')
		));
		exit;
	}
}
