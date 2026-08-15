<?php

declare(strict_types=1);

namespace BaseMgmt\Frontend;

use BaseMgmt\Auth\FrontendAuth;
use BaseMgmt\Auth\SessionManager;
use BaseMgmt\Modules\Camps\StaffRepository;

defined('ABSPATH') || exit;

/**
 * Frontend integration for Breakdance / Alpine.js workflow.
 *
 * Philosophy:
 *   The plugin does NOT render any UI HTML. All visual layout is built in
 *   Breakdance. The plugin's only frontend job is to:
 *     1. Load Alpine.js (if not already present).
 *     2. Inject `window.bmConfig` with REST URL, nonces and session state.
 *     3. Register `window.bmApi` – a thin Alpine-compatible API helper.
 *
 * Shortcodes available (all output only a <script> init block, no visual HTML):
 *   [bm_init]         – must be placed once on every Breakdance page that uses
 *                       plugin data. Enqueues assets and outputs bmConfig.
 *   [bm_auth_state]   – outputs a hidden <span> with data-bm-auth="0|1" so
 *                       Breakdance conditional visibility rules can use it.
 *
 * REST endpoints (consumed by Alpine.js components you build in Breakdance):
 *   GET  bm/v1/public/camps               → list of active camps
 *   GET  bm/v1/public/camps/{id}/staff    → staff list for a camp
 *   POST bm/v1/auth/login                 → authenticate
 *   POST bm/v1/auth/logout                → destroy session
 *   GET  bm/v1/auth/status                → current session state
 *   GET  bm/v1/panel/camp                 → own camp data + today submission status
 *   GET  bm/v1/panel/daily-count/last     → last daily count (form prefill)
 *   POST bm/v1/panel/daily-count          → submit daily count
 *   GET  bm/v1/panel/announcements        → active + archived + own announcements
 *   POST bm/v1/panel/announcements        → submit announcement for approval
 */
final class ShortcodeHandler {

	public function register(): void {
		add_shortcode('bm_init',       [$this, 'render_init']);
		add_shortcode('bm_auth_state', [$this, 'render_auth_state']);

		// Legacy shortcodes kept for backwards compatibility – they now also
		// just call render_init() so existing pages don't break.
		add_shortcode('camp_panel',         [$this, 'render_init']);
		add_shortcode('camp_access',        [$this, 'render_init']);
		add_shortcode('camp_overview',      [$this, 'render_init']);
		add_shortcode('camp_announcements', [$this, 'render_init']);
		add_shortcode('camp_daily_count',   [$this, 'render_init']);
	}

	/** Enqueues Alpine.js + all plugin frontend scripts. Called on wp_enqueue_scripts. */
	public function enqueue_assets(): void {
		// Idempotent guard – safe to call from multiple hooks.
		if ( wp_script_is('basemgmt-store', 'enqueued') ) {
			return;
		}

		// Alpine.js v3 from CDN (defer). In production you may self-host it.
		wp_enqueue_script(
			'alpinejs',
			'https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js',
			[],
			'3.14.9',
			true
		);
		wp_script_add_data( 'alpinejs', 'integrity', 'sha384-9Ax3MmS9AClxJyd5/zafcXXjxmwFhZCdsT6HJoJjarvCaAkJlk5QDzjLJm+Wdx5F' );
		wp_script_add_data( 'alpinejs', 'crossorigin', 'anonymous' );
		// Mark defer so Alpine boots after DOM is ready.
		add_filter('script_loader_tag', static function (string $tag, string $handle): string {
			if ( 'alpinejs' === $handle ) {
				return str_replace(' src=', ' defer src=', $tag);
			}
			return $tag;
		}, 10, 2);

		// REST API wrapper – no framework dependency, loaded first.
		wp_enqueue_script(
			'basemgmt-api',
			BASEMGMT_URL . 'assets/js/bm-api.js',
			[],
			BASEMGMT_VERSION,
			true
		);

		// Alpine components must be defined BEFORE bm-store.js calls
		// bmRegisterAll() on 'alpine:init'. Since all scripts go in the
		// footer they load synchronously in the order below.
		wp_enqueue_script(
			'basemgmt-components-auth',
			BASEMGMT_URL . 'assets/js/bm-components-auth.js',
			['basemgmt-api'],
			BASEMGMT_VERSION,
			true
		);

		wp_enqueue_script(
			'basemgmt-components-content',
			BASEMGMT_URL . 'assets/js/bm-components-content.js',
			['basemgmt-api'],
			BASEMGMT_VERSION,
			true
		);

		wp_enqueue_script(
			'basemgmt-components-social',
			BASEMGMT_URL . 'assets/js/bm-components-social.js',
			['basemgmt-api'],
			BASEMGMT_VERSION,
			true
		);

		// Alpine store + component registration – must load after all
		// window.bmXxx component functions are defined above.
		wp_enqueue_script(
			'basemgmt-store',
			BASEMGMT_URL . 'assets/js/bm-store.js',
			['alpinejs', 'basemgmt-api', 'basemgmt-components-auth', 'basemgmt-components-content', 'basemgmt-components-social'],
			BASEMGMT_VERSION,
			true
		);

		wp_localize_script('basemgmt-api', 'bmConfig', $this->build_config());
	}

	// ── Shortcode output ──────────────────────────────────────────────────────

	/**
	 * [bm_init] – outputs nothing visible.
	 * Assets are enqueued via enqueue_assets() on wp_enqueue_scripts.
	 * Returns empty string so shortcode leaves no stray markup.
	 */
	public function render_init(): string {
		// Force asset enqueue even if called late (e.g. inside Breakdance builder).
		if ( ! wp_script_is('basemgmt-store', 'enqueued') ) {
			$this->enqueue_assets();
		}
		return '';
	}

	/**
	 * [bm_auth_state] – outputs a tiny hidden element.
	 * Use in Breakdance conditions: "show section if .bm-auth-state[data-auth=1]".
	 */
	public function render_auth_state(): string {
		$session = SessionManager::current();
		$auth    = $session ? '1' : '0';
		$camp    = $session ? (int) $session->camp_id  : 0;
		$staff   = $session ? (int) $session->staff_id : 0;

		return sprintf(
			'<span class="bm-auth-state" data-bm-auth="%s" data-bm-camp="%d" data-bm-staff="%d" style="display:none" aria-hidden="true"></span>',
			esc_attr($auth),
			$camp,
			$staff
		);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function build_config(): array {
		$session      = SessionManager::current();
		$camp_id      = $session ? (int) $session->camp_id  : 0;
		$staff_id     = $session ? (int) $session->staff_id : 0;
		$display_name = '';

		if ( $staff_id ) {
			$staff = StaffRepository::get($staff_id);
			if ( $staff ) {
				$display_name = $staff->first_name . ' ' . $staff->last_name;
			}
		}

		return [
			'restUrl'       => esc_url_raw(rest_url('bm/v1/')),
			'wpNonce'       => wp_create_nonce('wp_rest'),    // X-WP-Nonce header
			'loginNonce'    => wp_create_nonce('bm_login'),   // POST auth/login body
			'panelNonce'    => wp_create_nonce('bm_panel'),   // POST panel/* body
			'authenticated' => (bool) $session,
			'campId'        => $camp_id,
			'staffId'       => $staff_id,
			'displayName'   => $display_name,
			'sessionExpires'=> $session ? $session->expires_at : null,
			// Inject camp list server-side so the login dropdown works without
			// requiring an authenticated REST request from the browser.
			'activeCamps'   => FrontendAuth::get_active_camps(),
		];
	}
}
