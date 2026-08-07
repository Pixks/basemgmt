<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Database\Schema;
use BaseMgmt\Modules\Menu\MealRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for Jadłospis (Meal Menu) module.
 */
final class MenuPage {

	public function render(): void {
		Capabilities::require_admin();

		// Allow inline table creation without plugin reactivation.
		if ( isset($_GET['bm_create_tables']) ) {
			check_admin_referer('bm_create_tables');
			Schema::create_tables();
			AdminMenu::set_notice(__('Tabele zostały utworzone / zaktualizowane.', 'basemgmt'));
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu'));
			exit;
		}

		$action = sanitize_key($_GET['bm_action'] ?? '');
		$id     = (int) ($_GET['id'] ?? 0);
		$date   = sanitize_text_field($_GET['date'] ?? '');

		match ($action) {
			'edit'  => $this->render_edit($id),
			'new'   => $this->render_edit(0, $date),
			default => $this->render_list(),
		};
	}

	private function render_list(): void {
		$filter_date = sanitize_text_field($_GET['filter_date'] ?? '');
		$days        = MealRepository::get_all_days($filter_date ? ['date' => $filter_date] : []);
		include BASEMGMT_DIR . 'templates/admin/menu/list.php';
	}

	private function render_edit(int $id, string $default_date = ''): void {
		$day        = $id ? MealRepository::get_day($id) : null;
		$items      = $id ? MealRepository::get_items($id) : [];
		$date       = $day ? $day->meal_date : ($default_date ?: gmdate('Y-m-d'));
		$meal_types = MealRepository::MEAL_TYPES;

		// Pass predefined options to the template.
		$diet_names     = \BaseMgmt\Modules\Menu\MealOptionRepository::get_diet_names();
		$location_names = \BaseMgmt\Modules\Menu\MealOptionRepository::get_location_names();

		include BASEMGMT_DIR . 'templates/admin/menu/edit.php';
	}

	// ── Form handlers ─────────────────────────────────────────────────────────

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_menu');

		$id   = (int) ($_POST['meal_day_id'] ?? 0);
		$date = sanitize_text_field($_POST['meal_date'] ?? '');

		if ( ! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ) {
			AdminMenu::set_notice(__('Nieprawidłowa data.', 'basemgmt'), 'error');
			wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=basemgmt-menu'));
			exit;
		}

		$data = [
			'meal_date' => $date,
			'notes'     => sanitize_textarea_field($_POST['notes'] ?? ''),
			'status'    => sanitize_key($_POST['status'] ?? 'published'),
		];

		if ( $id ) {
			MealRepository::update_day($id, $data);
		} else {
			$existing = MealRepository::get_day_by_date($date);
			if ( $existing ) {
				$id = (int) $existing->id;
				MealRepository::update_day($id, $data);
			} else {
				$id = MealRepository::create_day($data);
			}
		}

		if ( ! $id ) {
			AdminMenu::set_notice(__('Błąd zapisu jadłospisu. Sprawdź czy tabele istnieją (Jadłospis → Utwórz tabele).', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu'));
			exit;
		}

		AdminMenu::set_notice(__('Jadłospis zapisany.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu&bm_action=edit&id=' . $id));
		exit;
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer('bm_delete_menu_' . $id);
		MealRepository::delete_day($id);
		AdminMenu::set_notice(__('Jadłospis usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu'));
		exit;
	}

	public function handle_save_item(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_meal_item');

		$meal_day_id = (int) ($_POST['meal_day_id'] ?? 0);
		if ( ! $meal_day_id ) {
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu'));
			exit;
		}

		MealRepository::save_item([
			'id'               => (int) ($_POST['item_id'] ?? 0),
			'meal_day_id'      => $meal_day_id,
			'meal_type'        => sanitize_key($_POST['meal_type'] ?? 'inne'),
			'time_from'        => sanitize_text_field($_POST['time_from'] ?? ''),
			'title'            => sanitize_text_field($_POST['title'] ?? ''),
			'description'      => sanitize_textarea_field($_POST['description'] ?? ''),
			'location'         => sanitize_text_field($_POST['location'] ?? ''),
			'diet_info'        => sanitize_text_field($_POST['diet_info'] ?? ''),
			'allergens'        => sanitize_text_field($_POST['allergens'] ?? ''),
			'sort_order'       => (int) ($_POST['sort_order'] ?? 0),
			'is_new_today'     => (int) ($_POST['is_new_today'] ?? 0),
			'is_updated_today' => (int) ($_POST['is_updated_today'] ?? 0),
		]);

		// Auto-add to day plan if checkbox is checked.
		if ( ! empty($_POST['add_to_plan']) ) {
			$day = MealRepository::get_day($meal_day_id);
			if ( $day ) {
				$this->auto_add_meal_to_plan(
					$day->meal_date,
					sanitize_text_field($_POST['title'] ?? ''),
					sanitize_text_field($_POST['time_from'] ?? ''),
					sanitize_key($_POST['meal_type'] ?? 'inne')
				);
			}
		}

		AdminMenu::set_notice(__('Posiłek zapisany.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu&bm_action=edit&id=' . $meal_day_id));
		exit;
	}

	public function handle_delete_item(): void {
		Capabilities::require_admin();
		$id     = (int) ($_GET['item_id'] ?? 0);
		$day_id = (int) ($_GET['day_id'] ?? 0);
		check_admin_referer('bm_delete_meal_item_' . $id);
		MealRepository::delete_item($id);
		AdminMenu::set_notice(__('Posiłek usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu&bm_action=edit&id=' . $day_id));
		exit;
	}

	public function handle_copy(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_copy_menu');

		$from   = sanitize_text_field($_POST['copy_from'] ?? '');
		$to     = sanitize_text_field($_POST['copy_to']   ?? '');

		if ( ! $from || ! $to ) {
			AdminMenu::set_notice(__('Podaj datę źródłową i docelową.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu'));
			exit;
		}

		$result = MealRepository::copy_from_date($from, $to);

		if ( false === $result ) {
			AdminMenu::set_notice(__('Brak jadłospisu dla wybranego dnia źródłowego.', 'basemgmt'), 'error');
		} else {
			AdminMenu::set_notice(__('Jadłospis skopiowany.', 'basemgmt'));
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu'));
		exit;
	}

	public function handle_import_day_to_plan(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_import_day_to_plan');

		$meal_day_id = (int) ($_POST['meal_day_id'] ?? 0);
		$day         = $meal_day_id ? MealRepository::get_day($meal_day_id) : null;

		if ( ! $day ) {
			AdminMenu::set_notice(__('Nie znaleziono jadłospisu do importu.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu'));
			exit;
		}

		$plan_id = $this->get_or_create_global_plan((string) $day->meal_date);
		if ( ! $plan_id ) {
			AdminMenu::set_notice(__('Nie udało się przygotować planu dnia.', 'basemgmt'), 'error');
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu&bm_action=edit&id=' . $meal_day_id));
			exit;
		}

		$added   = 0;
		$skipped = 0;
		foreach ( MealRepository::get_items($meal_day_id) as $item ) {
			if ( $this->add_meal_to_plan_item($plan_id, $item->title, $item->time_from, $item->meal_type) ) {
				$added++;
			} else {
				$skipped++;
			}
		}

		AdminMenu::set_notice(
			sprintf(__('Dodano %1$d posiłków do planu dnia. Pominięto duplikaty: %2$d.', 'basemgmt'), $added, $skipped)
		);
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu&bm_action=edit&id=' . $meal_day_id));
		exit;
	}

	public function handle_reset_flags(): void {
		Capabilities::require_admin();
		$day_id = (int) ($_GET['day_id'] ?? 0);
		check_admin_referer('bm_reset_menu_flags_' . $day_id);
		MealRepository::reset_flags($day_id);
		AdminMenu::set_notice(__('Flagi zmian zresetowane.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-menu&bm_action=edit&id=' . $day_id));
		exit;
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Add a meal item as a plan item to the global daily plan for the given date.
	 * Creates the plan header if none exists.
	 */
	private function auto_add_meal_to_plan(string $date, string $title, string $time_from, string $meal_type): void {
		$plan_id = $this->get_or_create_global_plan($date);
		if ( ! $plan_id ) {
			return;
		}

		$this->add_meal_to_plan_item($plan_id, $title, $time_from, $meal_type);
	}

	private function get_or_create_global_plan(string $date): int {
		$plans = \BaseMgmt\Modules\Schedule\ScheduleRepository::get_all_headers([
			'date'     => $date,
			'status'   => \BaseMgmt\Modules\Schedule\ScheduleRepository::PLAN_ACTIVE,
			'is_global'=> 1,
		]);

		if ( ! empty($plans) ) {
			$plan_id = (int) reset($plans)->id;
		} else {
			$plan_id = \BaseMgmt\Modules\Schedule\ScheduleRepository::create_header([
				'plan_date'  => $date,
				'title'      => sprintf(__('Plan dnia %s', 'basemgmt'), date_i18n('d.m.Y', strtotime($date))),
				'is_global'  => 1,
				'status'     => \BaseMgmt\Modules\Schedule\ScheduleRepository::PLAN_ACTIVE,
				'created_by' => get_current_user_id(),
			]);
		}

		if ( ! $plan_id ) {
			return 0;
		}

		return $plan_id;
	}

	private function add_meal_to_plan_item(int $plan_id, string $title, string $time_from, string $meal_type): bool {
		$meal_type_labels = MealRepository::MEAL_TYPES;
		$category         = \BaseMgmt\Modules\Schedule\ScheduleRepository::CAT_POSILEK;
		$plan_title       = sprintf('%s – %s', $meal_type_labels[$meal_type] ?? $meal_type, $title);

		if ( \BaseMgmt\Modules\Schedule\ScheduleRepository::has_matching_item($plan_id, [
			'time_from' => $time_from,
			'time_to'   => '',
			'title'     => $plan_title,
			'category'  => $category,
		]) ) {
			return false;
		}

		\BaseMgmt\Modules\Schedule\ScheduleRepository::create_item([
			'plan_id'          => $plan_id,
			'time_from'        => $time_from,
			'time_to'          => '',
			'title'            => $plan_title,
			'description'      => '',
			'category'         => $category,
			'item_status'      => \BaseMgmt\Modules\Schedule\ScheduleRepository::ITEM_ACTIVE,
			'is_mandatory'     => 0,
			'sort_order'       => 0,
			'is_new_today'     => 0,
			'is_updated_today' => 0,
		]);

		return true;
	}
}
