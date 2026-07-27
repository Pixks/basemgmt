<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Cron\Scheduler;
use BaseMgmt\Frontend\ShortcodeHandler;
use BaseMgmt\Modules\Reservations\ReservationNotifier;
use BaseMgmt\REST\AuthController;
use BaseMgmt\REST\CommunicationController;
use BaseMgmt\REST\FormsController;
use BaseMgmt\REST\HelpController;
use BaseMgmt\REST\MenuController;
use BaseMgmt\REST\PanelController;
use BaseMgmt\REST\PublicController;
use BaseMgmt\REST\ReportsController;
use BaseMgmt\REST\ReservationsController;
use BaseMgmt\REST\ScheduleController;
use BaseMgmt\REST\WeatherController;

defined('ABSPATH') || exit;

/**
 * Main plugin bootstrap – singleton.
 * Wires all components together and registers WordPress hooks.
 */
final class Bootstrap {

	private static ?self $instance = null;
	private Loader $loader;

	private function __construct() {
		$this->loader = new Loader();
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		$this->load_textdomain();
		$this->register_capabilities();

		if ( is_admin() ) {
			$this->register_admin();
		}

		$this->register_rest();
		$this->register_frontend();
		$this->register_cron();
		$this->register_notifications();
		$this->register_ajax();

		$this->loader->run();
	}

	// ── Private wiring ────────────────────────────────────────────────────────

	private function load_textdomain(): void {
		load_plugin_textdomain(
			'basemgmt',
			false,
			dirname(plugin_basename(BASEMGMT_FILE)) . '/languages'
		);
	}

	private function register_capabilities(): void {
		$caps = new Capabilities();
		$this->loader->add_action('init', $caps, 'register');
	}

	private function register_admin(): void {
		$menu = new AdminMenu();
		$this->loader->add_action('admin_menu',            $menu, 'register_menus');
		$this->loader->add_action('admin_enqueue_scripts', $menu, 'enqueue_assets');
		$this->loader->add_action('admin_notices',         $menu, 'render_notices');

		// Register admin-post handlers from AdminMenu's action map.
		// Done directly with add_action (not via Loader) because we have object references.
		foreach ( $menu->post_actions() as $action => [$obj, $method] ) {
			add_action( "admin_post_{$action}", [ $obj, $method ] );
		}
	}

	private function register_rest(): void {
		$this->loader->add_action('rest_api_init', new AuthController(),             'register_routes');
		$this->loader->add_action('rest_api_init', new PublicController(),           'register_routes');
		$this->loader->add_action('rest_api_init', new PanelController(),            'register_routes');
		$this->loader->add_action('rest_api_init', new ReportsController(),          'register_routes');
		$this->loader->add_action('rest_api_init', new WeatherController(),          'register_routes');
		$this->loader->add_action('rest_api_init', new ScheduleController(),         'register_routes');
		$this->loader->add_action('rest_api_init', new ReservationsController(),     'register_routes');
		$this->loader->add_action('rest_api_init', new MenuController(),             'register_routes');
		$this->loader->add_action('rest_api_init', new CommunicationController(),    'register_routes');
		$this->loader->add_action('rest_api_init', new HelpController(),             'register_routes');
		$this->loader->add_action('rest_api_init', new FormsController(),            'register_routes');
	}

	private function register_frontend(): void {
		$sc = new ShortcodeHandler();
		$this->loader->add_action('init',               $sc, 'register');
		$this->loader->add_action('wp_enqueue_scripts', $sc, 'enqueue_assets');
	}

	private function register_cron(): void {
		$sched = new Scheduler();
		$this->loader->add_action('init',                        $sched, 'schedule_events');
		$this->loader->add_action('bm_daily_reminders',          $sched, 'send_daily_reminders');
		$this->loader->add_action('bm_expire_announcements',     $sched, 'expire_announcements');
		$this->loader->add_action('bm_cleanup_sessions',         $sched, 'cleanup_sessions');
		$this->loader->add_action('bm_refresh_weather',          $sched, 'refresh_weather');
		$this->loader->add_action('bm_expire_weather_alerts',    $sched, 'expire_weather_alerts');
		$this->loader->add_action('bm_check_missing_reports',    $sched, 'check_missing_reports');
		$this->loader->add_action('bm_sync_imgw_alerts',         $sched, 'sync_imgw_alerts');
		$this->loader->add_action('bm_expire_reservations',      $sched, 'expire_reservations');
	}

	private function register_notifications(): void {
		$notifier = new ReservationNotifier();
		$notifier->register();
	}

	private function register_ajax(): void {
		// Sortable.js drag-and-drop reorder for plan items.
		add_action('wp_ajax_bm_reorder_plan_items', [$this, 'ajax_reorder_plan_items']);
		// FullCalendar event source for reservations.
		add_action('wp_ajax_bm_calendar_events', [$this, 'ajax_calendar_events']);
	}

	// ── AJAX handlers ─────────────────────────────────────────────────────────

	public function ajax_reorder_plan_items(): void {
		check_ajax_referer('bm_reorder_items', 'nonce');
		if ( ! current_user_can('manage_basemgmt') ) {
			wp_send_json_error('forbidden', 403);
		}
		$order = array_map('intval', (array) ($_POST['order'] ?? []));
		if ( empty($order) ) {
			wp_send_json_error('no_data');
		}
		\BaseMgmt\Modules\Schedule\ScheduleRepository::reorder_items($order);
		wp_send_json_success();
	}

	public function ajax_calendar_events(): void {
		check_ajax_referer('bm_calendar', 'nonce');
		if ( ! current_user_can('manage_basemgmt') ) {
			wp_send_json_error('forbidden', 403);
		}
		$resource_id = (int) ($_GET['resource_id'] ?? 0);
		$start       = sanitize_text_field($_GET['start'] ?? gmdate('Y-m-01'));
		$end         = sanitize_text_field($_GET['end']   ?? gmdate('Y-m-t'));

		$filters = ['date_from' => $start, 'date_to' => $end];
		if ( $resource_id ) $filters['resource_id'] = $resource_id;

		$rows   = \BaseMgmt\Modules\Reservations\ReservationRepository::get_all($filters);
		$events = [];
		$colors = [
			'pending'   => '#f59e0b',
			'approved'  => '#10b981',
			'rejected'  => '#ef4444',
			'cancelled' => '#9ca3af',
			'expired'   => '#6b7280',
		];
		foreach ( $rows as $r ) {
			$resource = \BaseMgmt\Modules\Reservations\ResourceRepository::get((int) $r->resource_id);
			$camp     = \BaseMgmt\Modules\Camps\CampRepository::get((int) $r->camp_id);
			$events[] = [
				'id'            => $r->id,
				'title'         => ($resource ? $resource->name : '#' . $r->resource_id) . ' – ' . ($camp ? $camp->name : '?'),
				'start'         => $r->res_date . 'T' . $r->start_time,
				'end'           => $r->res_date . 'T' . $r->end_time,
				'color'         => $colors[$r->status] ?? '#6b7280',
				'extendedProps' => [
					'status'   => $r->status,
					'purpose'  => $r->purpose,
					'camp'     => $camp ? $camp->name : '',
					'resource' => $resource ? $resource->name : '',
				],
			];
		}
		wp_send_json($events);
	}
}
