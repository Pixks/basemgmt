<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Announcements\AnnouncementRepository;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Camps\DailyCountRepository;
use BaseMgmt\Modules\Communication\ConversationRepository;
use BaseMgmt\Modules\Forms\FormRepository;
use BaseMgmt\Modules\Forms\SubmissionRepository;
use BaseMgmt\Modules\Help\HelpRepository;
use BaseMgmt\Modules\Menu\MealRepository;
use BaseMgmt\Modules\Reservations\ReservationRepository;
use BaseMgmt\Modules\Schedule\ScheduleRepository;
use BaseMgmt\Modules\Weather\WeatherAlertRepository;

defined('ABSPATH') || exit;

/**
 * Dashboard summary page for the plugin.
 */
final class DashboardPage {

	public function render(): void {
		Capabilities::require_admin();

		$today   = gmdate('Y-m-d');
		$summary = CampRepository::active_summary();
		$pending = AnnouncementRepository::count_pending();

		// Report stats for today.
		$report_counts  = DailyCountRepository::daily_status_counts($today);
		$report_totals  = DailyCountRepository::daily_totals($today);
		$missing_camps  = DailyCountRepository::get_missing_camps_for_date($today);
		$active_alerts  = WeatherAlertRepository::get_active();

		// Schedule: today's plan summary.
		$today_plans        = ScheduleRepository::get_all_headers(['date' => $today, 'status' => 'active']);
		$today_item_count   = 0;
		$today_changed_count = 0;
		foreach ( $today_plans as $plan ) {
			$items = ScheduleRepository::get_items((int) $plan->id);
			$today_item_count += count($items);
			foreach ( $items as $it ) {
				if ( $it->is_new_today || $it->is_updated_today ) $today_changed_count++;
			}
		}

		// Reservations: pending count + upcoming.
		$pending_reservations  = ReservationRepository::count_pending();
		$upcoming_reservations = ReservationRepository::get_upcoming(5);

		// Meal menu: today's menu.
		$today_menu = MealRepository::get_day_for_frontend($today);

		// Communication: open threads + unread count.
		$open_threads   = ConversationRepository::count_open_threads();
		$unread_messages = ConversationRepository::count_unread_admin();

		// Help: important article count.
		$important_help = HelpRepository::count_important();

		// Forms & Submissions: new + open counts + recent.
		$new_submissions    = SubmissionRepository::count_new();
		$open_submissions   = SubmissionRepository::count_open();
		$recent_submissions = SubmissionRepository::get_recent(5);
		$active_forms       = FormRepository::count_active();

		include BASEMGMT_DIR . 'templates/admin/dashboard.php';
	}
}
