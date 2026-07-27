<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Cron\Scheduler;
use BaseMgmt\Frontend\ShortcodeHandler;
use BaseMgmt\REST\AuthController;
use BaseMgmt\REST\PanelController;
use BaseMgmt\REST\PublicController;

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
		$this->loader->add_action('rest_api_init', new AuthController(),   'register_routes');
		$this->loader->add_action('rest_api_init', new PublicController(), 'register_routes');
		$this->loader->add_action('rest_api_init', new PanelController(),  'register_routes');
	}

	private function register_frontend(): void {
		$sc = new ShortcodeHandler();
		$this->loader->add_action('init',               $sc, 'register');
		$this->loader->add_action('wp_enqueue_scripts', $sc, 'enqueue_assets');
	}

	private function register_cron(): void {
		$sched = new Scheduler();
		$this->loader->add_action('init',                    $sched, 'schedule_events');
		$this->loader->add_action('bm_daily_reminders',      $sched, 'send_daily_reminders');
		$this->loader->add_action('bm_expire_announcements', $sched, 'expire_announcements');
		$this->loader->add_action('bm_cleanup_sessions',     $sched, 'cleanup_sessions');
	}
}
