<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Modules\Announcements\AnnouncementRepository;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Camps\DailyCountRepository;
use WP_REST_Request;

defined('ABSPATH') || exit;

/**
 * Panel endpoints – all require a valid camp session.
 *
 * GET  /bm/v1/panel/camp                – own camp data + today's submission status
 * GET  /bm/v1/panel/daily-count/last    – last daily count entry (prefill form)
 * POST /bm/v1/panel/daily-count         – submit / update today's count
 * GET  /bm/v1/panel/announcements       – active announcements for this camp
 * POST /bm/v1/panel/announcements       – submit new announcement (→ pending)
 */
final class PanelController extends BaseController {

	public function register_routes(): void {
		$auth = fn(WP_REST_Request $r) => $this->require_session($r);

		register_rest_route(self::NAMESPACE, '/panel/camp', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_camp'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/daily-count/last', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_last_count'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/daily-count', [
			'methods'             => 'POST',
			'callback'            => [$this, 'submit_count'],
			'permission_callback' => $auth,
			'args'                => [
				'participants' => ['required' => true,  'sanitize_callback' => 'absint'],
				'staff'        => ['required' => true,  'sanitize_callback' => 'absint'],
				'workers'      => ['required' => true,  'sanitize_callback' => 'absint'],
				'notes'        => ['required' => false, 'sanitize_callback' => 'sanitize_textarea_field'],
				'nonce'        => ['required' => true,  'sanitize_callback' => 'sanitize_text_field'],
			],
		]);

		register_rest_route(self::NAMESPACE, '/panel/announcements', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_announcements'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/announcements', [
			'methods'             => 'POST',
			'callback'            => [$this, 'submit_announcement'],
			'permission_callback' => $auth,
			'args'                => [
				'title'          => ['required' => true,  'sanitize_callback' => 'sanitize_text_field'],
				'content'        => ['required' => true,  'sanitize_callback' => 'wp_kses_post'],
				'valid_from'     => ['required' => true,  'sanitize_callback' => 'sanitize_text_field'],
				'valid_until'    => ['required' => true,  'sanitize_callback' => 'sanitize_text_field'],
				'attachment_url' => ['required' => false, 'sanitize_callback' => 'esc_url_raw'],
				'nonce'          => ['required' => true,  'sanitize_callback' => 'sanitize_text_field'],
			],
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function get_camp(WP_REST_Request $request): mixed {
		$camp_id = (int) $request->get_param('_camp_id');
		$camp    = CampRepository::get($camp_id);

		if ( ! $camp ) {
			return $this->error('bm_not_found', __('Obóz nie istnieje.', 'basemgmt'), 404);
		}

		$latest = DailyCountRepository::get_latest($camp_id);

		return $this->ok([
			'id'             => (int) $camp->id,
			'name'           => $camp->name,
			'start_date'     => $camp->start_date,
			'end_date'       => $camp->end_date,
			'status'         => $camp->status,
			'submitted_today' => DailyCountRepository::submitted_today($camp_id),
			'latest_count'   => $latest ? [
				'participants' => (int) $latest->participants,
				'staff'        => (int) $latest->staff,
				'workers'      => (int) $latest->workers,
				'total'        => (int) $latest->participants + (int) $latest->staff + (int) $latest->workers,
				'count_date'   => $latest->count_date,
			] : null,
		]);
	}

	public function get_last_count(WP_REST_Request $request): mixed {
		$camp_id = (int) $request->get_param('_camp_id');
		$last    = DailyCountRepository::get_latest($camp_id);

		if ( ! $last ) {
			return $this->ok(['found' => false]);
		}

		return $this->ok([
			'found'        => true,
			'participants' => (int) $last->participants,
			'staff'        => (int) $last->staff,
			'workers'      => (int) $last->workers,
			'count_date'   => $last->count_date,
		]);
	}

	public function submit_count(WP_REST_Request $request): mixed {
		if ( ! wp_verify_nonce($request->get_param('nonce'), 'bm_panel') ) {
			return $this->error('bm_invalid_nonce', __('Nieprawidłowy token. Odśwież stronę.', 'basemgmt'), 403);
		}

		$camp_id  = (int) $request->get_param('_camp_id');
		$staff_id = (int) $request->get_param('_staff_id');
		$today    = gmdate('Y-m-d');

		$ok = DailyCountRepository::upsert(
			$camp_id,
			$today,
			(int) $request->get_param('participants'),
			(int) $request->get_param('staff'),
			(int) $request->get_param('workers'),
			$request->get_param('notes') ?: null,
			$staff_id
		);

		if ( ! $ok ) {
			return $this->error('bm_save_failed', __('Nie udało się zapisać danych.', 'basemgmt'));
		}

		return $this->ok(['success' => true, 'date' => $today]);
	}

	public function get_announcements(WP_REST_Request $request): mixed {
		$camp_id = (int) $request->get_param('_camp_id');

		$active   = AnnouncementRepository::get_for_camp($camp_id, 'active');
		$expired  = AnnouncementRepository::get_for_camp($camp_id, 'expired');
		$own      = AnnouncementRepository::get_by_camp($camp_id);

		$map = static fn($a) => [
			'id'             => (int) $a->id,
			'title'          => $a->title,
			'content'        => $a->content,
			'status'         => $a->status,
			'is_urgent'      => (bool) $a->is_urgent,
			'priority'       => (int) $a->priority,
			'valid_from'     => $a->valid_from,
			'valid_until'    => $a->valid_until,
			'attachment_url' => $a->attachment_url,
		];

		return $this->ok([
			'active'   => array_map($map, $active),
			'archived' => array_map($map, $expired),
			'own'      => array_map($map, $own),
		]);
	}

	public function submit_announcement(WP_REST_Request $request): mixed {
		if ( ! wp_verify_nonce($request->get_param('nonce'), 'bm_panel') ) {
			return $this->error('bm_invalid_nonce', __('Nieprawidłowy token. Odśwież stronę.', 'basemgmt'), 403);
		}

		$camp_id  = (int) $request->get_param('_camp_id');
		$staff_id = (int) $request->get_param('_staff_id');

		$valid_from  = sanitize_text_field($request->get_param('valid_from')  ?? '');
		$valid_until = sanitize_text_field($request->get_param('valid_until') ?? '');

		if ( ! $valid_from || ! $valid_until ) {
			return $this->error('bm_validation', __('Daty obowiązywania są wymagane.', 'basemgmt'));
		}

		// Validate date formats.
		if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid_from) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid_until) ) {
			return $this->error('bm_validation', __('Nieprawidłowy format daty. Wymagany format: RRRR-MM-DD.', 'basemgmt'));
		}

		$id = AnnouncementRepository::insert([
			'title'               => $request->get_param('title'),
			'content'             => $request->get_param('content'),
			'status'              => 'pending',
			'is_urgent'           => 0,
			'priority'            => 0,
			'valid_from'          => $valid_from,
			'valid_until'         => $valid_until,
			'is_global'           => 0,
			'attachment_url'      => $request->get_param('attachment_url') ?? '',
			'submitted_camp_id'   => $camp_id,
			'submitted_staff_id'  => $staff_id,
			'camp_ids'            => [$camp_id],
		]);

		if ( ! $id ) {
			return $this->error('bm_save_failed', __('Nie udało się zapisać ogłoszenia.', 'basemgmt'));
		}

		// Notify admin via email (extensible – hooked by Scheduler).
		do_action('bm_announcement_submitted', $id, $camp_id, $staff_id);

		return $this->ok(['success' => true, 'id' => $id], 201);
	}
}
