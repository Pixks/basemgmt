<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Modules\Reservations\ReservationRepository;
use BaseMgmt\Modules\Reservations\ResourceRepository;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Reservations endpoints – all require a valid camp session.
 *
 * GET  /bm/v1/panel/reservations/resources           – list active resources
 * GET  /bm/v1/panel/reservations/slots?resource_id=&date= – taken slots for a resource+date
 * GET  /bm/v1/panel/reservations                     – own camp's reservations
 * POST /bm/v1/panel/reservations                     – create reservation
 * POST /bm/v1/panel/reservations/(?P<id>\d+)/cancel  – cancel own reservation
 */
final class ReservationsController extends BaseController {

	public function register_routes(): void {
		$auth = fn(WP_REST_Request $r) => $this->require_session($r);

		register_rest_route(self::NAMESPACE, '/panel/reservations/resources', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_resources'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/reservations/slots', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_slots'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/reservations', [
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'get_my_reservations'],
				'permission_callback' => $auth,
			],
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'create_reservation'],
				'permission_callback' => $auth,
			],
		]);

		register_rest_route(self::NAMESPACE, '/panel/reservations/(?P<id>\d+)/cancel', [
			'methods'             => 'POST',
			'callback'            => [$this, 'cancel_reservation'],
			'permission_callback' => $auth,
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function get_resources(WP_REST_Request $request): WP_REST_Response {
		$resources = ResourceRepository::get_active();
		return new WP_REST_Response([
			'resources' => array_map([$this, 'format_resource'], $resources),
		]);
	}

	public function get_slots(WP_REST_Request $request): WP_REST_Response {
		$resource_id = (int) $request->get_param('resource_id');
		$date        = sanitize_text_field($request->get_param('date') ?? gmdate('Y-m-d'));

		if ( ! $resource_id ) {
			return $this->error('missing_param', __('Brak resource_id.', 'basemgmt'), 400);
		}

		$resource = ResourceRepository::get($resource_id);
		if ( ! $resource || $resource->status !== 'active' ) {
			return $this->error('not_found', __('Zasób nie znaleziony.', 'basemgmt'), 404);
		}

		$slots  = ReservationRepository::get_slots_for_date($resource_id, $date);
		$blocks = ResourceRepository::get_blocks($resource_id, $date, $date);

		return new WP_REST_Response([
			'resource'       => $this->format_resource($resource),
			'date'           => $date,
			'reserved_slots' => $slots,
			'block_windows'  => array_map(fn($b) => [
				'from'   => $b->block_from,
				'to'     => $b->block_to,
				'reason' => $b->reason,
			], $blocks),
		]);
	}

	public function get_my_reservations(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');
		$status  = sanitize_key($request->get_param('status') ?? '');
		$filters = $status ? ['status' => $status] : [];

		$rows = ReservationRepository::get_by_camp($camp_id, $filters);

		return new WP_REST_Response([
			'reservations' => array_map([$this, 'format_reservation'], $rows),
		]);
	}

	public function create_reservation(WP_REST_Request $request): WP_REST_Response|\WP_Error {
		$camp_id  = (int) $request->get_param('_camp_id');
		$staff_id = (int) $request->get_param('_staff_id');

		$result = ReservationRepository::create_with_conflict_check([
			'resource_id' => (int) $request->get_param('resource_id'),
			'camp_id'     => $camp_id,
			'staff_id'    => $staff_id,
			'res_date'    => sanitize_text_field($request->get_param('res_date')    ?? ''),
			'start_time'  => sanitize_text_field($request->get_param('start_time')  ?? ''),
			'end_time'    => sanitize_text_field($request->get_param('end_time')    ?? ''),
			'purpose'     => sanitize_textarea_field($request->get_param('purpose') ?? ''),
		]);

		if ( isset($result['error']) ) {
			$msgs = [
				'conflict'    => __('Termin jest już zajęty przez inną rezerwację.', 'basemgmt'),
				'blocked'     => __('Zasób ma aktywną blokadę techniczną w tym terminie.', 'basemgmt'),
				'unavailable' => __('Zasób jest niedostępny lub poza godzinami dostępności.', 'basemgmt'),
				'too_short'   => __('Czas rezerwacji jest za krótki.', 'basemgmt'),
				'too_long'    => __('Czas rezerwacji jest za długi.', 'basemgmt'),
				'db_error'    => __('Błąd zapisu. Spróbuj ponownie.', 'basemgmt'),
			];
			$http = $result['error'] === 'conflict' ? 409 : ($result['error'] === 'db_error' ? 500 : 422);
			return $this->error('reservation_' . $result['error'], $msgs[$result['error']] ?? __('Błąd rezerwacji.', 'basemgmt'), $http);
		}

		$reservation = ReservationRepository::get($result['id']);
		return new WP_REST_Response([
			'reservation' => $reservation ? $this->format_reservation($reservation) : null,
		], 201);
	}

	public function cancel_reservation(WP_REST_Request $request): WP_REST_Response|\WP_Error {
		$camp_id = (int) $request->get_param('_camp_id');
		$id      = (int) $request->get_param('id');

		$ok = ReservationRepository::cancel_by_camp($id, $camp_id);
		if ( ! $ok ) {
			return $this->error('cancel_failed', __('Nie można anulować tej rezerwacji.', 'basemgmt'), 422);
		}

		return new WP_REST_Response(['cancelled' => true]);
	}

	// ── Formatters ────────────────────────────────────────────────────────────

	private function format_resource(object $r): array {
		$type_labels = ResourceRepository::TYPES;
		return [
			'id'                   => (int) $r->id,
			'name'                 => $r->name,
			'type'                 => $r->type,
			'type_label'           => $type_labels[$r->type] ?? $r->type,
			'description'          => $r->description,
			'rules'                => $r->rules,
			'available_from'       => $r->available_from,
			'available_to'         => $r->available_to,
			'min_duration_minutes' => (int) $r->min_duration_minutes,
			'max_duration_minutes' => (int) $r->max_duration_minutes,
			'is_blocked'           => (bool) $r->is_blocked,
			'block_reason'         => $r->block_reason ?? '',
		];
	}

	private function format_reservation(object $r): array {
		$status_labels = ReservationRepository::STATUSES;
		return [
			'id'             => (int) $r->id,
			'resource_id'    => (int) $r->resource_id,
			'camp_id'        => (int) $r->camp_id,
			'res_date'       => $r->res_date,
			'start_time'     => $r->start_time,
			'end_time'       => $r->end_time,
			'purpose'        => $r->purpose,
			'status'         => $r->status,
			'status_label'   => $status_labels[$r->status] ?? $r->status,
			'admin_comment'  => $r->admin_comment ?? '',
			'created_at'     => $r->created_at,
			'updated_at'     => $r->updated_at,
		];
	}
}
