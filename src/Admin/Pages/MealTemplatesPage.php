<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Core\OperationLogger;
use BaseMgmt\Modules\Menu\MealRepository;
use BaseMgmt\Modules\Menu\MealTemplateRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for managing meal menu templates (Szablony jadłospisów).
 */
final class MealTemplatesPage {

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
		$templates = MealTemplateRepository::get_all();
		include BASEMGMT_DIR . 'templates/admin/menu/templates/list.php';
	}

	private function render_edit(int $id): void {
		$template   = $id ? MealTemplateRepository::get($id) : null;
		$items      = $id ? MealTemplateRepository::get_items($id) : [];
		$meal_types = MealRepository::MEAL_TYPES;

		$diet_names     = \BaseMgmt\Modules\Menu\MealOptionRepository::get_diet_names();
		$location_names = \BaseMgmt\Modules\Menu\MealOptionRepository::get_location_names();

		include BASEMGMT_DIR . 'templates/admin/menu/templates/edit.php';
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_meal_template');

		$id   = (int) ($_POST['template_id'] ?? 0);
		$name = sanitize_text_field($_POST['template_name'] ?? '');

		if ( ! $name ) {
			AdminMenu::set_notice(__('Podaj nazwę szablonu.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-meal-templates'));
			exit;
		}

		$data = [
			'name'        => $name,
			'description' => sanitize_textarea_field($_POST['template_description'] ?? ''),
			'created_by'  => get_current_user_id(),
		];

		if ( $id ) {
			MealTemplateRepository::update($id, $data);
		} else {
			$id = MealTemplateRepository::create($data);
		}

		OperationLogger::log('meal_template_saved', 'meal_template', $id, $name);

		AdminMenu::set_notice(__('Szablon jadłospisu zapisany.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-meal-templates&bm_action=edit&id=' . $id));
		exit;
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer('bm_delete_meal_template_' . $id);

		if ( $id ) {
			MealTemplateRepository::delete($id);
			OperationLogger::log('meal_template_deleted', 'meal_template', $id);
		}

		AdminMenu::set_notice(__('Szablon jadłospisu usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-meal-templates'));
		exit;
	}

	public function handle_save_item(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_meal_template_item');

		$template_id = (int) ($_POST['template_id'] ?? 0);
		$item_id     = (int) ($_POST['item_id']     ?? 0);

		MealTemplateRepository::save_item([
			'id'          => $item_id,
			'template_id' => $template_id,
			'meal_type'   => sanitize_key($_POST['meal_type']    ?? 'inne'),
			'time_from'   => sanitize_text_field($_POST['time_from']    ?? ''),
			'title'       => sanitize_text_field($_POST['title']        ?? ''),
			'description' => sanitize_textarea_field($_POST['description'] ?? ''),
			'location'    => sanitize_text_field($_POST['location']     ?? ''),
			'diet_info'   => sanitize_text_field($_POST['diet_info']    ?? ''),
			'allergens'   => sanitize_text_field($_POST['allergens']    ?? ''),
			'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
		]);

		AdminMenu::set_notice(__('Pozycja szablonu zapisana.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-meal-templates&bm_action=edit&id=' . $template_id));
		exit;
	}

	public function handle_delete_item(): void {
		Capabilities::require_admin();
		$item_id     = (int) ($_GET['item_id']     ?? 0);
		$template_id = (int) ($_GET['template_id'] ?? 0);
		check_admin_referer('bm_delete_meal_template_item_' . $item_id);

		if ( $item_id ) {
			MealTemplateRepository::delete_item($item_id);
		}

		AdminMenu::set_notice(__('Pozycja szablonu usunięta.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-meal-templates&bm_action=edit&id=' . $template_id));
		exit;
	}

	public function handle_apply(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_apply_meal_template');

		$template_id = (int) ($_POST['template_id'] ?? 0);
		$meal_day_id = (int) ($_POST['meal_day_id'] ?? 0);
		$replace     = ! empty($_POST['replace_existing']);

		if ( ! $template_id || ! $meal_day_id ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane.', 'basemgmt'), 'error');
			$redirect = $meal_day_id
				? admin_url('admin.php?page=basemgmt-menu&bm_action=edit&id=' . $meal_day_id)
				: admin_url('admin.php?page=basemgmt-menu');
			wp_safe_redirect($redirect);
			exit;
		}

		$count = MealTemplateRepository::apply_to_day($template_id, $meal_day_id, $replace);
		OperationLogger::log('meal_template_applied', 'meal_day', $meal_day_id, "template_id={$template_id} items={$count}");

		AdminMenu::set_notice(
			sprintf(__('Zastosowano szablon – dodano %d pozycji.', 'basemgmt'), $count)
		);
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu&bm_action=edit&id=' . $meal_day_id));
		exit;
	}
}
