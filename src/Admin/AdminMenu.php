<?php

declare(strict_types=1);

namespace BaseMgmt\Admin;

use BaseMgmt\Admin\Pages\AnnouncementsPage;
use BaseMgmt\Admin\Pages\CampsPage;
use BaseMgmt\Admin\Pages\CommunicationPage;
use BaseMgmt\Admin\Pages\DashboardPage;
use BaseMgmt\Admin\Pages\FormsPage;
use BaseMgmt\Admin\Pages\HelpPage;
use BaseMgmt\Admin\Pages\LogsPage;
use BaseMgmt\Admin\Pages\MealOptionsPage;
use BaseMgmt\Admin\Pages\MealTemplatesPage;
use BaseMgmt\Admin\Pages\MenuPage;
use BaseMgmt\Admin\Pages\LicensePage;
use BaseMgmt\Admin\Pages\OrgDocTemplatesPage;
use BaseMgmt\Admin\Pages\OrgDocumentsPage;
use BaseMgmt\Admin\Pages\OrgFinancePage;
use BaseMgmt\Admin\Pages\OrgTasksPage;
use BaseMgmt\Admin\Pages\PdfPage;
use BaseMgmt\Admin\Pages\PlanTemplatesPage;
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
	private LogsPage          $logs;
	private PlanTemplatesPage $plan_templates;
	private MealOptionsPage   $meal_options;
	private MealTemplatesPage $meal_templates;
	private PdfPage           $pdf;
	private LicensePage       $license;
	private OrgDocTemplatesPage $org_doc_templates;
	private OrgDocumentsPage    $org_documents;
	private OrgFinancePage      $org_finance;
	private OrgTasksPage        $org_tasks;

	public function __construct() {
		$this->dashboard       = new DashboardPage();
		$this->camps           = new CampsPage();
		$this->staff           = new StaffPage();
		$this->announcements   = new AnnouncementsPage();
		$this->reports         = new ReportsPage();
		$this->weather         = new WeatherPage();
		$this->schedule        = new SchedulePage();
		$this->reservations    = new ReservationsPage();
		$this->menu            = new MenuPage();
		$this->communication   = new CommunicationPage();
		$this->help            = new HelpPage();
		$this->forms           = new FormsPage();
		$this->settings        = new SettingsPage();
		$this->logs            = new LogsPage();
		$this->plan_templates  = new PlanTemplatesPage();
		$this->meal_options    = new MealOptionsPage();
		$this->meal_templates  = new MealTemplatesPage();
		$this->pdf             = new PdfPage();
		$this->license         = new LicensePage();
		$this->org_doc_templates = new OrgDocTemplatesPage();
		$this->org_documents     = new OrgDocumentsPage();
		$this->org_finance       = new OrgFinancePage();
		$this->org_tasks         = new OrgTasksPage();
	}

	// ── admin_menu hook ───────────────────────────────────────────────────────

	public function register_menus(): void {
		$pending = AnnouncementRepository::count_pending();
		$badge   = $pending ? " <span class='awaiting-mod'>$pending</span>" : '';

		$unread_comm  = ConversationRepository::count_unread_admin();
		$comm_badge   = $unread_comm ? " <span class='awaiting-mod'>$unread_comm</span>" : '';

		add_menu_page(
			__('CampLink', 'basemgmt'),
			__('CampLink', 'basemgmt'),
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

		// ── Organizacja (under CampLink) ─────────────────────────────────────
		add_submenu_page(
			'basemgmt',
			__('Organizacja – Szablony', 'basemgmt'),
			__('Organizacja', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-org',
			[$this->org_doc_templates, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Organizacja – Dokumenty', 'basemgmt'),
			'&nbsp;&nbsp;↳ ' . __('Dokumenty', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-org-documents',
			[$this->org_documents, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Organizacja – Finanse', 'basemgmt'),
			'&nbsp;&nbsp;↳ ' . __('Finanse', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-org-finance',
			[$this->org_finance, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Organizacja – Zadania', 'basemgmt'),
			'&nbsp;&nbsp;↳ ' . __('Zadania', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-org-tasks',
			[$this->org_tasks, 'render']
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
			__('Ogłoszenia', 'basemgmt'),
			__('Ogłoszenia', 'basemgmt') . $badge,
			'manage_bm_announcements',
			'basemgmt-announcements',
			[$this->announcements, 'render']
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
			__('Szablony planów dnia', 'basemgmt'),
			'↳ ' . __('Szablony', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-plan-templates',
			[$this->plan_templates, 'render']
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
			__('Szablony jadłospisów', 'basemgmt'),
			'↳ ' . __('Szablony', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-meal-templates',
			[$this->meal_templates, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Opcje jadłospisu', 'basemgmt'),
			'↳ ' . __('Opcje', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-meal-options',
			[$this->meal_options, 'render']
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
			__('Formularze', 'basemgmt'),
			__('Formularze', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-forms',
			[$this->forms, 'render']
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
			__('Raporty (PDF)', 'basemgmt'),
			__('Raporty (PDF)', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-pdf',
			[$this->pdf, 'render_list']
		);

		add_submenu_page(
			'basemgmt',
			__('Ustawienia', 'basemgmt'),
			__('Ustawienia', 'basemgmt'),
			'manage_options',
			'basemgmt-settings',
			[$this->settings, 'render']
		);

		add_submenu_page(
			'basemgmt',
			__('Licencja', 'basemgmt'),
			__('Licencja', 'basemgmt'),
			'manage_options',
			'basemgmt-license',
			[$this->license, 'render']
		);

		// Logs page: registered so it remains accessible via URL, but hidden from the sidebar.
		// Accessible from the Settings page.
		add_submenu_page(
			'basemgmt',
			__('Logi operacji', 'basemgmt'),
			__('Logi operacji', 'basemgmt'),
			'manage_basemgmt',
			'basemgmt-logs',
			[$this->logs, 'render']
		);
		remove_submenu_page('basemgmt', 'basemgmt-logs');
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

		// WP media uploader – on Org documents page.
		if ( $page === 'basemgmt-org-documents' || $page === 'basemgmt-org' ) {
			wp_enqueue_media();
		}

		if ( $page === 'basemgmt-schedule' && ! empty($_GET['edit']) ) {
			wp_enqueue_script(
				'sortablejs',
				'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
				[],
				'1.15.2',
				true
			);
			wp_script_add_data( 'sortablejs', 'integrity', 'sha384-BSxuMLxX+FCbTdYec3TbXlnMGEEM2QXTFdtDaveen71o+jswm2J36+xFqp8k4VHM' );
			wp_script_add_data( 'sortablejs', 'crossorigin', 'anonymous' );
		}

		// FullCalendar – only on reservations page.
		if ( $page === 'basemgmt-reservations' ) {
			wp_enqueue_style(
				'fullcalendar',
				'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css',
				[],
				'6.1.11'
			);
			wp_style_add_data( 'fullcalendar', 'integrity', 'sha384-OLBgp1GsljhM2TJ+sbHjaiH9txEUvgdDTAzHv2P24donTt6/529l+9Ua0vFImLlb' );
			wp_style_add_data( 'fullcalendar', 'crossorigin', 'anonymous' );
			wp_enqueue_script(
				'fullcalendar',
				'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js',
				[],
				'6.1.11',
				true
			);
			wp_script_add_data( 'fullcalendar', 'integrity', 'sha384-5JIwZN3kuxX2zKsavvNmbZ3zhZZMUtu/eQiK3BbXukpSXp0Cd2ZP4OAYKx7mrPgI' );
			wp_script_add_data( 'fullcalendar', 'crossorigin', 'anonymous' );
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
			'bm_save_camp_overview'    => [$this->camps,         'handle_save_overview'],
			'bm_save_camp_process'     => [$this->camps,         'handle_save_process'],
			'bm_save_camp_organizer'   => [$this->camps,         'handle_save_organizer'],
			'bm_save_camp_checklist'   => [$this->camps,         'handle_save_checklist'],
			'bm_save_camp_prearrival'  => [$this->camps,         'handle_save_prearrival'],
			'bm_save_camp_task'        => [$this->camps,         'handle_save_task'],
			'bm_delete_camp_task'      => [$this->camps,         'handle_delete_task'],
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
			'bm_bulk_create_plans'      => [$this->schedule,      'handle_bulk_create'],
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
			'bm_backup_data'              => [$this->settings,     'handle_backup'],
			'bm_import_data'              => [$this->settings,     'handle_import'],
			'bm_clear_data'               => [$this->settings,     'handle_clear'],
			'bm_compile_mo'               => [$this->settings,     'handle_compile_mo'],
			// Menu (Jadłospis)
			'bm_save_menu'                => [$this->menu,         'handle_save'],
			'bm_delete_menu'              => [$this->menu,         'handle_delete'],
			'bm_save_meal_item'           => [$this->menu,         'handle_save_item'],
			'bm_delete_meal_item'         => [$this->menu,         'handle_delete_item'],
			'bm_copy_menu'                => [$this->menu,         'handle_copy'],
			'bm_reset_menu_flags'         => [$this->menu,         'handle_reset_flags'],
			'bm_import_day_to_plan'       => [$this->menu,         'handle_import_day_to_plan'],
			// Communication (Komunikacja)
			'bm_create_thread'            => [$this->communication,'handle_create_thread'],
			'bm_admin_reply'              => [$this->communication,'handle_reply'],
			'bm_update_thread'            => [$this->communication,'handle_update_thread'],
			'bm_create_thread_from_submission' => [$this->forms,   'handle_create_thread_from_submission'],
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
			// Plan Templates (Szablony planów dnia)
			'bm_save_plan_template'       => [$this->plan_templates, 'handle_save'],
			'bm_delete_plan_template'     => [$this->plan_templates, 'handle_delete'],
			'bm_save_template_item'       => [$this->plan_templates, 'handle_save_item'],
			'bm_delete_template_item'     => [$this->plan_templates, 'handle_delete_item'],
			'bm_apply_plan_template'      => [$this->plan_templates, 'handle_apply'],
			// Meal Options (Opcje jadłospisu)
			'bm_save_meal_diet'           => [$this->meal_options,   'handle_save_diet'],
			'bm_delete_meal_diet'         => [$this->meal_options,   'handle_delete_diet'],
			'bm_save_meal_location'       => [$this->meal_options,   'handle_save_location'],
			'bm_delete_meal_location'     => [$this->meal_options,   'handle_delete_location'],
			// Staff unlock
			'bm_unlock_staff'             => [$this->staff,          'handle_unlock'],
			// Meal Templates (Szablony jadłospisów)
			'bm_save_meal_template'       => [$this->meal_templates, 'handle_save'],
			'bm_delete_meal_template'     => [$this->meal_templates, 'handle_delete'],
			'bm_save_meal_template_item'  => [$this->meal_templates, 'handle_save_item'],
			'bm_delete_meal_template_item'=> [$this->meal_templates, 'handle_delete_item'],
			'bm_apply_meal_template'      => [$this->meal_templates, 'handle_apply'],
			'bm_render_pdf'               => [$this->pdf,            'handle_render'],
			// License
			'bm_activate_license'         => [$this->license,        'handle_activate'],
			'bm_deactivate_license'       => [$this->license,        'handle_deactivate'],
			// Organizacja
			'bm_save_doc_template'              => [$this->org_doc_templates, 'handle_save'],
			'bm_delete_doc_template'            => [$this->org_doc_templates, 'handle_delete'],
			'bm_save_doc_library'               => [$this->org_documents,     'handle_save'],
			'bm_delete_doc_library'             => [$this->org_documents,     'handle_delete'],
			'bm_save_payment_package'           => [$this->org_finance,       'handle_save'],
			'bm_delete_payment_package'         => [$this->org_finance,       'handle_delete'],
			'bm_save_task_template'             => [$this->org_tasks,  'handle_save'],
			'bm_delete_task_template'           => [$this->org_tasks,  'handle_delete'],
			'bm_add_task_from_template'         => [$this->camps,      'handle_add_task_from_template'],
			'bm_save_camp_declaration'          => [$this->camps,      'handle_save_camp_declaration'],
			'bm_add_camp_damage'                => [$this->camps,      'handle_add_camp_damage'],
			'bm_delete_camp_damage'             => [$this->camps,      'handle_delete_camp_damage'],
			// Camp documents
			'bm_add_camp_doc_library'           => [$this->camps, 'handle_add_camp_doc_library'],
			'bm_create_camp_doc_from_template'  => [$this->camps, 'handle_create_camp_doc_from_template'],
			'bm_send_camp_doc'                  => [$this->camps, 'handle_send_camp_doc'],
			'bm_delete_camp_doc'                => [$this->camps, 'handle_delete_camp_doc'],
			// Camp finance
			'bm_save_camp_finance'              => [$this->camps, 'handle_save_camp_finance'],
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
