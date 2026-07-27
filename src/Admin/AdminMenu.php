<?php

declare(strict_types=1);

namespace BaseMgmt\Admin;

use BaseMgmt\Admin\Pages\AnnouncementsPage;
use BaseMgmt\Admin\Pages\CampsPage;
use BaseMgmt\Admin\Pages\DashboardPage;
use BaseMgmt\Admin\Pages\StaffPage;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Announcements\AnnouncementRepository;

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

	public function __construct() {
		$this->dashboard     = new DashboardPage();
		$this->camps         = new CampsPage();
		$this->staff         = new StaffPage();
		$this->announcements = new AnnouncementsPage();
	}

	// ── admin_menu hook ───────────────────────────────────────────────────────

	public function register_menus(): void {
		$pending = AnnouncementRepository::count_pending();
		$badge   = $pending ? " <span class='awaiting-mod'>$pending</span>" : '';

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
			'bm_approve_announcement'  => [$this->announcements, 'handle_approve'],
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
