<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Communication\ConversationRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for Komunikacja (Communication) module.
 */
final class CommunicationPage {

	public function render(): void {
		Capabilities::require_admin();

		$action    = sanitize_key($_GET['bm_action'] ?? '');
		$thread_id = (int) ($_GET['id'] ?? 0);

		match ($action) {
			'view'  => $this->render_view($thread_id),
			default => $this->render_list(),
		};
	}

	private function render_list(): void {
		$filter_camp   = (int) ($_GET['filter_camp']   ?? 0);
		$filter_status = sanitize_key($_GET['filter_status'] ?? 'all');
		$filter_unread = (bool) ($_GET['filter_unread'] ?? false);

		$filters = [];
		if ( $filter_camp )   $filters['camp_id']      = $filter_camp;
		if ( $filter_status !== 'all' ) $filters['status'] = $filter_status;
		if ( $filter_unread ) $filters['unread_admin']  = true;

		$threads   = ConversationRepository::get_all_threads($filters);
		$all_camps = CampRepository::get_all();
		$statuses  = ConversationRepository::STATUSES;
		$priorities= ConversationRepository::PRIORITIES;

		include BASEMGMT_DIR . 'templates/admin/communication/list.php';
	}

	private function render_view(int $thread_id): void {
		$thread = ConversationRepository::get_thread($thread_id);
		if ( ! $thread ) {
			AdminMenu::set_notice(__('Wątek nie znaleziony.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-communication'));
			exit;
		}

		// Mark as read for admin.
		ConversationRepository::mark_read_admin($thread_id);

		$messages  = ConversationRepository::get_messages($thread_id);
		$all_camps = CampRepository::get_all();
		$statuses  = ConversationRepository::STATUSES;
		$priorities= ConversationRepository::PRIORITIES;
		$wp_users  = get_users(['role__in' => ['administrator', 'editor'], 'number' => 50]);

		include BASEMGMT_DIR . 'templates/admin/communication/view.php';
	}

	// ── Form handlers ─────────────────────────────────────────────────────────

	public function handle_reply(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_admin_reply');

		$thread_id = (int) ($_POST['thread_id'] ?? 0);
		$content   = wp_kses_post($_POST['content'] ?? '');

		if ( ! $thread_id || ! $content ) {
			AdminMenu::set_notice(__('Treść wiadomości jest wymagana.', 'basemgmt'), 'error');
			wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=basemgmt-communication'));
			exit;
		}

		$thread = ConversationRepository::get_thread($thread_id);
		if ( ! $thread ) {
			AdminMenu::set_notice(__('Wątek nie znaleziony.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-communication'));
			exit;
		}

		ConversationRepository::add_message([
			'thread_id'   => $thread_id,
			'author_type' => 'admin',
			'author_id'   => get_current_user_id(),
			'content'     => $content,
		]);

		AdminMenu::set_notice(__('Odpowiedź wysłana.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-communication&bm_action=view&id=' . $thread_id));
		exit;
	}

	public function handle_update_thread(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_update_thread');

		$thread_id = (int) ($_POST['thread_id'] ?? 0);
		if ( ! $thread_id ) {
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-communication'));
			exit;
		}

		ConversationRepository::update_thread($thread_id, [
			'status'      => sanitize_key($_POST['status'] ?? ''),
			'priority'    => sanitize_key($_POST['priority'] ?? ''),
			'is_urgent'   => (int) ($_POST['is_urgent'] ?? 0),
			'assigned_to' => (int) ($_POST['assigned_to'] ?? 0) ?: null,
		]);

		AdminMenu::set_notice(__('Wątek zaktualizowany.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-communication&bm_action=view&id=' . $thread_id));
		exit;
	}
}
