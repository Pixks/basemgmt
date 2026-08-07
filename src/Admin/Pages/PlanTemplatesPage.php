<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Core\OperationLogger;
use BaseMgmt\Modules\Schedule\PlanTemplateRepository;
use BaseMgmt\Modules\Schedule\ScheduleRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for managing daily plan templates.
 */
final class PlanTemplatesPage {

	public function render(): void {
		Capabilities::require_admin();

		$action = sanitize_key($_GET['bm_action'] ?? '');
		$id     = (int) ($_GET['id'] ?? 0);

		match ($action) {
			'edit'  => $this->render_edit($id),
			'new'   => $this->render_edit(0),
			default => $this->render_list(),
		};
	}

	private function render_list(): void {
		$templates = PlanTemplateRepository::get_all();
		include BASEMGMT_DIR . 'templates/admin/schedule/templates/list.php';
	}

	private function render_edit(int $id): void {
		$template   = $id ? PlanTemplateRepository::get($id) : null;
		$items      = $id ? PlanTemplateRepository::get_items($id) : [];
		$recurrences = PlanTemplateRepository::RECURRENCES;
		$day_names  = PlanTemplateRepository::DAY_NAMES;
		$categories = ScheduleRepository::CATEGORIES;
		include BASEMGMT_DIR . 'templates/admin/schedule/templates/edit.php';
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_plan_template');

		$id   = (int) ($_POST['template_id'] ?? 0);
		$name = sanitize_text_field($_POST['template_name'] ?? '');

		if ( ! $name ) {
			AdminMenu::set_notice(__('Podaj nazwę szablonu.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-plan-templates'));
			exit;
		}

		$days_of_week = '';
		if ( ! empty($_POST['days_of_week']) && is_array($_POST['days_of_week']) ) {
			$days_of_week = implode(',', array_map('intval', $_POST['days_of_week']));
		}

		$data = [
			'name'         => $name,
			'description'  => sanitize_textarea_field($_POST['template_description'] ?? ''),
			'recurrence'   => sanitize_key($_POST['recurrence'] ?? PlanTemplateRepository::RECURRENCE_ONCE),
			'days_of_week' => $days_of_week,
			'created_by'   => get_current_user_id(),
		];

		if ( $id ) {
			PlanTemplateRepository::update($id, $data);
			$action_label = OperationLogger::ACTION_TEMPLATE_UPDATED;
		} else {
			$id = PlanTemplateRepository::create($data);
			$action_label = OperationLogger::ACTION_TEMPLATE_CREATED;
		}

		OperationLogger::log($action_label, 'plan_template', $id, $name);

		AdminMenu::set_notice(__('Szablon planu dnia zapisany.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-plan-templates&bm_action=edit&id=' . $id));
		exit;
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer('bm_delete_plan_template_' . $id);

		if ( $id ) {
			PlanTemplateRepository::delete($id);
			OperationLogger::log(OperationLogger::ACTION_TEMPLATE_DELETED, 'plan_template', $id);
		}

		AdminMenu::set_notice(__('Szablon usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-plan-templates'));
		exit;
	}

	public function handle_save_item(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_template_item');

		$template_id = (int) ($_POST['template_id'] ?? 0);
		$item_id     = (int) ($_POST['item_id']     ?? 0);

		PlanTemplateRepository::save_item([
			'id'           => $item_id,
			'template_id'  => $template_id,
			'time_from'    => sanitize_text_field($_POST['time_from']    ?? ''),
			'time_to'      => sanitize_text_field($_POST['time_to']      ?? ''),
			'title'        => sanitize_text_field($_POST['item_title']   ?? ''),
			'description'  => sanitize_textarea_field($_POST['description'] ?? ''),
			'category'     => sanitize_key($_POST['category']            ?? ScheduleRepository::CAT_INNE),
			'is_mandatory' => (int) ($_POST['is_mandatory']              ?? 0),
			'sort_order'   => (int) ($_POST['sort_order']                ?? 0),
		]);

		AdminMenu::set_notice(__('Pozycja szablonu zapisana.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-plan-templates&bm_action=edit&id=' . $template_id));
		exit;
	}

	public function handle_delete_item(): void {
		Capabilities::require_admin();
		$item_id     = (int) ($_GET['item_id']     ?? 0);
		$template_id = (int) ($_GET['template_id'] ?? 0);
		check_admin_referer('bm_delete_template_item_' . $item_id);

		if ( $item_id ) {
			PlanTemplateRepository::delete_item($item_id);
		}

		AdminMenu::set_notice(__('Pozycja szablonu usunięta.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-plan-templates&bm_action=edit&id=' . $template_id));
		exit;
	}

	public function handle_apply(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_apply_plan_template');

		$template_id = (int) ($_POST['template_id'] ?? 0);
		$plan_id     = (int) ($_POST['plan_id']     ?? 0);
		$replace     = ! empty($_POST['replace_existing']);

		if ( ! $template_id || ! $plan_id ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule'));
			exit;
		}

		$count = PlanTemplateRepository::apply_to_plan($template_id, $plan_id, $replace);
		OperationLogger::log('template_applied', 'plan', $plan_id, "template_id={$template_id} items={$count}");

		AdminMenu::set_notice(
			sprintf(__('Zastosowano szablon – dodano %d pozycji.', 'basemgmt'), $count)
		);
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-schedule&bm_action=edit&id=' . $plan_id));
		exit;
	}
}
