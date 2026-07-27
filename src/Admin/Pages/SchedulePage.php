<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Schedule\ScheduleRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for Plan dnia (Day Schedule) module.
 */
final class SchedulePage {

	public function render(): void {
		Capabilities::require_admin();

		$action = sanitize_key($_GET['bm_action'] ?? '');
		$id     = (int) ($_GET['id'] ?? 0);
		$date   = sanitize_text_field($_GET['date'] ?? '');

		match ($action) {
			'edit'   => $this->render_edit($id),
			'new'    => $this->render_edit(0, $date),
			default  => $this->render_list(),
		};
	}

	private function render_list(): void {
		$filter_date = sanitize_text_field($_GET['filter_date'] ?? '');
		$headers     = ScheduleRepository::get_all_headers(
			$filter_date ? ['date' => $filter_date] : []
		);

		include BASEMGMT_DIR . 'templates/admin/schedule/list.php';
	}

	private function render_edit(int $id, string $default_date = ''): void {
		$header    = $id ? ScheduleRepository::get_header($id) : null;
		$items     = $id ? ScheduleRepository::get_items($id) : [];
		$all_camps = CampRepository::get_all(['status' => 'active']);
		$assigned  = $id ? ScheduleRepository::get_assigned_camps($id) : [];
		$date      = $header ? $header->plan_date : ($default_date ?: gmdate('Y-m-d'));

		$categories = ScheduleRepository::CATEGORIES;

		include BASEMGMT_DIR . 'templates/admin/schedule/edit.php';
	}

	// ── Form handlers ─────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_schedule');

		$id        = (int) ($_POST['plan_id'] ?? 0);
		$plan_date = sanitize_text_field($_POST['plan_date'] ?? '');
		$is_global = (int) ($_POST['is_global'] ?? 1);
		$status    = sanitize_key($_POST['plan_status'] ?? ScheduleRepository::PLAN_ACTIVE);
		$title     = sanitize_text_field($_POST['plan_title'] ?? '');

		if ( ! $plan_date ) {
			AdminMenu::set_notice(__('Podaj datę planu.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule'));
			exit;
		}

		if ( $id ) {
			ScheduleRepository::update_header($id, [
				'title'     => $title,
				'is_global' => $is_global,
				'status'    => $status,
			]);
		} else {
			$id = ScheduleRepository::create_header([
				'plan_date'  => $plan_date,
				'title'      => $title,
				'is_global'  => $is_global,
				'status'     => $status,
				'created_by' => get_current_user_id(),
			]);
		}

		if ( ! $is_global ) {
			$camp_ids = array_map('intval', (array) ($_POST['camp_ids'] ?? []));
			ScheduleRepository::assign_camps($id, $camp_ids);
		} else {
			ScheduleRepository::assign_camps($id, []);
		}

		AdminMenu::set_notice(__('Plan dnia zapisany.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule&bm_action=edit&id=' . $id));
		exit;
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer('bm_delete_plan_' . $id);
		if ( $id ) ScheduleRepository::delete_header($id);
		AdminMenu::set_notice(__('Plan dnia usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule'));
		exit;
	}

	public function handle_save_item(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_plan_item');

		$plan_id  = (int) ($_POST['plan_id']  ?? 0);
		$item_id  = (int) ($_POST['item_id']  ?? 0);
		$user_id  = get_current_user_id();

		$data = [
			'plan_id'          => $plan_id,
			'time_from'        => sanitize_text_field($_POST['time_from']        ?? ''),
			'time_to'          => sanitize_text_field($_POST['time_to']          ?? ''),
			'title'            => sanitize_text_field($_POST['item_title']       ?? ''),
			'description'      => sanitize_textarea_field($_POST['description']  ?? ''),
			'category'         => sanitize_key($_POST['category']                ?? ScheduleRepository::CAT_INNE),
			'item_status'      => sanitize_key($_POST['item_status']             ?? ScheduleRepository::ITEM_ACTIVE),
			'is_mandatory'     => (int) ($_POST['is_mandatory']                  ?? 0),
			'sort_order'       => (int) ($_POST['sort_order']                    ?? 0),
			'is_new_today'     => (int) ($_POST['is_new_today']                  ?? 0),
			'is_updated_today' => (int) ($_POST['is_updated_today']              ?? 0),
		];

		if ( $item_id ) {
			ScheduleRepository::update_item($item_id, $data, $user_id);
		} else {
			ScheduleRepository::create_item($data);
		}

		AdminMenu::set_notice(__('Pozycja planu zapisana.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule&bm_action=edit&id=' . $plan_id));
		exit;
	}

	public function handle_delete_item(): void {
		Capabilities::require_admin();
		$item_id = (int) ($_GET['item_id'] ?? 0);
		$plan_id = (int) ($_GET['plan_id'] ?? 0);
		check_admin_referer('bm_delete_item_' . $item_id);
		if ( $item_id ) ScheduleRepository::delete_item($item_id);
		AdminMenu::set_notice(__('Pozycja usunięta.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule&bm_action=edit&id=' . $plan_id));
		exit;
	}

	public function handle_copy(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_copy_plan');
		$from    = sanitize_text_field($_POST['copy_from'] ?? '');
		$to      = sanitize_text_field($_POST['copy_to']   ?? '');
		$user_id = get_current_user_id();
		if ( ! $from || ! $to ) {
			AdminMenu::set_notice(__('Podaj daty kopiowania.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule'));
			exit;
		}
		$new_id = ScheduleRepository::copy_from_date($from, $to, $user_id);
		if ( $new_id ) {
			AdminMenu::set_notice(__('Plan skopiowany.', 'basemgmt'));
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule&bm_action=edit&id=' . $new_id));
		} else {
			AdminMenu::set_notice(__('Nie znaleziono planu dla podanej daty lub plan na docelowy dzień już istnieje.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule'));
		}
		exit;
	}

	public function handle_reset_flags(): void {
		Capabilities::require_admin();
		$plan_id = (int) ($_GET['plan_id'] ?? 0);
		check_admin_referer('bm_reset_flags_' . $plan_id);
		if ( $plan_id ) ScheduleRepository::reset_daily_flags($plan_id);
		AdminMenu::set_notice(__('Flagi zmian zresetowane.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule&bm_action=edit&id=' . $plan_id));
		exit;
	}
}
