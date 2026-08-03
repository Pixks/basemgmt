<?php

declare(strict_types=1);

namespace BaseMgmt\Admin;

use BaseMgmt\Admin\Pages\AnnouncementsPage;
use BaseMgmt\Admin\Pages\CampsPage;
use BaseMgmt\Admin\Pages\CommunicationPage;
use BaseMgmt\Admin\Pages\DashboardPage;
use BaseMgmt\Admin\Pages\FormsPage;
use BaseMgmt\Admin\Pages\HelpPage;
use BaseMgmt\Admin\Pages\MenuPage;
use BaseMgmt\Admin\Pages\ReportsPage;
use BaseMgmt\Admin\Pages\ReservationsPage;
use BaseMgmt\Admin\Pages\SchedulePage;
use BaseMgmt\Admin\Pages\SettingsPage;
use BaseMgmt\Admin\Pages\StaffPage;
use BaseMgmt\Admin\Pages\WeatherPage;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Announcements\AnnouncementRepository;
use BaseMgmt\Modules\Communication\ConversationRepository;

defined('ABSPATH') || exit;

/**
 * Registers the plugin's admin menu and sub-menus.
 * Also owns asset enqueueing and flash-notice rendering.
 */
final class AdminMenu {

	private DashboardPage     $dashboard;
	private CampsPage         $camps;
	private StaffPage         $staff;
	private AnnouncementsPage $announcements;
	private ReportsPage       $reports;
	private WeatherPage       $weather;
	private SchedulePage      $schedule;
	private ReservationsPage  $reservations;
	private MenuPage          $menu;
	private CommunicationPage $communication;
	private HelpPage          $help;
	private FormsPage         $forms;
	private SettingsPage      $settings;

	public function __construct() {
		$this->dashboard     = new DashboardPage();
		$this->camps         = new CampsPage();
		$this->staff         = new StaffPage();
		$this->announcements = new AnnouncementsPage();
		$this->reports       = new ReportsPage();
		$this->weather       = new WeatherPage();
		$this->schedule      = new SchedulePage();
		$this->reservations  = new ReservationsPage();
		$this->menu          = new MenuPage();
		$this->communication = new CommunicationPage();
		$this->help          = new HelpPage();
		$this->forms         = new FormsPage();
		$this->settings      = new SettingsPage();
	}

	// ── admin_menu hook ───────────────────────────────────────────────────────

	public function register_menus(): void {
		$pending = AnnouncementRepository::count_pending();
		$badge   = $pending ? " <span class='awaiting-mod'>$pending</span>" : '';

		$unread_comm  = ConversationRepository::count_unread_admin();
		$comm_badge   = $unread_comm ? " <span class='awaiting-mod'>$unread_comm</span>" : '';

		add_menu_page(
			__('Baza Obozowa', 'basemgmt'),
			__('Baza Obozowa', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt',
			[$this->dashboard, 'render'],
			'dashicons-calendar-alt',
			30
		);

		add_submenu_page(
			'basemgmt',
			__('Dashboard', 'basemgmt'),
			__('Dashboard', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt',
			[$this->dashboard, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Obozy', 'basemgmt'),
			__('Obozy', 'basemgmt'),
			'manage_bm_camps',
			'basemgmt-camps',
			[$this->camps, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Kadra', 'basemgmt'),
			__('Kadra', 'basemgmt'),
			'manage_bm_staff',
			'basemgmt-staff',
			[$this->staff, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Ogłoszenia', 'basemgmt'),
			__('Ogłoszenia', 'basemgmt') . $badge,
			'manage_bm_announcements',
			'basemgmt-announcements',
			[$this->announcements, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Meldunki', 'basemgmt'),
			__('Meldunki', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-reports',
			[$this->reports, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Pogoda', 'basemgmt'),
			__('Pogoda', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-weather',
			[$this->weather, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Plan dnia', 'basemgmt'),
			__('Plan dnia', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-schedule',
			[$this->schedule, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Rezerwacje', 'basemgmt'),
			__('Rezerwacje', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-reservations',
			[$this->reservations, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Jadłospis', 'basemgmt'),
			__('Jadłospis', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-menu',
			[$this->menu, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Komunikacja', 'basemgmt'),
			__('Komunikacja', 'basemgmt') . $comm_badge,
			'manage_basemgmt',
			'basemgmt-communication',
			[$this->communication, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Pomoc', 'basemgmt'),
			__('Pomoc', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-help',
			[$this->help, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Formularze', 'basemgmt'),
			__('Formularze', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-forms',
			[$this->forms, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Ustawienia', 'basemgmt'),
			__('Ustawienia', 'basemgmt'),
			'manage_options',
			'basemgmt-settings',
			[$this->settings, 'render']
		);
	}

	// ── Asset enqueueing ──────────────────────────────────────────────────────

	public function enqueue_assets(string $hook): void {
		if ( ! str_contains($hook, 'basemgmt') ) {
			return;
		}

		wp_enqueue_style(
			'basemgmt-admin',
			BASEMGMT_URL . 'assets/css/admin.css',
			[],
			BASEMGMT_VERSION
		);

		wp_enqueue_script(
			'basemgmt-admin',
			BASEMGMT_URL . 'assets/js/admin.js',
			['jquery'],
			BASEMGMT_VERSION,
			true
		);

		// Sortable.js – only on schedule edit page.
		$page = sanitize_key($_GET['page'] ?? '');
		if ( $page === 'basemgmt-schedule' && ! empty($_GET['edit']) ) {
			wp_enqueue_script(
				'sortablejs',
				'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
				[],
				'1.15.2',
				true
			);
		}

		// FullCalendar – only on reservations page.
		if ( $page === 'basemgmt-reservations' ) {
			wp_enqueue_style(
				'fullcalendar',
				'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css',
				[],
				'6.1.11'
			);
			wp_enqueue_script(
				'fullcalendar',
				'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js',
				[],
				'6.1.11',
				true
			);
		}
	}

	// ── Admin notices (flash messages via transient) ──────────────────────────

	public function render_notices(): void {
		$msg = get_transient('bm_admin_notice_' . get_current_user_id());
		if ( ! $msg ) {
			return;
		}
		delete_transient('bm_admin_notice_' . get_current_user_id());

		$type = $msg['type'] ?? 'success';
		$text = $msg['text'] ?? '';
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr($type),
			esc_html($text)
		);
	}

	// ── admin-post action map ─────────────────────────────────────────────────

	/**
	 * Returns the map of admin_post actions to [object, method] pairs.
	 * Used by Bootstrap to register hooks.
	 *
	 * @return array<string, array{0:object, 1:string}>
	 */
	public function post_actions(): array {
		return [
			'bm_save_camp'             => [$this->camps,         'handle_save'],
			'bm_delete_camp'           => [$this->camps,         'handle_delete'],
			'bm_save_staff'            => [$this->staff,         'handle_save'],
			'bm_delete_staff'          => [$this->staff,         'handle_delete'],
			'bm_toggle_staff_active'   => [$this->staff,         'handle_toggle_active'],
			'bm_reset_staff_code'      => [$this->staff,         'handle_reset_code'],
			'bm_save_announcement'     => [$this->announcements, 'handle_save'],
			'bm_delete_announcement'   => [$this->announcements, 'handle_delete'],
			'bm_approve_announcement'   => [$this->announcements, 'handle_approve'],
			'bm_save_report'            => [$this->reports,       'handle_save'],
			'bm_save_weather_settings'  => [$this->weather,       'handle_save_settings'],
			'bm_save_weather_alert'     => [$this->weather,       'handle_save_alert'],
			'bm_delete_weather_alert'   => [$this->weather,       'handle_delete_alert'],
			'bm_refresh_weather'        => [$this->weather,       'handle_refresh_weather'],
			'bm_sync_imgw'              => [$this->weather,       'handle_sync_imgw'],
			// Schedule (Plan dnia)
			'bm_save_schedule'          => [$this->schedule,      'handle_save'],
			'bm_delete_plan'            => [$this->schedule,      'handle_delete'],
			'bm_save_plan_item'         => [$this->schedule,      'handle_save_item'],
			'bm_delete_plan_item'       => [$this->schedule,      'handle_delete_item'],
			'bm_copy_plan'              => [$this->schedule,      'handle_copy'],
			'bm_reset_plan_flags'       => [$this->schedule,      'handle_reset_flags'],
			// Reservations (Rezerwacje)
			'bm_save_resource'          => [$this->reservations,  'handle_save_resource'],
			'bm_delete_resource'        => [$this->reservations,  'handle_delete_resource'],
			'bm_save_resource_block'    => [$this->reservations,  'handle_save_block'],
			'bm_delete_resource_block'  => [$this->reservations,  'handle_delete_block'],
			'bm_reservation_action'     => [$this->reservations,  'handle_reservation_action'],
			'bm_admin_create_reservation' => [$this->reservations,'handle_admin_create_reservation'],
			// Settings
			'bm_save_settings'            => [$this->settings,     'handle_save'],
			'bm_send_test_email'          => [$this->settings,     'handle_send_test'],
			'bm_save_email_template'      => [$this->settings,     'handle_save_template'],
			'bm_reset_email_template'     => [$this->settings,     'handle_reset_template'],
			// Menu (Jadłospis)
			'bm_save_menu'                => [$this->menu,         'handle_save'],
			'bm_delete_menu'              => [$this->menu,         'handle_delete'],
			'bm_save_meal_item'           => [$this->menu,         'handle_save_item'],
			'bm_delete_meal_item'         => [$this->menu,         'handle_delete_item'],
			'bm_copy_menu'                => [$this->menu,         'handle_copy'],
			'bm_reset_menu_flags'         => [$this->menu,         'handle_reset_flags'],
			// Communication (Komunikacja)
			'bm_create_thread'            => [$this->communication,'handle_create_thread'],
			'bm_admin_reply'              => [$this->communication,'handle_reply'],
			'bm_update_thread'            => [$this->communication,'handle_update_thread'],
			// Help (Pomoc)
			'bm_save_help'                => [$this->help,         'handle_save'],
			'bm_delete_help'              => [$this->help,         'handle_delete'],
			// Forms & Submissions (Formularze i Zgłoszenia)
			'bm_save_form'                => [$this->forms,        'handle_save_form'],
			'bm_delete_form'              => [$this->forms,        'handle_delete_form'],
			'bm_save_form_field'          => [$this->forms,        'handle_save_field'],
			'bm_delete_form_field'        => [$this->forms,        'handle_delete_field'],
			'bm_update_submission'        => [$this->forms,        'handle_update_submission'],
			'bm_download_attachment'      => [$this->forms,        'handle_download_attachment'],
		];
	}

	// ── Static flash helper ───────────────────────────────────────────────────

	public static function set_notice(string $text, string $type = 'success'): void {
		set_transient(
			'bm_admin_notice_' . get_current_user_id(),
			['text' => $text, 'type' => $type],
			60
		);
	}
}
