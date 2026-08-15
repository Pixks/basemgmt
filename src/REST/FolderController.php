<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Database\Schema;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Camp folder endpoints – all require a valid camp session.
 *
 * GET  /bm/v1/panel/folder/documents           – list documents from doc_library
 * GET  /bm/v1/panel/folder/camp-documents      – list camp-specific documents
 * GET  /bm/v1/panel/folder/damages             – list own camp damages
 * POST /bm/v1/panel/folder/damages             – report new damage
 * GET  /bm/v1/panel/folder/declaration         – get declaration header + days
 * POST /bm/v1/panel/folder/declaration/day     – save/upsert a declaration day
 * GET  /bm/v1/panel/folder/decl-docs           – list declaration docs sent to camp
 * POST /bm/v1/panel/folder/decl-docs/{id}/approve – approve a declaration doc
 * GET  /bm/v1/panel/folder/equipment           – list camp equipment
 * POST /bm/v1/panel/folder/equipment           – issue new equipment item
 * POST /bm/v1/panel/folder/equipment/{id}/return – register a return
 */
final class FolderController extends BaseController {

	public function register_routes(): void {
		$auth = fn(WP_REST_Request $r) => $this->require_session($r);
		$ns   = self::NAMESPACE;

		register_rest_route($ns, '/panel/folder/documents', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_documents'],
			'permission_callback' => $auth,
		]);

		register_rest_route($ns, '/panel/folder/camp-documents', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_camp_documents'],
			'permission_callback' => $auth,
		]);

		register_rest_route($ns, '/panel/folder/damages', [
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'get_damages'],
				'permission_callback' => $auth,
			],
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'report_damage'],
				'permission_callback' => $auth,
			],
		]);

		register_rest_route($ns, '/panel/folder/declaration', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_declaration'],
			'permission_callback' => $auth,
		]);

		register_rest_route($ns, '/panel/folder/declaration/day', [
			'methods'             => 'POST',
			'callback'            => [$this, 'save_declaration_day'],
			'permission_callback' => $auth,
		]);

		register_rest_route($ns, '/panel/folder/decl-docs', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_decl_docs'],
			'permission_callback' => $auth,
		]);

		register_rest_route($ns, '/panel/folder/decl-docs/(?P<id>\d+)/approve', [
			'methods'             => 'POST',
			'callback'            => [$this, 'approve_decl_doc'],
			'permission_callback' => $auth,
		]);

		register_rest_route($ns, '/panel/folder/equipment', [
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'get_equipment'],
				'permission_callback' => $auth,
			],
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'issue_equipment'],
				'permission_callback' => $auth,
			],
		]);

		register_rest_route($ns, '/panel/folder/equipment/(?P<id>\d+)/return', [
			'methods'             => 'POST',
			'callback'            => [$this, 'return_equipment'],
			'permission_callback' => $auth,
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function get_documents(WP_REST_Request $request): WP_REST_Response {
		global $wpdb;
		$table = Schema::table('doc_library');
		$rows  = $wpdb->get_results(
			"SELECT id, title, doc_type, file_url, file_name, auto_add FROM {$table} ORDER BY sort_order ASC, id ASC"
		);

		return $this->ok([
			'documents' => array_map(fn($r) => [
				'id'        => (int) $r->id,
				'title'     => $r->title,
				'doc_type'  => $r->doc_type,
				'file_url'  => $r->file_url,
				'file_name' => $r->file_name,
				'auto_add'  => (bool) $r->auto_add,
			], $rows),
		]);
	}

	public function get_camp_documents(WP_REST_Request $request): WP_REST_Response {
		global $wpdb;
		$camp_id = (int) $request->get_param('_camp_id');
		$table   = Schema::table('camp_documents');

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, document_type, doc_category, status, file_url,
				        sent_at, signed_at, signed_method, locked, created_at
				 FROM {$table}
				 WHERE camp_id = %d AND doc_category = 'document'
				 ORDER BY id DESC",
				$camp_id
			)
		);

		return $this->ok([
			'camp_documents' => array_map(fn($r) => [
				'id'            => (int) $r->id,
				'title'         => $r->title,
				'document_type' => $r->document_type,
				'status'        => $r->status,
				'file_url'      => $r->file_url,
				'sent_at'       => $r->sent_at,
				'signed_at'     => $r->signed_at,
				'signed_method' => $r->signed_method,
				'locked'        => (bool) $r->locked,
				'created_at'    => $r->created_at,
			], $rows),
		]);
	}

	public function get_damages(WP_REST_Request $request): WP_REST_Response {
		global $wpdb;
		$camp_id = (int) $request->get_param('_camp_id');
		$table   = Schema::table('camp_damages');

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, name, description, cost, status, created_at FROM {$table} WHERE camp_id = %d ORDER BY id DESC",
				$camp_id
			)
		);

		return $this->ok([
			'damages' => array_map(fn($r) => [
				'id'          => (int) $r->id,
				'name'        => $r->name,
				'description' => $r->description ?? '',
				'cost'        => (float) $r->cost,
				'status'      => $r->status,
				'created_at'  => $r->created_at,
			], $rows),
		]);
	}

	public function report_damage(WP_REST_Request $request): WP_REST_Response|\WP_Error {
		if ( ! wp_verify_nonce( (string) $request->get_param('nonce'), 'bm_panel' ) ) {
			return $this->error('bm_invalid_nonce', __('Nieprawidłowy token. Odśwież stronę.', 'basemgmt'), 403);
		}

		$camp_id = (int) $request->get_param('_camp_id');
		$name    = sanitize_text_field($request->get_param('name') ?? '');
		$desc    = sanitize_textarea_field($request->get_param('description') ?? '');
		$cost    = (float) ($request->get_param('cost') ?? 0);

		if ( empty($name) ) {
			return $this->error('missing_name', __('Nazwa szkody jest wymagana.', 'basemgmt'), 422);
		}

		global $wpdb;
		$table  = Schema::table('camp_damages');
		$result = $wpdb->insert($table, [
			'camp_id'     => $camp_id,
			'name'        => $name,
			'description' => $desc,
			'cost'        => $cost,
			'status'      => 'reported',
		]);

		if ( ! $result ) {
			return $this->error('db_error', __('Błąd zapisu. Spróbuj ponownie.', 'basemgmt'), 500);
		}

		return $this->ok(
			['id' => (int) $wpdb->insert_id, 'message' => __('Szkoda zgłoszona.', 'basemgmt')],
			201
		);
	}

	public function get_declaration(WP_REST_Request $request): WP_REST_Response {
		global $wpdb;
		$camp_id    = (int) $request->get_param('_camp_id');
		$decl_table = Schema::table('camp_declarations');
		$days_table = Schema::table('camp_declaration_days');

		$decl = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, declared_persons, declared_diets, arrival_time, departure_time, submitted_at, signed_at, notes
				 FROM {$decl_table} WHERE camp_id = %d",
				$camp_id
			)
		);

		$days = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, declaration_date, declared_persons, arrival_time, departure_time
				 FROM {$days_table} WHERE camp_id = %d ORDER BY declaration_date ASC",
				$camp_id
			)
		);

		return $this->ok([
			'declaration' => $decl ? [
				'id'               => (int) $decl->id,
				'declared_persons' => (int) $decl->declared_persons,
				'declared_diets'   => (int) $decl->declared_diets,
				'arrival_time'     => $decl->arrival_time,
				'departure_time'   => $decl->departure_time,
				'submitted_at'     => $decl->submitted_at,
				'signed_at'        => $decl->signed_at,
				'notes'            => $decl->notes ?? '',
			] : null,
			'days' => array_map(fn($d) => [
				'id'               => (int) $d->id,
				'declaration_date' => $d->declaration_date,
				'declared_persons' => (int) $d->declared_persons,
				'arrival_time'     => $d->arrival_time,
				'departure_time'   => $d->departure_time,
			], $days),
		]);
	}

	public function save_declaration_day(WP_REST_Request $request): WP_REST_Response|\WP_Error {
		if ( ! wp_verify_nonce( (string) $request->get_param('nonce'), 'bm_panel' ) ) {
			return $this->error('bm_invalid_nonce', __('Nieprawidłowy token. Odśwież stronę.', 'basemgmt'), 403);
		}

		$camp_id          = (int) $request->get_param('_camp_id');
		$date             = sanitize_text_field($request->get_param('declaration_date') ?? '');
		$declared_persons = (int) ($request->get_param('declared_persons') ?? 0);
		$arrival_time     = sanitize_text_field($request->get_param('arrival_time') ?? '');
		$departure_time   = sanitize_text_field($request->get_param('departure_time') ?? '');

		if ( empty($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ) {
			return $this->error('invalid_date', __('Nieprawidłowa data.', 'basemgmt'), 422);
		}

		global $wpdb;
		$table      = Schema::table('camp_declaration_days');
		$existing   = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE camp_id = %d AND declaration_date = %s",
				$camp_id,
				$date
			)
		);

		if ( $existing ) {
			$wpdb->update(
				$table,
				compact('declared_persons', 'arrival_time', 'departure_time'),
				['id' => $existing]
			);
			$row_id = $existing;
		} else {
			$wpdb->insert($table, [
				'camp_id'          => $camp_id,
				'declaration_date' => $date,
				'declared_persons' => $declared_persons,
				'arrival_time'     => $arrival_time,
				'departure_time'   => $departure_time,
			]);
			$row_id = (int) $wpdb->insert_id;
		}

		return $this->ok([
			'day' => [
				'id'               => $row_id,
				'declaration_date' => $date,
				'declared_persons' => $declared_persons,
				'arrival_time'     => $arrival_time,
				'departure_time'   => $departure_time,
			],
		]);
	}

	public function get_decl_docs(WP_REST_Request $request): WP_REST_Response {
		global $wpdb;
		$camp_id = (int) $request->get_param('_camp_id');
		$table   = Schema::table('camp_decl_docs');

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, status, file_url, signed_method, signed_at,
				        approved_by, approved_at, sent_to_camp, camp_approved_at, created_at
				 FROM {$table}
				 WHERE camp_id = %d AND sent_to_camp = 1
				 ORDER BY id DESC",
				$camp_id
			)
		);

		return $this->ok([
			'decl_docs' => array_map(fn($r) => [
				'id'               => (int) $r->id,
				'title'            => $r->title,
				'status'           => $r->status,
				'file_url'         => $r->file_url,
				'signed_method'    => $r->signed_method,
				'signed_at'        => $r->signed_at,
				'approved_at'      => $r->approved_at,
				'camp_approved_at' => $r->camp_approved_at,
				'created_at'       => $r->created_at,
			], $rows),
		]);
	}

	public function approve_decl_doc(WP_REST_Request $request): WP_REST_Response|\WP_Error {
		if ( ! wp_verify_nonce( (string) $request->get_param('nonce'), 'bm_panel' ) ) {
			return $this->error('bm_invalid_nonce', __('Nieprawidłowy token. Odśwież stronę.', 'basemgmt'), 403);
		}

		global $wpdb;
		$camp_id = (int) $request->get_param('_camp_id');
		$doc_id  = (int) $request->get_param('id');
		$table   = Schema::table('camp_decl_docs');

		$doc = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, camp_id, sent_to_camp, camp_approved_at FROM {$table} WHERE id = %d",
				$doc_id
			)
		);

		if ( ! $doc || (int) $doc->camp_id !== $camp_id ) {
			return $this->error('not_found', __('Dokument nie istnieje.', 'basemgmt'), 404);
		}
		if ( ! $doc->sent_to_camp ) {
			return $this->error('not_sent', __('Dokument nie został przesłany do obozu.', 'basemgmt'), 422);
		}
		if ( $doc->camp_approved_at ) {
			return $this->error('already_approved', __('Dokument jest już zatwierdzony.', 'basemgmt'), 409);
		}

		$wpdb->update(
			$table,
			['camp_approved_at' => current_time('mysql')],
			['id' => $doc_id]
		);

		return $this->ok(['message' => __('Dokument zatwierdzony.', 'basemgmt')]);
	}

	public function get_equipment(WP_REST_Request $request): WP_REST_Response {
		global $wpdb;
		$camp_id = (int) $request->get_param('_camp_id');
		$table   = Schema::table('camp_equipment');

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, equipment_type, name, issued_qty, returned_qty, notes, created_at
				 FROM {$table} WHERE camp_id = %d ORDER BY id DESC",
				$camp_id
			)
		);

		return $this->ok([
			'equipment' => array_map(fn($r) => [
				'id'            => (int) $r->id,
				'equipment_type'=> $r->equipment_type,
				'name'          => $r->name,
				'issued_qty'    => (int) $r->issued_qty,
				'returned_qty'  => (int) $r->returned_qty,
				'outstanding'   => max(0, (int) $r->issued_qty - (int) $r->returned_qty),
				'notes'         => $r->notes ?? '',
				'created_at'    => $r->created_at,
			], $rows),
		]);
	}

	public function issue_equipment(WP_REST_Request $request): WP_REST_Response|\WP_Error {
		if ( ! wp_verify_nonce( (string) $request->get_param('nonce'), 'bm_panel' ) ) {
			return $this->error('bm_invalid_nonce', __('Nieprawidłowy token. Odśwież stronę.', 'basemgmt'), 403);
		}

		$camp_id        = (int) $request->get_param('_camp_id');
		$name           = sanitize_text_field($request->get_param('name') ?? '');
		$equipment_type = sanitize_text_field($request->get_param('equipment_type') ?? '');
		$issued_qty     = max(1, (int) ($request->get_param('issued_qty') ?? 1));
		$notes          = sanitize_textarea_field($request->get_param('notes') ?? '');

		if ( empty($name) ) {
			return $this->error('missing_name', __('Nazwa sprzętu jest wymagana.', 'basemgmt'), 422);
		}

		global $wpdb;
		$table  = Schema::table('camp_equipment');
		$result = $wpdb->insert($table, [
			'camp_id'        => $camp_id,
			'equipment_type' => $equipment_type,
			'name'           => $name,
			'issued_qty'     => $issued_qty,
			'returned_qty'   => 0,
			'notes'          => $notes,
		]);

		if ( ! $result ) {
			return $this->error('db_error', __('Błąd zapisu. Spróbuj ponownie.', 'basemgmt'), 500);
		}

		return $this->ok([
			'id'      => (int) $wpdb->insert_id,
			'message' => __('Sprzęt wydany.', 'basemgmt'),
		], 201);
	}

	public function return_equipment(WP_REST_Request $request): WP_REST_Response|\WP_Error {
		if ( ! wp_verify_nonce( (string) $request->get_param('nonce'), 'bm_panel' ) ) {
			return $this->error('bm_invalid_nonce', __('Nieprawidłowy token. Odśwież stronę.', 'basemgmt'), 403);
		}

		global $wpdb;
		$camp_id      = (int) $request->get_param('_camp_id');
		$item_id      = (int) $request->get_param('id');
		$return_qty   = max(1, (int) ($request->get_param('return_qty') ?? 1));
		$table        = Schema::table('camp_equipment');

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, camp_id, issued_qty, returned_qty FROM {$table} WHERE id = %d",
				$item_id
			)
		);

		if ( ! $row || (int) $row->camp_id !== $camp_id ) {
			return $this->error('not_found', __('Pozycja sprzętu nie istnieje.', 'basemgmt'), 404);
		}

		$new_returned = min(
			(int) $row->issued_qty,
			(int) $row->returned_qty + $return_qty
		);

		$wpdb->update( $table, ['returned_qty' => $new_returned], ['id' => $item_id] );

		return $this->ok([
			'id'           => $item_id,
			'returned_qty' => $new_returned,
			'outstanding'  => max(0, (int) $row->issued_qty - $new_returned),
			'message'      => __('Zwrot zarejestrowany.', 'basemgmt'),
		]);
	}
}
