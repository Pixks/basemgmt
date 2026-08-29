<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Modules\Communication\ConversationRepository;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Communication REST endpoints.
 *
 * All require a valid camp session.
 * camp_id is always taken from the session – never from request params.
 *
 * GET  /bm/v1/panel/messages                   – list camp's threads
 * POST /bm/v1/panel/messages                   – create new thread
 * GET  /bm/v1/panel/messages/{id}              – view thread + messages
 * POST /bm/v1/panel/messages/{id}/reply        – reply to a thread
 */
final class CommunicationController extends BaseController {

	public function register_routes(): void {
		$auth = fn(WP_REST_Request $r) => $this->require_session($r);

		register_rest_route(self::NAMESPACE, '/panel/messages', [
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'list_threads'],
				'permission_callback' => $auth,
			],
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'create_thread'],
				'permission_callback' => $auth,
			],
		]);

		register_rest_route(self::NAMESPACE, '/panel/messages/(?P<id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_thread'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/messages/(?P<id>\d+)/reply', [
			'methods'             => 'POST',
			'callback'            => [$this, 'reply'],
			'permission_callback' => $auth,
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function list_threads(WP_REST_Request $request): WP_REST_Response {
		$camp_id = (int) $request->get_param('_camp_id');
		$threads = ConversationRepository::get_all_threads(['camp_id' => $camp_id]);

		return new WP_REST_Response([
			'threads'       => array_map([$this, 'format_thread'], $threads),
			'unread_count'  => array_sum(array_column($threads, 'unread_camp')),
		]);
	}

	public function create_thread(WP_REST_Request $request): WP_REST_Response|\WP_Error {
		$nonce_ok = $this->require_panel_nonce($request);
		if ( is_wp_error($nonce_ok) ) {
			return $nonce_ok;
		}

		$camp_id  = (int) $request->get_param('_camp_id');
		$staff_id = (int) $request->get_param('_staff_id');

		$subject = sanitize_text_field($request->get_param('subject') ?? '');
		$content = wp_kses_post($request->get_param('content') ?? '');

		if ( ! $subject || ! $content ) {
			return $this->error('missing_fields', __('Temat i treść są wymagane.', 'basemgmt'));
		}

		$thread_id = ConversationRepository::create_thread([
			'camp_id'             => $camp_id,
			'subject'             => $subject,
			'priority'            => sanitize_key($request->get_param('priority') ?? 'normal'),
			'created_by_staff_id' => $staff_id,
		]);

		// First message.
		ConversationRepository::add_message([
			'thread_id'   => $thread_id,
			'author_type' => 'staff',
			'author_id'   => $staff_id,
			'content'     => $content,
		]);

		return new WP_REST_Response(['thread_id' => $thread_id], 201);
	}

	public function get_thread(WP_REST_Request $request): WP_REST_Response {
		$camp_id   = (int) $request->get_param('_camp_id');
		$thread_id = (int) $request->get_param('id');

		$thread = ConversationRepository::get_thread_for_camp($thread_id, $camp_id);
		if ( ! $thread ) {
			return $this->error('not_found', __('Wątek nie znaleziony.', 'basemgmt'), 404);
		}

		// Mark as read for camp.
		ConversationRepository::mark_read_camp($thread_id);

		$messages = ConversationRepository::get_messages($thread_id);

		return new WP_REST_Response([
			'thread'   => $this->format_thread($thread),
			'messages' => array_map([$this, 'format_message'], $messages),
		]);
	}

	public function reply(WP_REST_Request $request): WP_REST_Response|\WP_Error {
		$nonce_ok = $this->require_panel_nonce($request);
		if ( is_wp_error($nonce_ok) ) {
			return $nonce_ok;
		}

		$camp_id   = (int) $request->get_param('_camp_id');
		$staff_id  = (int) $request->get_param('_staff_id');
		$thread_id = (int) $request->get_param('id');

		$thread = ConversationRepository::get_thread_for_camp($thread_id, $camp_id);
		if ( ! $thread ) {
			return $this->error('not_found', __('Wątek nie znaleziony.', 'basemgmt'), 404);
		}
		if ( in_array($thread->status, ['closed', 'archived'], true) ) {
			return $this->error('thread_closed', __('Wątek jest zamknięty.', 'basemgmt'), 403);
		}

		$content = wp_kses_post($request->get_param('content') ?? '');
		if ( ! $content ) {
			return $this->error('missing_content', __('Treść wiadomości jest wymagana.', 'basemgmt'));
		}

		$msg_id = ConversationRepository::add_message([
			'thread_id'   => $thread_id,
			'author_type' => 'staff',
			'author_id'   => $staff_id,
			'content'     => $content,
		]);

		return new WP_REST_Response(['message_id' => $msg_id], 201);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function format_thread(object $thread): array {
		return [
			'id'              => (int) $thread->id,
			'subject'         => $thread->subject,
			'status'          => $thread->status,
			'status_label'    => ConversationRepository::STATUSES[$thread->status] ?? $thread->status,
			'priority'        => $thread->priority,
			'priority_label'  => ConversationRepository::PRIORITIES[$thread->priority] ?? $thread->priority,
			'is_urgent'       => (bool) $thread->is_urgent,
			'unread_camp'     => (int) $thread->unread_camp,
			'last_message_at' => $thread->last_message_at,
			'created_at'      => $thread->created_at,
		];
	}

	private function format_message(object $msg): array {
		return [
			'id'             => (int) $msg->id,
			'author_type'    => $msg->author_type,
			'content'        => $msg->content,
			'is_system'      => (bool) $msg->is_system,
			'attachment_url' => $msg->attachment_url,
			'created_at'     => $msg->created_at,
		];
	}
}
