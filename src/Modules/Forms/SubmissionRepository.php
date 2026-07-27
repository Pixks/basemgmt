<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Forms;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access for Zgłoszenia (Submissions) module.
 *
 * Tables:
 *   bm_submissions            – one row per submission
 *   bm_submission_attachments – uploaded files linked to a submission
 *   bm_submission_history     – audit log of status changes
 *
 * Key design decision:
 *   form_snapshot (JSON) = form definition + fields at time of submission.
 *   submission_data (JSON) = key→value map of submitted field values.
 *   Both are stored immutably; changes to the form definition never affect
 *   existing submissions.
 */
final class SubmissionRepository {

	// ── Status constants ──────────────────────────────────────────────────────

	public const STATUS_NEW      = 'new';
	public const STATUS_INPROG   = 'in_progress';
	public const STATUS_WAITING  = 'waiting';
	public const STATUS_CLOSED   = 'closed';
	public const STATUS_CANCELLED= 'cancelled';

	public const STATUSES = [
		self::STATUS_NEW      => 'Nowe',
		self::STATUS_INPROG   => 'W trakcie',
		self::STATUS_WAITING  => 'Oczekuje na odpowiedź',
		self::STATUS_CLOSED   => 'Zamknięte',
		self::STATUS_CANCELLED=> 'Anulowane',
	];

	// ── Priority constants ────────────────────────────────────────────────────

	public const PRIORITY_LOW    = 'low';
	public const PRIORITY_NORMAL = 'normal';
	public const PRIORITY_HIGH   = 'high';
	public const PRIORITY_URGENT = 'urgent';

	public const PRIORITIES = [
		self::PRIORITY_LOW    => 'Niski',
		self::PRIORITY_NORMAL => 'Normalny',
		self::PRIORITY_HIGH   => 'Wysoki',
		self::PRIORITY_URGENT => 'Pilny',
	];

	// ── Allowed upload types ──────────────────────────────────────────────────

	public const ALLOWED_MIME_TYPES = [
		'application/pdf',
		'image/jpeg',
		'image/png',
		'image/gif',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/zip',
		'text/plain',
	];

	public const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

	// ── Submissions CRUD ──────────────────────────────────────────────────────

	public static function get_all(array $filters = []): array {
		global $wpdb;
		$t     = Schema::table('submissions');
		$where = ['1=1'];
		$vals  = [];

		if ( ! empty($filters['camp_id']) ) {
			$where[] = 'camp_id = %d';
			$vals[]  = (int) $filters['camp_id'];
		}
		if ( ! empty($filters['form_id']) ) {
			$where[] = 'form_id = %d';
			$vals[]  = (int) $filters['form_id'];
		}
		if ( ! empty($filters['status']) ) {
			$where[] = 'status = %s';
			$vals[]  = $filters['status'];
		}
		if ( ! empty($filters['priority']) ) {
			$where[] = 'priority = %s';
			$vals[]  = $filters['priority'];
		}
		if ( ! empty($filters['category']) ) {
			$where[] = 'category = %s';
			$vals[]  = $filters['category'];
		}
		if ( ! empty($filters['assigned_to']) ) {
			$where[] = 'assigned_to = %d';
			$vals[]  = (int) $filters['assigned_to'];
		}
		if ( ! empty($filters['date_from']) ) {
			$where[] = 'DATE(created_at) >= %s';
			$vals[]  = $filters['date_from'];
		}
		if ( ! empty($filters['date_to']) ) {
			$where[] = 'DATE(created_at) <= %s';
			$vals[]  = $filters['date_to'];
		}

		$sql = "SELECT * FROM {$t} WHERE " . implode(' AND ', $where)
			. ' ORDER BY created_at DESC';

		if ( ! empty($vals) ) {
			$sql = $wpdb->prepare($sql, ...$vals);
		}

		return $wpdb->get_results($sql) ?: [];
	}

	public static function get(int $id): ?object {
		global $wpdb;
		$t = Schema::table('submissions');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	public static function get_for_camp(int $id, int $camp_id): ?object {
		global $wpdb;
		$t = Schema::table('submissions');
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$t} WHERE id = %d AND camp_id = %d", $id, $camp_id)
		) ?: null;
	}

	/**
	 * Create a new submission.
	 *
	 * @param array $data {
	 *   form_id, camp_id, staff_id, category,
	 *   form_snapshot (JSON string), submission_data (JSON string),
	 *   priority
	 * }
	 */
	public static function create(array $data): int {
		global $wpdb;
		$t = Schema::table('submissions');

		$wpdb->insert($t, [
			'form_id'         => (int) $data['form_id'],
			'camp_id'         => (int) $data['camp_id'],
			'staff_id'        => (int) $data['staff_id'],
			'category'        => sanitize_key($data['category'] ?? 'inne'),
			'status'          => self::STATUS_NEW,
			'priority'        => sanitize_key($data['priority'] ?? self::PRIORITY_NORMAL),
			'form_snapshot'   => $data['form_snapshot'] ?? '{}',
			'submission_data' => $data['submission_data'] ?? '{}',
			'admin_comment'   => '',
			'assigned_to'     => null,
		]);

		return (int) $wpdb->insert_id;
	}

	public static function update_status(int $id, string $new_status, int $changed_by, string $note = ''): void {
		global $wpdb;
		$ts  = Schema::table('submissions');
		$th  = Schema::table('submission_history');

		$row = self::get($id);
		if ( ! $row ) return;

		$wpdb->update($ts, ['status' => sanitize_key($new_status)], ['id' => $id]);

		$wpdb->insert($th, [
			'submission_id' => $id,
			'changed_by'    => $changed_by,
			'from_status'   => $row->status,
			'to_status'     => sanitize_key($new_status),
			'note'          => sanitize_textarea_field($note),
		]);
	}

	public static function update_admin_fields(int $id, array $data): void {
		global $wpdb;
		$t       = Schema::table('submissions');
		$payload = [];

		if ( isset($data['status']) ) {
			$payload['status'] = sanitize_key($data['status']);
		}
		if ( isset($data['priority']) ) {
			$payload['priority'] = sanitize_key($data['priority']);
		}
		if ( isset($data['admin_comment']) ) {
			$payload['admin_comment'] = sanitize_textarea_field($data['admin_comment']);
		}
		if ( array_key_exists('assigned_to', $data) ) {
			$payload['assigned_to'] = ! empty($data['assigned_to']) ? (int) $data['assigned_to'] : null;
		}

		if ( ! empty($payload) ) {
			$wpdb->update($t, $payload, ['id' => $id]);
		}
	}

	// ── Attachments ───────────────────────────────────────────────────────────

	public static function get_attachments(int $submission_id): array {
		global $wpdb;
		$t = Schema::table('submission_attachments');
		return $wpdb->get_results(
			$wpdb->prepare("SELECT * FROM {$t} WHERE submission_id = %d ORDER BY id ASC", $submission_id)
		) ?: [];
	}

	public static function get_attachment(int $id): ?object {
		global $wpdb;
		$t = Schema::table('submission_attachments');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	/**
	 * Handle a single file upload for a submission.
	 * Returns attachment ID on success, WP_Error on failure.
	 *
	 * @param array{name:string, tmp_name:string, type:string, size:int, error:int} $file $_FILES entry
	 * @param int $submission_id
	 * @return int|\WP_Error
	 */
	public static function handle_upload(array $file, int $submission_id): int|\WP_Error {
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return new \WP_Error('upload_error', __('Błąd przesyłania pliku.', 'basemgmt'));
		}

		if ( $file['size'] > self::MAX_FILE_SIZE ) {
			return new \WP_Error('file_too_large', __('Plik jest za duży (max 10 MB).', 'basemgmt'));
		}

		// Verify MIME type using finfo, not client-sent type.
		$finfo     = new \finfo(FILEINFO_MIME_TYPE);
		$real_mime = $finfo->file($file['tmp_name']);
		if ( ! in_array($real_mime, self::ALLOWED_MIME_TYPES, true) ) {
			return new \WP_Error('invalid_mime', __('Niedozwolony typ pliku.', 'basemgmt'));
		}

		// Sanitize original filename, strip dangerous chars.
		$orig_name = sanitize_file_name($file['name']);

		// Build target directory: wp-content/uploads/basemgmt/{submission_id}/
		$upload    = wp_upload_dir();
		$dir       = trailingslashit($upload['basedir']) . 'basemgmt/' . $submission_id . '/';
		if ( ! wp_mkdir_p($dir) ) {
			return new \WP_Error('mkdir_fail', __('Nie można utworzyć katalogu przesyłania.', 'basemgmt'));
		}

		// Unique filename to prevent overwrites.
		$unique_name = wp_unique_filename($dir, $orig_name);
		$target_path = $dir . $unique_name;

		if ( ! move_uploaded_file($file['tmp_name'], $target_path) ) {
			return new \WP_Error('move_fail', __('Nie można przenieść pliku.', 'basemgmt'));
		}

		// Protect directory from direct access.
		$htaccess = $dir . '.htaccess';
		if ( ! file_exists($htaccess) ) {
			file_put_contents($htaccess, "deny from all\n");
		}

		global $wpdb;
		$t = Schema::table('submission_attachments');
		$wpdb->insert($t, [
			'submission_id' => $submission_id,
			'original_name' => $orig_name,
			'stored_name'   => $unique_name,
			'mime_type'     => $real_mime,
			'file_size'     => (int) $file['size'],
			'file_path'     => $target_path,
		]);

		return (int) $wpdb->insert_id;
	}

	// ── History ───────────────────────────────────────────────────────────────

	public static function get_history(int $submission_id): array {
		global $wpdb;
		$t = Schema::table('submission_history');
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE submission_id = %d ORDER BY created_at ASC",
				$submission_id
			)
		) ?: [];
	}

	// ── Counts / stats ────────────────────────────────────────────────────────

	public static function count_new(): int {
		global $wpdb;
		$t = Schema::table('submissions');
		return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'new'");
	}

	public static function count_open(): int {
		global $wpdb;
		$t = Schema::table('submissions');
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$t} WHERE status IN ('new','in_progress','waiting')"
		);
	}

	public static function get_recent(int $limit = 5): array {
		global $wpdb;
		$t = Schema::table('submissions');
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE status NOT IN ('closed','cancelled') ORDER BY created_at DESC LIMIT %d",
				$limit
			)
		) ?: [];
	}

	// ── Server-side form validation ───────────────────────────────────────────

	/**
	 * Validate submitted data against field definitions.
	 *
	 * @param object[] $fields      Form field rows from bm_form_fields.
	 * @param array    $submitted   Raw POST data (key => raw value).
	 * @return array{errors: array<string,string>, clean: array<string,mixed>}
	 */
	public static function validate(array $fields, array $submitted): array {
		$errors = [];
		$clean  = [];

		foreach ( $fields as $field ) {
			$key   = $field->field_key;
			$raw   = $submitted[$key] ?? '';
			$label = $field->label;

			// required check
			if ( $field->is_required && ( $raw === '' || $raw === null || $raw === [] ) ) {
				$errors[$key] = sprintf(__('%s jest wymagane.', 'basemgmt'), $label);
				$clean[$key]  = '';
				continue;
			}

			// type-specific sanitization & basic validation
			switch ( $field->type ) {
				case 'email':
					$v = sanitize_email((string) $raw);
					if ( $raw !== '' && ! is_email($v) ) {
						$errors[$key] = sprintf(__('%s – nieprawidłowy adres e-mail.', 'basemgmt'), $label);
					}
					$clean[$key] = $v;
					break;

				case 'number':
					$v = sanitize_text_field((string) $raw);
					if ( $raw !== '' && ! is_numeric($v) ) {
						$errors[$key] = sprintf(__('%s – podaj liczbę.', 'basemgmt'), $label);
					}
					$clean[$key] = $v === '' ? '' : (float) $v;
					break;

				case 'date':
					$v = sanitize_text_field((string) $raw);
					if ( $raw !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ) {
						$errors[$key] = sprintf(__('%s – nieprawidłowy format daty.', 'basemgmt'), $label);
					}
					$clean[$key] = $v;
					break;

				case 'textarea':
					$clean[$key] = sanitize_textarea_field((string) $raw);
					break;

				case 'checkbox':
					$vals = is_array($raw) ? array_map('sanitize_text_field', $raw) : [];
					$clean[$key] = $vals;
					break;

				case 'file':
					// files are handled separately by handle_upload(); skip in text validation
					$clean[$key] = null;
					break;

				default:
					$clean[$key] = sanitize_text_field((string) $raw);
					break;
			}
		}

		return ['errors' => $errors, 'clean' => $clean];
	}
}
