<?php

declare(strict_types=1);

namespace BaseMgmt\Cron;

use BaseMgmt\Auth\SessionManager;
use BaseMgmt\Modules\Announcements\AnnouncementRepository;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Camps\DailyCountRepository;

defined('ABSPATH') || exit;

/**
 * Manages WP-Cron events for the plugin.
 *
 * Events:
 *   bm_daily_reminders      – daily, checks for camps that haven't submitted today
 *   bm_expire_announcements – hourly, expires overdue announcements
 *   bm_cleanup_sessions     – daily, purges expired session records
 */
final class Scheduler {

	/** Called during plugin activation. */
	public static function register_schedules(): void {
		if ( ! wp_next_scheduled('bm_daily_reminders') ) {
			wp_schedule_event(
				strtotime('today 08:00:00'),
				'daily',
				'bm_daily_reminders'
			);
		}

		if ( ! wp_next_scheduled('bm_expire_announcements') ) {
			wp_schedule_event(time(), 'hourly', 'bm_expire_announcements');
		}

		if ( ! wp_next_scheduled('bm_cleanup_sessions') ) {
			wp_schedule_event(time(), 'daily', 'bm_cleanup_sessions');
		}
	}

	/** Called during plugin deactivation. */
	public static function clear_schedules(): void {
		foreach (['bm_daily_reminders', 'bm_expire_announcements', 'bm_cleanup_sessions'] as $hook) {
			$ts = wp_next_scheduled($hook);
			if ( $ts ) {
				wp_unschedule_event($ts, $hook);
			}
		}
	}

	/** Registers cron event hooks on 'init'. Called by Bootstrap. */
	public function schedule_events(): void {
		// Events already scheduled at activation; this is a no-op but hook is kept
		// for future runtime schedule adjustments.
	}

	// ── Cron callbacks ────────────────────────────────────────────────────────

	/**
	 * Sends a reminder email to the admin for each active camp
	 * that hasn't submitted a headcount today.
	 */
	public function send_daily_reminders(): void {
		$camps = CampRepository::get_all(['status' => 'active']);

		if ( empty($camps) ) {
			return;
		}

		$missing = [];
		foreach ( $camps as $camp ) {
			if ( ! DailyCountRepository::submitted_today((int) $camp->id) ) {
				$missing[] = $camp->name;
			}
		}

		if ( empty($missing) ) {
			return;
		}

		$to      = get_option('admin_email');
		$subject = sprintf(
			/* translators: %s: site name */
			__('[%s] Brak dziennego meldunku', 'basemgmt'),
			get_bloginfo('name')
		);
		$body    = __("Następujące obozy nie wysłały dziennego stanu liczebności:\n\n", 'basemgmt');
		$body   .= implode("\n", $missing);
		$body   .= "\n\n" . __('Zaloguj się do panelu, aby sprawdzić szczegóły.', 'basemgmt');

		wp_mail($to, $subject, $body);

		/**
		 * Fires after daily reminder emails are sent.
		 *
		 * @param string[] $missing Names of camps that haven't reported.
		 */
		do_action('bm_daily_reminders_sent', $missing);
	}

	/** Expires announcements whose valid_until date has passed. */
	public function expire_announcements(): void {
		$count = AnnouncementRepository::expire_overdue();
		if ( $count > 0 ) {
			do_action('bm_announcements_expired', $count);
		}
	}

	/** Cleans up expired session rows. */
	public function cleanup_sessions(): void {
		SessionManager::cleanup_expired();
	}
}
