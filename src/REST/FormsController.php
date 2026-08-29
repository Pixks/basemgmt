<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Modules\Forms\FormRepository;
use BaseMgmt\Modules\Forms\SubmissionRepository;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * REST endpoints for Formularze i Zgłoszenia module.
 *
 * All require a valid camp session (inject _camp_id / _staff_id via require_session).
 *
 * GET    /bm/v1/panel/forms                          – list accessible forms for camp
 * GET    /bm/v1/panel/forms/{id}                     – get form definition + fields
 * POST   /bm/v1/panel/submissions                    – submit a form
 * GET    /bm/v1/panel/submissions                    – list own submissions
 * GET    /bm/v1/panel/submissions/{id}               – view own submission detail
 * GET    /bm/v1/panel/submissions/{id}/attachment/{att_id} – download attachment
 */
final class FormsController extends BaseController {

	public function register_routes(): void {
		$auth = fn(WP_REST_Request $r) => $this->require_session($r);

		register_rest_route(self::NAMESPACE, '/panel/forms', [
			'methods'             => 'GET',
			'callback'            => [$this, 'list_forms'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/forms/(?P<id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_form'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/submissions', [
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'list_submissions'],
				'permission_callback' => $auth,
			],
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'submit_form'],
				'permission_callback' => $auth,
			],
		]);

		register_rest_route(self::NAMESPACE, '/panel/submissions/(?P<id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_submission'],
			'permission_callback' => $auth,
		]);

		register_rest_route(
			self::NAMESPACE,
			'/panel/submissions/(?P<id>\d+)/attachment/(?P<att_id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'download_attachment'],
				'permission_callback' => $auth,
			]
		);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function list_forms(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');
		$forms   = FormRepository::get_for_camp($camp_id);

		$out = [];
		foreach ( $forms as $f ) {
			$out[] = [
				'id'          => (int) $f->id,
				'name'        => $f->name,
				'description' => $f->description,
				'category'    => $f->category,
				'is_pinned'   => (bool) $f->is_pinned,
				'info_before' => $f->info_before,
			];
		}

		return new WP_REST_Response(['forms' => $out], 200);
	}

	public function get_form(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');
		$form_id = (int) $request->get_param('id');

		$form = FormRepository::get_for_camp_checked($form_id, $camp_id);
		if ( ! $form ) {
			return new WP_REST_Response(['error' => __('Formularz nie istnieje lub brak dostępu.', 'basemgmt')], 403);
		}

		$fields = FormRepository::get_fields($form_id);
		$fields_out = [];
		foreach ( $fields as $field ) {
			$opts = json_decode($field->options_json ?? '[]', true) ?: [];
			$fields_out[] = [
				'id'            => (int) $field->id,
				'field_key'     => $field->field_key,
				'label'         => $field->label,
				'type'          => $field->type,
				'is_required'   => (bool) $field->is_required,
				'placeholder'   => $field->placeholder,
				'help_text'     => $field->help_text,
				'options'       => $opts,
				'default_value' => $field->default_value,
				'sort_order'    => (int) $field->sort_order,
			];
		}

		return new WP_REST_Response([
			'form'   => [
				'id'          => (int) $form->id,
				'name'        => $form->name,
				'description' => $form->description,
				'category'    => $form->category,
				'info_before' => $form->info_before,
				'info_after'  => $form->info_after,
			],
			'fields' => $fields_out,
		], 200);
	}

	public function list_submissions(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');

		$filters = ['camp_id' => $camp_id];
		$status  = sanitize_key($request->get_param('status') ?? '');
		if ( $status ) {
			$filters['status'] = $status;
		}

		$rows = SubmissionRepository::get_all($filters);
		$out  = [];
		foreach ( $rows as $s ) {
			$out[] = $this->submission_summary($s);
		}

		return new WP_REST_Response(['submissions' => $out], 200);
	}

	public function get_submission(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');
		$sub_id  = (int) $request->get_param('id');

		$sub = SubmissionRepository::get_for_camp($sub_id, $camp_id);
		if ( ! $sub ) {
			return new WP_REST_Response(['error' => __('Zgłoszenie nie istnieje lub brak dostępu.', 'basemgmt')], 403);
		}

		$form_snapshot   = json_decode($sub->form_snapshot ?? '{}', true);
		$submission_data = json_decode($sub->submission_data ?? '{}', true);
		$attachments     = SubmissionRepository::get_attachments($sub_id);

		$atts_out = [];
		foreach ( $attachments as $att ) {
			$atts_out[] = [
				'id'            => (int) $att->id,
				'original_name' => $att->original_name,
				'mime_type'     => $att->mime_type,
				'file_size'     => (int) $att->file_size,
				'download_url'  => rest_url(
					self::NAMESPACE . '/panel/submissions/' . $sub_id . '/attachment/' . $att->id
				),
			];
		}

		return new WP_REST_Response([
			'submission'      => $this->submission_summary($sub),
			'admin_comment'   => $sub->admin_comment,
			'form_snapshot'   => $form_snapshot,
			'submission_data' => $submission_data,
			'attachments'     => $atts_out,
		], 200);
	}

	public function submit_form(WP_REST_Request $request): WP_REST_Response {
		$nonce_ok = $this->require_panel_nonce($request);
		if ( is_wp_error($nonce_ok) ) {
			return $nonce_ok;
		}

		$camp_id  = (int) $request->get_param('_camp_id');
		$staff_id = (int) $request->get_param('_staff_id');
		$form_id  = (int) $request->get_param('form_id');
		$priority = sanitize_key($request->get_param('priority') ?? SubmissionRepository::PRIORITY_NORMAL);

		// Verify form is accessible by camp.
		$form = FormRepository::get_for_camp_checked($form_id, $camp_id);
		if ( ! $form ) {
			return new WP_REST_Response(['error' => __('Formularz nie istnieje lub brak dostępu.', 'basemgmt')], 403);
		}

		// Get current field definitions for validation + snapshot.
		$fields = FormRepository::get_fields($form_id);

		// Get submitted data.
		$body      = $request->get_json_params() ?? [];
		$submitted = $body['data'] ?? [];
		if ( ! is_array($submitted) ) {
			$submitted = [];
		}

		// Server-side validation.
		$validation = SubmissionRepository::validate($fields, $submitted);
		if ( ! empty($validation['errors']) ) {
			return new WP_REST_Response([
				'error'  => __('Formularz zawiera błędy.', 'basemgmt'),
				'fields' => $validation['errors'],
			], 422);
		}

		// Build snapshot: { form: {...}, fields: [...] }
		$fields_snapshot = [];
		foreach ( $fields as $f ) {
			$fields_snapshot[] = [
				'field_key'   => $f->field_key,
				'label'       => $f->label,
				'type'        => $f->type,
				'is_required' => (bool) $f->is_required,
				'options'     => json_decode($f->options_json ?? '[]', true) ?: [],
			];
		}
		$snapshot = wp_json_encode([
			'form'   => [
				'id'       => (int) $form->id,
				'name'     => $form->name,
				'category' => $form->category,
			],
			'fields' => $fields_snapshot,
		]);

		$sub_id = SubmissionRepository::create([
			'form_id'         => $form_id,
			'camp_id'         => $camp_id,
			'staff_id'        => $staff_id,
			'category'        => $form->category,
			'priority'        => $priority,
			'form_snapshot'   => $snapshot,
			'submission_data' => wp_json_encode($validation['clean']),
		]);

		return new WP_REST_Response([
			'submission_id' => $sub_id,
			'info_after'    => $form->info_after,
		], 201);
	}

	public function download_attachment(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');
		$sub_id  = (int) $request->get_param('id');
		$att_id  = (int) $request->get_param('att_id');

		// Verify camp owns the submission.
		$sub = SubmissionRepository::get_for_camp($sub_id, $camp_id);
		if ( ! $sub ) {
			return new WP_REST_Response(['error' => __('Brak dostępu.', 'basemgmt')], 403);
		}

		$att = SubmissionRepository::get_attachment($att_id);
		if ( ! $att || (int) $att->submission_id !== $sub_id ) {
			return new WP_REST_Response(['error' => __('Plik nie istnieje.', 'basemgmt')], 404);
		}

		if ( ! file_exists($att->file_path) ) {
			return new WP_REST_Response(['error' => __('Plik nie istnieje na serwerze.', 'basemgmt')], 404);
		}

		// Prevent path traversal: ensure file is inside uploads/basemgmt/.
		$upload  = wp_upload_dir();
		$allowed = realpath( trailingslashit( $upload['basedir'] ) . 'basemgmt' );
		$real    = realpath( $att->file_path );
		if ( ! $real || ! $allowed || ! str_starts_with( $real, $allowed . DIRECTORY_SEPARATOR ) ) {
			return new WP_REST_Response( ['error' => __( 'Niedozwolona lokalizacja pliku.', 'basemgmt' )], 403 );
		}

		// SEC-06: Whitelist MIME type – blokuje header injection przez dane z DB.
		$safe_mime = in_array( $att->mime_type, SubmissionRepository::ALLOWED_MIME_TYPES, true )
			? $att->mime_type
			: 'application/octet-stream';

		// Stream file and exit; headers are set directly.
		nocache_headers();
		header('Content-Type: ' . $safe_mime);
		header('Content-Disposition: attachment; filename="' . rawurlencode($att->original_name) . '"');
		header('Content-Length: ' . (int) $att->file_size);
		readfile($att->file_path);
		exit;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function submission_summary(object $s): array {
		return [
			'id'         => (int) $s->id,
			'form_id'    => (int) $s->form_id,
			'camp_id'    => (int) $s->camp_id,
			'staff_id'   => (int) $s->staff_id,
			'category'   => $s->category,
			'status'     => $s->status,
			'priority'   => $s->priority,
			'created_at' => $s->created_at,
			'updated_at' => $s->updated_at,
		];
	}
}
