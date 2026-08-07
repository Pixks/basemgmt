<?php

declare(strict_types=1);

namespace BaseMgmt\Cron;

use BaseMgmt\Auth\SessionManager;
use BaseMgmt\Core\EmailService;
use BaseMgmt\Modules\Announcements\AnnouncementRepository;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Camps\DailyCountRepository;
use BaseMgmt\Modules\Reservations\ReservationRepository;
use BaseMgmt\Modules\Weather\ImgwAlertsSync;
use BaseMgmt\Modules\Weather\WeatherAlertRepository;
use BaseMgmt\Modules\Weather\WeatherService;

defined('ABSPATH') || exit;

/**
 * Manages WP-Cron events for the plugin.
 *
 * Events:
 *   bm_daily_reminders       – daily, checks for camps missing today's report
 *   bm_expire_announcements  – hourly, expires overdue announcements
 *   bm_cleanup_sessions      – daily, purges expired session records
 *   bm_refresh_weather       – hourly, refreshes weather data cache
 *   bm_expire_weather_alerts – hourly, deactivates expired weather alerts
 *   bm_check_missing_reports – daily, fires hook for missing report processing
 */
final class Scheduler {

	private const ALL_HOOKS = [
		'bm_daily_reminders',
		'bm_expire_announcements',
		'bm_cleanup_sessions',
		'bm_refresh_weather',
		'bm_expire_weather_alerts',
		'bm_check_missing_reports',
		'bm_sync_imgw_alerts',
		'bm_expire_reservations',
		'bm_periodic_staff_report',
	];

	/** Called during plugin activation. */
	public static function register_schedules(): void {
		if ( ! wp_next_scheduled('bm_daily_reminders') ) {
			wp_schedule_event(strtotime('today 08:00:00'), 'daily', 'bm_daily_reminders');
		}
		if ( ! wp_next_scheduled('bm_expire_announcements') ) {
			wp_schedule_event(time(), 'hourly', 'bm_expire_announcements');
		}
		if ( ! wp_next_scheduled('bm_cleanup_sessions') ) {
			wp_schedule_event(time(), 'daily', 'bm_cleanup_sessions');
		}
		if ( ! wp_next_scheduled('bm_refresh_weather') ) {
			wp_schedule_event(time(), 'hourly', 'bm_refresh_weather');
		}
		if ( ! wp_next_scheduled('bm_expire_weather_alerts') ) {
			wp_schedule_event(time(), 'hourly', 'bm_expire_weather_alerts');
		}
		if ( ! wp_next_scheduled('bm_check_missing_reports') ) {
			wp_schedule_event(strtotime('today 08:30:00'), 'daily', 'bm_check_missing_reports');
		}

		// IMGW sync – interval is configurable; schedule only if enabled.
		self::reschedule_imgw_sync();

		// Expire past pending reservations – daily at 00:05.
		if ( ! wp_next_scheduled('bm_expire_reservations') ) {
			wp_schedule_event(strtotime('today 00:05:00'), 'daily', 'bm_expire_reservations');
		}

		// Periodic staff count report.
		self::reschedule_staff_report();
	}

	/** Called during plugin deactivation. */
	public static function clear_schedules(): void {
		foreach ( self::ALL_HOOKS as $hook ) {
			$ts = wp_next_scheduled($hook);
			if ( $ts ) {
				wp_unschedule_event($ts, $hook);
			}
		}
	}

	/**
	 * (Re)schedule the IMGW sync cron based on current settings.
	 * Safe to call multiple times – it only reschedules when interval changes.
	 */
	public static function reschedule_imgw_sync(): void {
		$settings = ImgwAlertsSync::get_settings();
		$hook     = 'bm_sync_imgw_alerts';

		$ts = wp_next_scheduled($hook);
		if ( ! $settings['enabled'] ) {
			if ( $ts ) {
				wp_unschedule_event($ts, $hook);
			}
			return;
		}

		$interval = $settings['sync_interval'] ?: 'hourly';

		// If already scheduled with same interval, nothing to do.
		if ( $ts ) {
			$scheduled_interval = wp_get_schedule($hook);
			if ( $scheduled_interval === $interval ) {
				return;
			}
			wp_unschedule_event($ts, $hook);
		}

		wp_schedule_event(time(), $interval, $hook);
	}

	/** No-op on 'init'; schedules already registered at activation. */
	public function schedule_events(): void {}

	// ── Cron callbacks ────────────────────────────────────────────────────────

	public function send_daily_reminders(): void {
		$camps = CampRepository::get_all(['status' => 'active']);
		if ( empty($camps) ) {
			return;
		}

		$missing = [];
		foreach ( $camps as $camp ) {
			if ( ! DailyCountRepository::is_submitted_today((int) $camp->id) ) {
				$missing[] = $camp->name;
			}
		}

		if ( empty($missing) ) {
			return;
		}

		$to      = get_option('bm_missing_report_emails', get_option('admin_email'));
		$subject = sprintf(
			/* translators: %s: site name */
			__('[%s] Brak dziennego meldunku', 'basemgmt'),
			get_bloginfo('name')
		);

		EmailService::send_many(
			array_filter(array_map('sanitize_email', explode(',', (string) $to))),
			$subject,
			'missing_report_notification',
			[
				'report_date'        => date_i18n('d.m.Y', strtotime(gmdate('Y-m-d'))),
				'missing_count'      => count($missing),
				'missing_camps_html' => '<ul><li>' . implode('</li><li>', array_map('esc_html', $missing)) . '</li></ul>',
			]
		);

		/** @param string[] $missing Camp names that haven't submitted. */
		do_action('bm_daily_reminders_sent', $missing);
	}

	public function expire_announcements(): void {
		$count = AnnouncementRepository::expire_overdue();
		if ( $count > 0 ) {
			do_action('bm_announcements_expired', $count);
		}
	}

	public function cleanup_sessions(): void {
		SessionManager::cleanup_expired();
	}

	/** Refresh weather data from API (cached by WeatherService). */
	public function refresh_weather(): void {
		if ( ! WeatherService::is_configured() ) {
			return;
		}
		$service = new WeatherService();
		$service->refresh();
	}

	/** Deactivate weather alerts whose valid_until has passed. */
	public function expire_weather_alerts(): void {
		$count = WeatherAlertRepository::deactivate_expired();
		if ( $count > 0 ) {
			do_action('bm_weather_alerts_expired', $count);
		}
	}

	/** Check for missing reports and fire extension hook. */
	public function check_missing_reports(): void {
		$today   = gmdate('Y-m-d');
		$missing = DailyCountRepository::get_missing_camps_for_date($today);

		/**
		 * @param array  $missing  Array of {id, name} objects.
		 * @param string $date     The date being checked (Y-m-d).
		 */
		do_action('bm_missing_reports_checked', $missing, $today);
	}

	/** Sync IMGW meteorological warnings (called by WP-Cron). */
	public function sync_imgw_alerts(): void {
		$sync = new ImgwAlertsSync();
		$sync->sync();
	}

	/** Expire pending reservations whose date has passed. */
	public function expire_reservations(): void {
		$count = ReservationRepository::expire_past();
		if ( $count > 0 ) {
			do_action('bm_reservations_expired', $count);
		}
	}

	/** Send periodic staff count report to configured recipients. */
	public function send_periodic_staff_report(): void {
		$emails = array_filter(array_map(
			'sanitize_email',
			explode(',', (string) get_option('bm_report_emails', ''))
		));

		if ( empty($emails) ) {
			return;
		}

		$today   = gmdate('Y-m-d');
		$time    = current_time('H:i');
		$camps   = CampRepository::get_all(['status' => 'active']);

		$lines   = [];
		$totals  = ['participants' => 0, 'staff' => 0, 'workers' => 0];

		foreach ( $camps as $camp ) {
			$count = \BaseMgmt\Modules\Camps\DailyCountRepository::get_by_date((int) $camp->id, $today);
			if ( $count ) {
				$p = (int) $count->participants;
				$s = (int) $count->staff;
				$w = (int) $count->workers;
				$totals['participants'] += $p;
				$totals['staff']        += $s;
				$totals['workers']      += $w;
				$lines[] = sprintf(
					"  %s: %d uczestników, %d kadra, %d pracownicy",
					$camp->name, $p, $s, $w
				);
			} else {
				$lines[] = "  {$camp->name}: brak meldunku";
			}
		}

		$subject = sprintf(
			'[%s] Raport stanów osobowych – %s %s',
			get_bloginfo('name'),
			date_i18n('d.m.Y', strtotime($today)),
			$time
		);
		$lines_html = '<table class="meta-table"><thead><tr><th>' . esc_html__('Obóz', 'basemgmt') . '</th><th>' . esc_html__('Stan', 'basemgmt') . '</th></tr></thead><tbody>';
		foreach ( $lines as $line ) {
			[$camp_name, $camp_status] = array_pad(explode(': ', $line, 2), 2, '');
			$lines_html .= '<tr><td>' . esc_html(trim($camp_name)) . '</td><td>' . esc_html(trim($camp_status)) . '</td></tr>';
		}
		$lines_html .= '</tbody></table>';

		EmailService::send_many(
			$emails,
			$subject,
			'periodic_staff_report',
			[
				'report_date'        => date_i18n('d.m.Y', strtotime($today)),
				'report_time'        => $time,
				'report_lines_html'  => $lines_html,
				'total_participants' => $totals['participants'],
				'total_staff'        => $totals['staff'],
				'total_workers'      => $totals['workers'],
			]
		);

		do_action('bm_periodic_staff_report_sent', $totals, $camps);
	}

	/**
	 * (Re)schedule the periodic staff report cron based on current settings.
	 * Interval is stored as 'bm_report_interval': hourly | twicedaily | daily.
	 */
	public static function reschedule_staff_report(): void {
		$hook     = 'bm_periodic_staff_report';
		$emails   = get_option('bm_report_emails', '');
		$interval = get_option('bm_report_interval', 'daily');

		$ts = wp_next_scheduled($hook);

		if ( ! $emails ) {
			if ( $ts ) {
				wp_unschedule_event($ts, $hook);
			}
			return;
		}

		if ( $ts ) {
			$current_interval = wp_get_schedule($hook);
			if ( $current_interval === $interval ) {
				return;
			}
			wp_unschedule_event($ts, $hook);
		}

		wp_schedule_event(time(), $interval, $hook);
	}
}
