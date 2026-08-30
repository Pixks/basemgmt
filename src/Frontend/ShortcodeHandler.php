<?php

declare(strict_types=1);

namespace BaseMgmt\Frontend;

use BaseMgmt\Auth\FrontendAuth;
use BaseMgmt\Auth\SessionManager;
use BaseMgmt\Modules\Camps\StaffRepository;
use BaseMgmt\Modules\Camps\CampRepository;

defined('ABSPATH') || exit;

/**
 * Frontend integration for shortcode-based camp panel UI.
 */
final class ShortcodeHandler {

	/** @var array<string,string> */
	private array $panel_shortcodes = [
		'bm_panel_login'               => 'login',
		'bm_panel_camp_header'         => 'camp_header',
		'bm_panel_logout'              => 'logout',
		'bm_panel_announcements'       => 'announcements',
		'bm_panel_announcement_form'   => 'announcement_form',
		'bm_panel_reports'             => 'reports',
		'bm_panel_weather'             => 'weather',
		'bm_panel_schedule'            => 'schedule',
		'bm_panel_reservations'        => 'reservations',
		'bm_panel_menu_day'            => 'menu_day',
		'bm_panel_menu_week'           => 'menu_week',
		'bm_panel_conversations'       => 'conversations',
		'bm_panel_conversation_new'    => 'conversation_new',
		'bm_panel_conversation_thread' => 'conversation_thread',
		'bm_panel_help_list'           => 'help_list',
		'bm_panel_help_article'        => 'help_article',
		'bm_panel_forms_list'          => 'forms_list',
		'bm_panel_form'                => 'form',
		'bm_panel_submissions_list'    => 'submissions_list',
		'bm_panel_submission'          => 'submission',
		'bm_panel_unread_counter'      => 'unread_counter',
		'bm_panel_folder_docs'         => 'folder_docs',
		'bm_panel_camp_documents'      => 'camp_documents',
		'bm_panel_damages'             => 'damages',
		'bm_panel_declaration'         => 'declaration',
		'bm_panel_decl_docs'           => 'decl_docs',
		'bm_panel_equipment'           => 'equipment',
	];

	public function register(): void {
		add_shortcode('bm_init',       [$this, 'render_init']);
		add_shortcode('bm_auth_state', [$this, 'render_auth_state']);
		add_shortcode('bm_panel_element', [$this, 'render_panel_element_shortcode']);
		add_shortcode('bm_panel_session_guard', [$this, 'render_panel_session_guard']);

		foreach ($this->panel_shortcodes as $shortcode => $element) {
			add_shortcode($shortcode, function ($atts = []) use ($element): string {
				return $this->render_panel_element($element, is_array($atts) ? $atts : []);
			});
		}

		// Legacy shortcodes kept for backwards compatibility.
		add_shortcode('camp_panel',         [$this, 'render_init']);
		add_shortcode('camp_access',        [$this, 'render_init']);
		add_shortcode('camp_overview',      [$this, 'render_init']);
		add_shortcode('camp_announcements', [$this, 'render_init']);
		add_shortcode('camp_daily_count',   [$this, 'render_init']);

		add_shortcode('bm_var', [$this, 'render_var']);
	}

	/** Enqueues frontend scripts and shared shortcode styles. */
	public function enqueue_assets(): void {
		if ( wp_script_is('basemgmt-store', 'enqueued') ) {
			return;
		}

		wp_enqueue_script(
			'alpinejs',
			'https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js',
			[],
			'3.14.9',
			true
		);
		wp_script_add_data( 'alpinejs', 'integrity', 'sha384-9Ax3MmS9AClxJyd5/zafcXXjxmwFhZCdsT6HJoJjarvCaAkJlk5QDzjLJm+Wdx5F' );
		wp_script_add_data( 'alpinejs', 'crossorigin', 'anonymous' );
		add_filter('script_loader_tag', static function (string $tag, string $handle): string {
			if ( 'alpinejs' === $handle ) {
				return str_replace(' src=', ' defer src=', $tag);
			}
			return $tag;
		}, 10, 2);

		wp_enqueue_style(
			'basemgmt-shortcodes',
			BASEMGMT_URL . 'assets/css/bm-shortcodes.css',
			[],
			BASEMGMT_VERSION
		);
		wp_add_inline_style('basemgmt-shortcodes', PanelStyleSettings::build_inline_css());

		$custom_font_url = PanelStyleSettings::get_custom_font_url();
		if ( $custom_font_url !== '' ) {
			wp_enqueue_style('basemgmt-custom-font', $custom_font_url, [], null);
		}

		wp_enqueue_script(
			'basemgmt-api',
			BASEMGMT_URL . 'assets/js/bm-api.js',
			[],
			BASEMGMT_VERSION,
			true
		);

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

		wp_enqueue_script(
			'basemgmt-store',
			BASEMGMT_URL . 'assets/js/bm-store.js',
			['alpinejs', 'basemgmt-api', 'basemgmt-components-auth', 'basemgmt-components-content', 'basemgmt-components-social'],
			BASEMGMT_VERSION,
			true
		);

		wp_localize_script('basemgmt-api', 'bmConfig', $this->build_config());
	}

	public function render_init(): string {
		if ( ! wp_script_is('basemgmt-store', 'enqueued') ) {
			$this->enqueue_assets();
		}
		return '';
	}

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

	/**
	 * [bm_panel_element type="login"] generic panel element renderer.
	 *
	 * @param array<string,mixed> $atts
	 */
	public function render_panel_element_shortcode($atts = []): string {
		$atts = shortcode_atts([
			'type' => 'login',
		], is_array($atts) ? $atts : []);

		$type = sanitize_key((string) $atts['type']);
		return $this->render_panel_element($type, $atts);
	}

	/**
	 * [bm_panel_session_guard]...[/bm_panel_session_guard]
	 *
	 * @param array<string,mixed> $atts
	 */
	public function render_panel_session_guard($atts = [], string $content = ''): string {
		$this->render_init();
		$atts = shortcode_atts([
			'logged'   => '1',
			'redirect' => '',
		], is_array($atts) ? $atts : []);

		$show_if_logged = ! in_array((string) $atts['logged'], ['0', 'false', 'no'], true);
		$condition = $show_if_logged ? '$store.bm.authenticated' : '!$store.bm.authenticated';

		$redirect_snippet = '';
		if ( ! empty( $atts['redirect'] ) ) {
			$redirect_raw = trim( (string) $atts['redirect'] );
			if ( str_starts_with( $redirect_raw, '/' ) && ! str_starts_with( $redirect_raw, '//' ) ) {
				$redirect_url = esc_url( home_url( $redirect_raw ) );
			} else {
				$redirect_url = esc_url( $redirect_raw );
			}
			$js_condition    = $show_if_logged ? '!v' : 'v';
			$js_url          = wp_json_encode( $redirect_url );
			$redirect_snippet = sprintf(
				' x-init="(function(){ var go = function(v){ if (%s){ window.location.href = %s; } }; go($store.bm.authenticated); $watch(\'$store.bm.authenticated\', go); })()"',
				$js_condition,
				$js_url
			);
		}

		return sprintf(
			'<div class="bm-ui bm-ui--session-guard"%s x-cloak x-show="%s">%s</div>',
			$redirect_snippet,
			esc_attr($condition),
			do_shortcode($content)
		);
	}

	/**
	 * @param array<string,mixed> $atts
	 */
	public function render_panel_element(string $type, array $atts = []): string {
		$this->render_init();

		switch ($type) {
			case 'login':
				return $this->panel_login($atts);
			case 'camp_header':
				return $this->panel_camp_header();
			case 'logout':
				return $this->panel_logout();
			case 'announcements':
				return $this->panel_announcements();
			case 'announcement_form':
				return $this->panel_announcement_form();
			case 'reports':
				return $this->panel_reports();
			case 'weather':
				return $this->panel_weather();
			case 'schedule':
				return $this->panel_schedule();
			case 'reservations':
				return $this->panel_reservations();
			case 'menu_day':
				return $this->panel_menu_day();
			case 'menu_week':
				return $this->panel_menu_week();
			case 'conversations':
				return $this->panel_conversations();
			case 'conversation_new':
				return $this->panel_conversation_new();
			case 'conversation_thread':
				return $this->panel_conversation_thread();
			case 'help_list':
				return $this->panel_help_list();
			case 'help_article':
				return $this->panel_help_article();
			case 'forms_list':
				return $this->panel_forms_list();
			case 'form':
				return $this->panel_form();
			case 'submissions_list':
				return $this->panel_submissions_list();
			case 'submission':
				return $this->panel_submission();
			case 'unread_counter':
				return $this->panel_unread_counter();
			case 'folder_docs':
				return $this->panel_folder_docs();
			case 'camp_documents':
				return $this->panel_camp_documents();
			case 'damages':
				return $this->panel_damages();
			case 'declaration':
				return $this->panel_declaration();
			case 'decl_docs':
				return $this->panel_decl_docs();
			case 'equipment':
				return $this->panel_equipment();
			default:
				return sprintf(
					'<div class="bm-ui bm-ui--notice">%s</div>',
					esc_html__('Nieznany element shortcode panelu.', 'basemgmt')
				);
		}
	}

	private function panel_login( array $atts = [] ): string {
		$redirect_raw = isset( $atts['redirect_url'] ) ? trim( (string) $atts['redirect_url'] ) : '';
		$redirect_url = '';
		if ( $redirect_raw !== '' ) {
			// Root-relative paths (e.g. /oboz/panel) must be converted to absolute
			// URLs before passing to esc_url(), otherwise esc_url() may strip them.
			if ( str_starts_with( $redirect_raw, '/' ) && ! str_starts_with( $redirect_raw, '//' ) ) {
				$redirect_url = esc_url( home_url( $redirect_raw ) );
			} else {
				$redirect_url = esc_url( $redirect_raw );
			}
		}
		$redirect_attr = $redirect_url ? ' data-bm-redirect="' . esc_attr( $redirect_url ) . '"' : '';
		return $this->wrap_panel(
			'',
			'<div class="bm-ui bm-ui--card bm-ui--auth-card" x-data="bmLogin()" x-cloak x-show="!$store.bm.authenticated" x-init="init()" ' . $redirect_attr . '>'
				. '<div class="bm-ui__header"><h3>' . esc_html__('Panel kadry obozowej', 'basemgmt') . '</h3></div>'
				. '<div class="bm-ui__body">'
				. '<p class="bm-ui__intro">' . esc_html__('Wybierz obóz, członka kadry i wpisz 6-cyfrowy kod bezpieczeństwa, aby przejść do panelu.', 'basemgmt') . '</p>'
				. '<div class="bm-ui__stack">'
				. '<div class="bm-ui__field">'
				. '<label class="bm-ui__label">' . esc_html__('Obóz', 'basemgmt') . '</label>'
				. '<select class="bm-ui__input" x-model="campId" @change="loadStaff()"><option value="">—</option><template x-for="c in camps" :key="c.id"><option :value="c.id" x-text="c.name"></option></template></select>'
				. '<div class="bm-ui__hint">' . esc_html__('Najpierw wybierz aktywny obóz, dla którego chcesz się zalogować.', 'basemgmt') . '</div>'
				. '</div>'
				. '<div class="bm-ui__field" x-show="campId && staffList.length">'
				. '<label class="bm-ui__label">' . esc_html__('Kadra', 'basemgmt') . '</label>'
				. '<select class="bm-ui__input" x-model="staffId"><option value="">—</option><template x-for="s in staffList" :key="s.id"><option :value="s.id" x-text="s.display_name"></option></template></select>'
				. '<div class="bm-ui__hint">' . esc_html__('Pokażemy tylko osoby przypisane do wybranego obozu.', 'basemgmt') . '</div>'
				. '</div>'
				. '<div class="bm-ui__field" x-show="staffId">'
				. '<label class="bm-ui__label">' . esc_html__('Kod bezpieczeństwa', 'basemgmt') . '</label>'
				. '<input class="bm-ui__input" type="password" x-model="code" maxlength="6" inputmode="numeric" autocomplete="one-time-code" @keydown.enter="submit()">'
				. '<div class="bm-ui__hint">' . esc_html__('Kod ma dokładnie 6 cyfr.', 'basemgmt') . '</div>'
				. '</div>'
				. '</div>'
				. '<p class="bm-ui__error" x-show="error" x-text="error"></p>'
				. '<div class="bm-ui__actions"><button type="button" class="bm-ui__btn bm-ui__btn--login" @click="submit()" :disabled="loading || !campId || !staffId || !code" x-text="loading ? \'Logowanie…\' : \'Zaloguj się\'"></button></div>'
				. '<p class="bm-ui__muted bm-ui__auth-note">' . esc_html__('Po poprawnym logowaniu od razu zobaczysz panel kadry dla wybranego obozu.', 'basemgmt') . '</p>'
				. '</div></div>',
			'bm-ui--panel-login'
		);
	}

	private function panel_camp_header(): string {
		// ── Inline SVG icon helpers ──────────────────────────────────────────────
		$ico_user = '<svg class="bm-ui__camp-ico" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
		$ico_cal  = '<svg class="bm-ui__camp-ico" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
		$ico_warn = '<svg class="bm-ui__camp-status-ico" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
		$ico_ok   = '<svg class="bm-ui__camp-status-ico" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
		$ico_info = '<svg class="bm-ui__camp-status-ico" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

		$html  = '<article class="bm-ui bm-ui--camp-header" x-data="bmCamp()" x-cloak x-show="$store.bm.authenticated" x-init="init()" aria-label="' . esc_attr__('Informacje o pobycie', 'basemgmt') . '">';

		// Loading skeleton
		$html .= '<div class="bm-ui__camp-loading" x-show="!camp">';
		$html .=   '<span class="bm-ui__muted">' . esc_html__('Ładowanie danych obozu…', 'basemgmt') . '</span>';
		$html .= '</div>';

		// Main two-column body
		$html .= '<div class="bm-ui__camp-body" x-show="camp">';

		// ── Left: info ───────────────────────────────────────────────────────────
		$html .= '<div class="bm-ui__camp-info">';
		$html .=   '<p class="bm-ui__camp-eyebrow">' . esc_html__('Twój aktualny pobyt', 'basemgmt') . '</p>';
		$html .=   '<h2 class="bm-ui__camp-name" x-text="camp ? camp.name : \'\'"></h2>';
		$html .=   '<p class="bm-ui__camp-guest">' . $ico_user . '<span x-text="\'Miło cię widzieć, \' + $store.bm.displayName + \'!\'">' . esc_html__('Witaj!', 'basemgmt') . '</span></p>';
		$html .=   '<dl class="bm-ui__camp-dates">';
		$html .=     '<div class="bm-ui__camp-date-row">' . $ico_cal;
		$html .=       '<dt>' . esc_html__('Przyjazd', 'basemgmt') . '</dt>';
		$html .=       '<dd x-text="camp ? camp.start_date : \'\'"></dd>';
		$html .=     '</div>';
		$html .=     '<div class="bm-ui__camp-date-row">' . $ico_cal;
		$html .=       '<dt>' . esc_html__('Wyjazd', 'basemgmt') . '</dt>';
		$html .=       '<dd x-text="camp ? camp.end_date : \'\'"></dd>';
		$html .=     '</div>';
		$html .=     '<div class="bm-ui__camp-date-row bm-ui__camp-nights" x-show="nightsCount > 0">';
		$html .=       '<dt>' . esc_html__('Czas pobytu', 'basemgmt') . '</dt>';
		$html .=       '<dd><span x-text="nightsCount"></span> ' . esc_html__('nocy', 'basemgmt') . '</dd>';
		$html .=     '</div>';
		$html .=   '</dl>';
		$html .= '</div>';

		// ── Right: check-in status ───────────────────────────────────────────────
		$html .= '<div class="bm-ui__camp-checkin">';

		// Variant: required
		$html .= '<div class="bm-ui__camp-checkin-block bm-ui__camp-checkin-block--required" x-show="checkinStatus === \'required\'" role="alert">';
		$html .=   '<div class="bm-ui__camp-checkin-head">' . $ico_warn;
		$html .=     '<strong class="bm-ui__camp-checkin-label">' . esc_html__('Meldunek wymagany', 'basemgmt') . '</strong>';
		$html .=   '</div>';
		$html .=   '<p class="bm-ui__camp-checkin-desc">' . esc_html__('Uzupełnij dane meldunkowe przed rozpoczęciem pobytu.', 'basemgmt') . '</p>';
		$html .=   '<button type="button" class="bm-ui__btn bm-ui__camp-checkin-cta">' . esc_html__('Uzupełnij meldunek', 'basemgmt') . '</button>';
		$html .= '</div>';

		// Variant: completed
		$html .= '<div class="bm-ui__camp-checkin-block bm-ui__camp-checkin-block--completed" x-show="checkinStatus === \'completed\'">';
		$html .=   '<div class="bm-ui__camp-checkin-head">' . $ico_ok;
		$html .=     '<strong class="bm-ui__camp-checkin-label">' . esc_html__('Meldunek potwierdzony', 'basemgmt') . '</strong>';
		$html .=   '</div>';
		$html .=   '<p class="bm-ui__camp-checkin-desc">' . esc_html__('Dane meldunkowe zostały zapisane.', 'basemgmt') . '</p>';
		$html .=   '<button type="button" class="bm-ui__btn bm-ui__btn--ghost bm-ui__btn--small bm-ui__camp-checkin-cta">' . esc_html__('Zobacz dane meldunku', 'basemgmt') . '</button>';
		$html .= '</div>';

		// Variant: unavailable
		$html .= '<div class="bm-ui__camp-checkin-block bm-ui__camp-checkin-block--unavailable" x-show="checkinStatus === \'unavailable\'">';
		$html .=   '<div class="bm-ui__camp-checkin-head">' . $ico_info;
		$html .=     '<strong class="bm-ui__camp-checkin-label">' . esc_html__('Meldunek niedostępny', 'basemgmt') . '</strong>';
		$html .=   '</div>';
		$html .=   '<p class="bm-ui__camp-checkin-desc">' . esc_html__('Meldunek będzie dostępny po rozpoczęciu pobytu.', 'basemgmt') . '</p>';
		$html .= '</div>';

		$html .= '</div>'; // .bm-ui__camp-checkin
		$html .= '</div>'; // .bm-ui__camp-body

		// ── Stats strip ──────────────────────────────────────────────────────────
		$html .= '<div class="bm-ui__camp-stats" x-show="latestCount">';
		$html .=   '<div class="bm-ui__camp-stat"><span class="bm-ui__camp-stat-value" x-text="latestCount ? latestCount.participants : 0">–</span><span class="bm-ui__camp-stat-label">' . esc_html__('Uczestnicy', 'basemgmt') . '</span></div>';
		$html .=   '<div class="bm-ui__camp-stat"><span class="bm-ui__camp-stat-value" x-text="latestCount ? latestCount.staff : 0">–</span><span class="bm-ui__camp-stat-label">' . esc_html__('Kadra', 'basemgmt') . '</span></div>';
		$html .=   '<div class="bm-ui__camp-stat"><span class="bm-ui__camp-stat-value" x-text="latestCount ? latestCount.workers : 0">–</span><span class="bm-ui__camp-stat-label">' . esc_html__('Pracownicy', 'basemgmt') . '</span></div>';
		$html .=   '<div class="bm-ui__camp-stat"><span class="bm-ui__camp-stat-value" x-text="latestCount ? (latestCount.participants + latestCount.staff + latestCount.workers) : 0">–</span><span class="bm-ui__camp-stat-label">' . esc_html__('Łącznie', 'basemgmt') . '</span></div>';
		$html .= '</div>';

		$html .= '</article>';
		return $this->wrap_panel( '', $html, '' );
	}

	private function panel_logout(): string {
		return $this->wrap_panel( __('Sesja', 'basemgmt'), '<div class="bm-ui bm-ui--inline bm-ui--compact-card" x-data="bmLogout()" x-cloak x-show="$store.bm.authenticated">'
			. '<span class="bm-ui__muted" x-text="\'Zalogowany: \' + $store.bm.displayName"></span>'
			. '<button class="bm-ui__btn bm-ui__btn--small" @click="logout()">' . esc_html__('Wyloguj', 'basemgmt') . '</button>'
			. '</div>' );
	}

	private function panel_announcements(): string {
		return $this->wrap_panel( __('Aktualności', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmAnnouncements()" x-cloak x-show="$store.bm.authenticated">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Ogłoszenia', 'basemgmt') . '</h3><button class="bm-ui__btn bm-ui__btn--light bm-ui__btn--small" @click="refresh()">↻</button></div>'
			. '<div class="bm-ui__body">'
			. '<template x-for="ann in active" :key="ann.id"><div class="bm-ui__item"><strong x-text="ann.title"></strong><div x-html="ann.content"></div><small class="bm-ui__muted" x-text="ann.valid_until"></small></div></template>'
			. '<p class="bm-ui__muted" x-show="!active.length">' . esc_html__('Brak aktywnych ogłoszeń.', 'basemgmt') . '</p>'
			. '</div></div>' );
	}

	private function panel_announcement_form(): string {
		return $this->wrap_panel( __('Nowe ogłoszenie', 'basemgmt'), '<form class="bm-ui bm-ui--card bm-ui--form-card" x-data="bmAnnForm()" x-cloak x-show="$store.bm.authenticated" @submit.prevent="submit()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Nowe ogłoszenie', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<input class="bm-ui__input" type="text" x-model="title" placeholder="' . esc_attr__('Tytuł', 'basemgmt') . '">' 
			. '<textarea class="bm-ui__input" x-model="content" rows="4" placeholder="' . esc_attr__('Treść', 'basemgmt') . '"></textarea>'
			. '<div class="bm-ui__grid"><input class="bm-ui__input" type="date" x-model="valid_from"><input class="bm-ui__input" type="date" x-model="valid_until"></div>'
			. '<input class="bm-ui__input" type="url" x-model="attachment_url" placeholder="https://">'
			. '<p class="bm-ui__success" x-show="success" x-text="success"></p><p class="bm-ui__error" x-show="error" x-text="error"></p>'
			. '<button class="bm-ui__btn" type="submit" :disabled="loading" x-text="loading ? \'Wysyłanie…\' : \'Wyślij\'"></button>'
			. '</div></form>' );
	}

	private function panel_reports(): string {
		return $this->wrap_panel( __('Meldunek dzienny', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--form-card" x-data="bmReports()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Meldunek dzienny', 'basemgmt') . '</h3><span x-text="statusLabel"></span></div><div class="bm-ui__body">'
			. '<div class="bm-ui__grid"><input class="bm-ui__input" type="number" x-model.number="form.participants" min="0" placeholder="' . esc_attr__('Uczestnicy', 'basemgmt') . '"><input class="bm-ui__input" type="number" x-model.number="form.staff" min="0" placeholder="' . esc_attr__('Kadra', 'basemgmt') . '"><input class="bm-ui__input" type="number" x-model.number="form.workers" min="0" placeholder="' . esc_attr__('Pracownicy', 'basemgmt') . '"></div>'
			. '<textarea class="bm-ui__input" x-model="form.notes" rows="3" placeholder="' . esc_attr__('Uwagi', 'basemgmt') . '"></textarea>'
			. '<p class="bm-ui__muted">' . esc_html__('Łącznie:', 'basemgmt') . ' <strong x-text="total"></strong></p>'
			. '<p class="bm-ui__success" x-show="success" x-text="success"></p><p class="bm-ui__error" x-show="error" x-text="error"></p>'
			. '<div class="bm-ui__actions"><button type="button" class="bm-ui__btn bm-ui__btn--ghost" @click="saveDraft()" :disabled="loading || isSubmitted">' . esc_html__('Zapisz roboczo', 'basemgmt') . '</button><button type="button" class="bm-ui__btn" @click="submit()" :disabled="loading || isSubmitted">' . esc_html__('Wyślij', 'basemgmt') . '</button></div>'
			. '</div></div>' );
	}

	private function panel_weather(): string {
		return $this->wrap_panel( __('Pogoda', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--feature-card" x-data="bmWeather()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Pogoda i alerty', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<p class="bm-ui__muted" x-show="loading">' . esc_html__('Ładowanie…', 'basemgmt') . '</p>'
			. '<p class="bm-ui__error" x-show="error" x-text="error"></p>'
			. '<template x-if="current"><div><p><strong x-text="current.icon"></strong> <span x-text="current.label"></span></p><p><span x-text="current.temperature"></span>°C · 💨 <span x-text="current.windspeed"></span> km/h</p></div></template>'
			. '<template x-for="day in forecast" :key="day.date"><div class="bm-ui__item"><strong x-text="day.date"></strong><span x-text="day.icon + \' \' + day.label"></span></div></template>'
			. '<template x-for="alert in alerts" :key="alert.id"><div class="bm-ui__item" :class="alert.is_urgent ? \'bm-ui__item--urgent\' : \'\'"><strong x-text="alert.title"></strong><p x-text="alert.message"></p></div></template>'
			. '</div></div>' );
	}

	private function panel_schedule(): string {
		return $this->wrap_panel( __('Plan dnia', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmSchedule()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Plan dnia', 'basemgmt') . '</h3><input class="bm-ui__input bm-ui__input--small" type="date" x-model="selectedDate" @change="loadSchedule()"></div><div class="bm-ui__body">'
			. '<template x-for="plan in plans" :key="plan.id"><div class="bm-ui__item"><strong x-text="plan.title || \'Plan\'"></strong><template x-for="item in plan.items" :key="item.id"><div class="bm-ui__line"><span x-text="item.time_from"></span> <span x-text="item.title"></span></div></template></div></template>'
			. '<p class="bm-ui__muted" x-show="!loading && !plans.length">' . esc_html__('Brak planu na wybrany dzień.', 'basemgmt') . '</p>'
			. '</div></div>' );
	}

	private function panel_reservations(): string {
		return $this->wrap_panel( __('Rezerwacje', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--form-card" x-data="bmReservations()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Rezerwacje', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<template x-for="res in resources" :key="res.id"><div class="bm-ui__item"><strong x-text="res.name"></strong><button type="button" class="bm-ui__btn bm-ui__btn--small" @click="openForm(res)">' . esc_html__('Zarezerwuj', 'basemgmt') . '</button></div></template>'
			. '<template x-if="selectedResource"><div class="bm-ui__item"><p><strong x-text="selectedResource.name"></strong></p><input class="bm-ui__input" type="date" x-model="form.res_date" @change="loadSlots()"><div class="bm-ui__grid"><input class="bm-ui__input" type="time" x-model="form.start_time"><input class="bm-ui__input" type="time" x-model="form.end_time"></div><input class="bm-ui__input" type="text" x-model="form.purpose" placeholder="' . esc_attr__('Cel rezerwacji', 'basemgmt') . '"><p class="bm-ui__error" x-show="formError" x-text="formError"></p><button class="bm-ui__btn" type="button" @click="submitReservation()">' . esc_html__('Wyślij rezerwację', 'basemgmt') . '</button></div></template>'
			. '<template x-for="r in myReservations" :key="r.id"><div class="bm-ui__line"><span x-text="r.res_date + \' \' + r.start_time + \'-\' + r.end_time"></span><button type="button" class="bm-ui__btn bm-ui__btn--small bm-ui__btn--ghost" x-show="r.status === \'pending\'" @click="cancel(r.id)">' . esc_html__('Anuluj', 'basemgmt') . '</button></div></template>'
			. '</div></div>' );
	}

	private function panel_menu_day(): string {
		return $this->wrap_panel( __('Jadłospis', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmMenu()" x-cloak x-show="$store.bm.authenticated" x-init="init(); setViewMode(\'day\')">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Jadłospis dzienny', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<select class="bm-ui__input" x-model="selectedDate" @change="selectDate(selectedDate)"><template x-for="d in availableDates" :key="d"><option :value="d" x-text="d"></option></template></select>'
			. '<template x-if="day"><div class="bm-ui__item"><template x-for="item in day.items" :key="item.id"><div class="bm-ui__line"><span x-text="mealTypeLabel(item.meal_type)"></span><strong x-text="item.name"></strong></div></template></div></template>'
			. '<p class="bm-ui__error" x-show="error" x-text="error"></p></div></div>' );
	}

	private function panel_menu_week(): string {
		return $this->wrap_panel( __('Jadłospis', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmMenu()" x-cloak x-show="$store.bm.authenticated" x-init="init(); setViewMode(\'week\')">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Jadłospis tygodniowy', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<template x-for="dayItem in weekDays" :key="dayItem.date"><div class="bm-ui__item"><strong x-text="dayItem.date"></strong><template x-for="item in dayItem.items" :key="item.id"><div class="bm-ui__line"><span x-text="mealTypeLabel(item.meal_type)"></span><span x-text="item.name"></span></div></template></div></template>'
			. '<p class="bm-ui__error" x-show="error" x-text="error"></p></div></div>' );
	}

	private function panel_conversations(): string {
		return $this->wrap_panel( __('Wiadomości', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmConversations()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Wiadomości', 'basemgmt') . '</h3><button type="button" class="bm-ui__btn bm-ui__btn--small" @click="view=\'new\'">' . esc_html__('Nowy', 'basemgmt') . '</button></div><div class="bm-ui__body">'
			. '<template x-for="thread in threads" :key="thread.id"><button type="button" class="bm-ui__item bm-ui__item--button" @click="openThread(thread.id)"><strong x-text="thread.subject"></strong><small x-text="thread.unread_camp ? thread.unread_camp + \' nowe\' : \'\'"></small></button></template>'
			. '</div></div>' );
	}

	private function panel_conversation_new(): string {
		return $this->wrap_panel( __('Nowa wiadomość', 'basemgmt'), '<form class="bm-ui bm-ui--card bm-ui--form-card" x-data="bmConversations()" x-cloak x-show="$store.bm.authenticated" x-init="init(); view=\'new\'" @submit.prevent="createThread()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Nowy wątek', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<input class="bm-ui__input" type="text" x-model="form.subject" placeholder="' . esc_attr__('Temat', 'basemgmt') . '"><textarea class="bm-ui__input" rows="4" x-model="form.content" placeholder="' . esc_attr__('Treść', 'basemgmt') . '"></textarea><select class="bm-ui__input" x-model="form.priority"><option value="low">' . esc_html__('Niski', 'basemgmt') . '</option><option value="normal">' . esc_html__('Normalny', 'basemgmt') . '</option><option value="high">' . esc_html__('Wysoki', 'basemgmt') . '</option><option value="urgent">' . esc_html__('Pilny', 'basemgmt') . '</option></select>'
			. '<p class="bm-ui__success" x-show="success" x-text="success"></p><p class="bm-ui__error" x-show="error" x-text="error"></p>'
			. '<button class="bm-ui__btn" type="submit" :disabled="loading">' . esc_html__('Wyślij', 'basemgmt') . '</button></div></form>' );
	}

	private function panel_conversation_thread(): string {
		return $this->wrap_panel( __('Wątek wiadomości', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--detail-card" x-data="bmConversations()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Widok wątku', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<p class="bm-ui__muted" x-show="!currentThread">' . esc_html__('Wybierz wątek z listy wiadomości.', 'basemgmt') . '</p>'
			. '<template x-if="currentThread"><div><h4 x-text="currentThread.subject"></h4><template x-for="m in messages" :key="m.id"><div class="bm-ui__item"><strong x-text="m.author_type"></strong><div x-text="m.content"></div></div></template><textarea class="bm-ui__input" rows="3" x-model="replyContent"></textarea><button class="bm-ui__btn" type="button" @click="sendReply()">' . esc_html__('Odpowiedz', 'basemgmt') . '</button></div></template>'
			. '</div></div>' );
	}

	private function panel_help_list(): string {
		return $this->wrap_panel( __('Pomoc', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmHelp()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Baza pomocy', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<div class="bm-ui__grid"><input class="bm-ui__input" type="text" x-model="search" placeholder="' . esc_attr__('Szukaj…', 'basemgmt') . '"><button type="button" class="bm-ui__btn bm-ui__btn--small" @click="applyFilters()">' . esc_html__('Filtruj', 'basemgmt') . '</button></div>'
			. '<template x-for="article in articles" :key="article.id"><button type="button" class="bm-ui__item bm-ui__item--button" @click="openArticle(article.id)"><strong x-text="article.title"></strong></button></template>'
			. '</div></div>' );
	}

	private function panel_help_article(): string {
		return $this->wrap_panel( __('Artykuł pomocy', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--detail-card" x-data="bmHelp()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Artykuł pomocy', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<p class="bm-ui__muted" x-show="!current">' . esc_html__('Wybierz artykuł na liście pomocy.', 'basemgmt') . '</p>'
			. '<template x-if="current"><div><h4 x-text="current.title"></h4><div x-html="current.content"></div></div></template>'
			. '</div></div>' );
	}

	private function panel_forms_list(): string {
		return $this->wrap_panel( __('Formularze', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmForms()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Formularze', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<select class="bm-ui__input" x-model="filterCategory"><option value="">' . esc_html__('Wszystkie kategorie', 'basemgmt') . '</option><template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template></select>'
			. '<template x-for="formItem in filtered" :key="formItem.id"><button type="button" class="bm-ui__item bm-ui__item--button" @click="openForm(formItem.id)"><strong x-text="formItem.title"></strong></button></template>'
			. '</div></div>' );
	}

	private function panel_form(): string {
		return $this->wrap_panel( __('Wypełnianie formularza', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--form-card" x-data="bmForms()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Wypełnij formularz', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<p class="bm-ui__muted" x-show="!currentForm">' . esc_html__('Wybierz formularz z listy formularzy.', 'basemgmt') . '</p>'
			. '<template x-if="currentForm"><div><h4 x-text="currentForm.title"></h4><template x-for="f in fields" :key="f.id"><div><label class="bm-ui__label" x-text="f.label"></label><input class="bm-ui__input" type="text" x-model="formValues[f.field_key]"><small class="bm-ui__error" x-text="fieldError(f.field_key)"></small></div></template><p class="bm-ui__error" x-show="error" x-text="error"></p><button class="bm-ui__btn" type="button" @click="submit()" :disabled="submitting">' . esc_html__('Wyślij', 'basemgmt') . '</button><p class="bm-ui__success" x-show="submitted">' . esc_html__('Formularz wysłany.', 'basemgmt') . '</p></div></template>'
			. '</div></div>' );
	}

	private function panel_submissions_list(): string {
		return $this->wrap_panel( __('Moje zgłoszenia', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmSubmissions()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Moje zgłoszenia', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<select class="bm-ui__input" x-model="filterStatus" @change="applyFilter()"><option value="">' . esc_html__('Wszystkie statusy', 'basemgmt') . '</option><option value="new">' . esc_html__('Nowe', 'basemgmt') . '</option><option value="in_progress">' . esc_html__('W trakcie', 'basemgmt') . '</option><option value="waiting">' . esc_html__('Oczekuje', 'basemgmt') . '</option><option value="closed">' . esc_html__('Zamknięte', 'basemgmt') . '</option><option value="cancelled">' . esc_html__('Anulowane', 'basemgmt') . '</option></select>'
			. '<template x-for="s in submissions" :key="s.id"><button type="button" class="bm-ui__item bm-ui__item--button" @click="openSubmission(s.id)"><strong x-text="s.form_title || s.category"></strong><small x-text="statusLabel(s.status)"></small></button></template>'
			. '</div></div>' );
	}

	private function panel_submission(): string {
		return $this->wrap_panel( __('Szczegóły zgłoszenia', 'basemgmt'), '<div class="bm-ui bm-ui--card bm-ui--detail-card" x-data="bmSubmissions()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Szczegóły zgłoszenia', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<p class="bm-ui__muted" x-show="!current">' . esc_html__('Wybierz zgłoszenie z listy.', 'basemgmt') . '</p>'
			. '<template x-if="current"><div><p><strong>' . esc_html__('Status:', 'basemgmt') . '</strong> <span x-text="statusLabel(current.submission.status)"></span></p><p><strong>' . esc_html__('Priorytet:', 'basemgmt') . '</strong> <span x-text="priorityLabel(current.submission.priority)"></span></p><pre class="bm-ui__json" x-text="JSON.stringify(current.submission_data, null, 2)"></pre></div></template>'
			. '</div></div>' );
	}

	private function panel_unread_counter(): string {
		return '<div class="bm-ui bm-ui--badge" x-data="{}" x-cloak x-show="($store.bm && $store.bm.authenticated)"><span x-text="$store.bm ? $store.bm.unreadCount : 0"></span></div>';
	}

	private function panel_folder_docs(): string {
		return $this->wrap_panel(
			__('Biblioteka dokumentów', 'basemgmt'),
			'<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmFolderDocs()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
				. '<div class="bm-ui__header"><h3>' . esc_html__('Biblioteka dokumentów', 'basemgmt') . '</h3><button class="bm-ui__btn bm-ui__btn--light bm-ui__btn--small" @click="init()">↻</button></div>'
				. '<div class="bm-ui__body">'
				. '<p class="bm-ui__muted" x-show="loading">' . esc_html__('Ładowanie…', 'basemgmt') . '</p>'
				. '<p class="bm-ui__error" x-show="error" x-text="error"></p>'
				. '<template x-for="doc in documents" :key="doc.id">'
				.   '<div class="bm-ui__item">'
				.     '<span x-text="docIcon(doc.document_type)" aria-hidden="true"></span>'
				.     '<div>'
				.       '<strong x-text="doc.title"></strong>'
				.       '<p class="bm-ui__muted" x-show="doc.description" x-text="doc.description"></p>'
				.     '</div>'
				.     '<a class="bm-ui__btn bm-ui__btn--ghost bm-ui__btn--small" :href="doc.file_url" target="_blank" rel="noopener" x-show="doc.file_url">' . esc_html__('Pobierz', 'basemgmt') . '</a>'
				.   '</div>'
				. '</template>'
				. '<p class="bm-ui__muted" x-show="!loading && !documents.length && !error">' . esc_html__('Brak dokumentów w bibliotece.', 'basemgmt') . '</p>'
				. '</div></div>'
		);
	}

	private function panel_camp_documents(): string {
		return $this->wrap_panel(
			__('Dokumenty obozu', 'basemgmt'),
			'<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmCampDocuments()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
				. '<div class="bm-ui__header"><h3>' . esc_html__('Dokumenty obozu', 'basemgmt') . '</h3><button class="bm-ui__btn bm-ui__btn--light bm-ui__btn--small" @click="init()">↻</button></div>'
				. '<div class="bm-ui__body">'
				. '<p class="bm-ui__muted" x-show="loading">' . esc_html__('Ładowanie…', 'basemgmt') . '</p>'
				. '<p class="bm-ui__error" x-show="error" x-text="error"></p>'
				. '<template x-for="doc in documents" :key="doc.id">'
				.   '<div class="bm-ui__item">'
				.     '<span x-text="docIcon(doc.document_type)" aria-hidden="true"></span>'
				.     '<div class="bm-ui__item-main">'
				.       '<strong x-text="doc.title"></strong>'
				.       '<span class="bm-ui__badge" :class="statusClass(doc.status)" x-text="statusLabel(doc.status)"></span>'
				.     '</div>'
				.     '<a class="bm-ui__btn bm-ui__btn--ghost bm-ui__btn--small" :href="doc.file_url" target="_blank" rel="noopener" x-show="doc.file_url">' . esc_html__('Pobierz', 'basemgmt') . '</a>'
				.   '</div>'
				. '</template>'
				. '<p class="bm-ui__muted" x-show="!loading && !documents.length && !error">' . esc_html__('Brak dokumentów przypisanych do obozu.', 'basemgmt') . '</p>'
				. '</div></div>'
		);
	}

	private function panel_damages(): string {
		return $this->wrap_panel(
			__('Szkody', 'basemgmt'),
			'<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmDamages()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
				. '<div class="bm-ui__header"><h3>' . esc_html__('Szkody', 'basemgmt') . '</h3><button class="bm-ui__btn bm-ui__btn--small" @click="openForm()" x-show="!showForm">+ ' . esc_html__('Zgłoś szkodę', 'basemgmt') . '</button></div>'
				. '<div class="bm-ui__body">'
				. '<p class="bm-ui__success" x-show="success" x-text="success"></p>'
				. '<p class="bm-ui__error"   x-show="error"   x-text="error"></p>'
				// Form
				. '<div x-show="showForm" class="bm-ui__stack">'
				.   '<input class="bm-ui__input" type="text" x-model="form.name" placeholder="' . esc_attr__('Nazwa szkody', 'basemgmt') . '">'
				.   '<textarea class="bm-ui__input" rows="3" x-model="form.description" placeholder="' . esc_attr__('Opis (opcjonalnie)', 'basemgmt') . '"></textarea>'
				.   '<input class="bm-ui__input" type="number" min="0" step="0.01" x-model="form.cost" placeholder="' . esc_attr__('Szacowany koszt (PLN)', 'basemgmt') . '">'
				.   '<div class="bm-ui__actions">'
				.     '<button class="bm-ui__btn" type="button" @click="submit()" :disabled="saving" x-text="saving ? \'Wysyłanie\u2026\' : \'Zgłoś\'"></button>'
				.     '<button class="bm-ui__btn bm-ui__btn--ghost" type="button" @click="showForm = false">' . esc_html__('Anuluj', 'basemgmt') . '</button>'
				.   '</div>'
				. '</div>'
				// List
				. '<p class="bm-ui__muted" x-show="loading">' . esc_html__('Ładowanie…', 'basemgmt') . '</p>'
				. '<template x-for="dmg in damages" :key="dmg.id">'
				.   '<div class="bm-ui__item">'
				.     '<div class="bm-ui__item-main">'
				.       '<strong x-text="dmg.name"></strong>'
				.       '<span class="bm-ui__badge" :class="statusClass(dmg.status)" x-text="statusLabel(dmg.status)"></span>'
				.     '</div>'
				.     '<p class="bm-ui__muted" x-show="dmg.description" x-text="dmg.description"></p>'
				.     '<small class="bm-ui__muted" x-show="dmg.cost > 0" x-text="\'Koszt: \' + dmg.cost + \' PLN\'"></small>'
				.   '</div>'
				. '</template>'
				. '<p class="bm-ui__muted" x-show="!loading && !damages.length">' . esc_html__('Brak zgłoszonych szkód.', 'basemgmt') . '</p>'
				. '</div></div>'
		);
	}

	private function panel_declaration(): string {
		return $this->wrap_panel(
			__('Deklaracja obozu', 'basemgmt'),
			'<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmDeclaration()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
				. '<div class="bm-ui__header"><h3>' . esc_html__('Deklaracja obozu', 'basemgmt') . '</h3><button class="bm-ui__btn bm-ui__btn--small" @click="newDay()" x-show="!editing">+ ' . esc_html__('Dodaj dzień', 'basemgmt') . '</button></div>'
				. '<div class="bm-ui__body">'
				. '<p class="bm-ui__error" x-show="error" x-text="error"></p>'
				. '<p class="bm-ui__muted" x-show="loading">' . esc_html__('Ładowanie…', 'basemgmt') . '</p>'
				// Edit form
				. '<div x-show="editing" class="bm-ui__stack">'
				.   '<input class="bm-ui__input" type="date" x-model="editing.declaration_date">'
				.   '<input class="bm-ui__input" type="number" min="0" x-model.number="editing.declared_persons" placeholder="' . esc_attr__('Liczba osób', 'basemgmt') . '">'
				.   '<input class="bm-ui__input" type="time" x-model="editing.arrival_time" placeholder="' . esc_attr__('Godzina przyjazdu', 'basemgmt') . '">'
				.   '<input class="bm-ui__input" type="time" x-model="editing.departure_time" placeholder="' . esc_attr__('Godzina wyjazdu', 'basemgmt') . '">'
				.   '<div class="bm-ui__actions">'
				.     '<button class="bm-ui__btn" type="button" @click="saveDay()" :disabled="saving" x-text="saving ? \'Zapisywanie\u2026\' : \'Zapisz\'"></button>'
				.   '</div>'
				. '</div>'
				// Days list
				. '<template x-for="day in days" :key="day.declaration_date">'
				.   '<div class="bm-ui__item">'
				.     '<div class="bm-ui__item-main">'
				.       '<strong x-text="fmtDate(day.declaration_date)"></strong>'
				.       '<span class="bm-ui__muted" x-text="day.declared_persons + \' os.\'"></span>'
				.     '</div>'
				.     '<small class="bm-ui__muted" x-show="day.arrival_time || day.departure_time" x-text="(day.arrival_time || \'—\') + \' – \' + (day.departure_time || \'—\')"></small>'
				.     '<button class="bm-ui__btn bm-ui__btn--ghost bm-ui__btn--small" @click="editDay(day)">' . esc_html__('Edytuj', 'basemgmt') . '</button>'
				.   '</div>'
				. '</template>'
				. '<p class="bm-ui__muted" x-show="!loading && !days.length">' . esc_html__('Brak wpisów w deklaracji.', 'basemgmt') . '</p>'
				. '</div></div>'
		);
	}

	private function panel_decl_docs(): string {
		return $this->wrap_panel(
			__('Dokumenty deklaracji', 'basemgmt'),
			'<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmDeclDocs()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
				. '<div class="bm-ui__header"><h3>' . esc_html__('Dokumenty deklaracji', 'basemgmt') . '</h3><button class="bm-ui__btn bm-ui__btn--light bm-ui__btn--small" @click="load()">↻</button></div>'
				. '<div class="bm-ui__body">'
				. '<p class="bm-ui__success" x-show="success" x-text="success"></p>'
				. '<p class="bm-ui__error"   x-show="error"   x-text="error"></p>'
				. '<p class="bm-ui__muted" x-show="loading">' . esc_html__('Ładowanie…', 'basemgmt') . '</p>'
				. '<template x-for="doc in docs" :key="doc.id">'
				.   '<div class="bm-ui__item">'
				.     '<div class="bm-ui__item-main">'
				.       '<strong x-text="doc.title || doc.document_type"></strong>'
				.       '<span class="bm-ui__badge" :class="statusClass(doc)" x-text="statusLabel(doc)"></span>'
				.     '</div>'
				.     '<div class="bm-ui__actions">'
				.       '<a class="bm-ui__btn bm-ui__btn--ghost bm-ui__btn--small" :href="doc.file_url" target="_blank" rel="noopener" x-show="doc.file_url">' . esc_html__('Pobierz', 'basemgmt') . '</a>'
				.       '<button class="bm-ui__btn bm-ui__btn--small" @click="approve(doc.id)" :disabled="approving === doc.id" x-show="!doc.camp_approved_at" x-text="approving === doc.id ? \'Zatwierdzanie\u2026\' : \'Zatwierdź\'"></button>'
				.   '</div>'
				. '</template>'
				. '<p class="bm-ui__muted" x-show="!loading && !docs.length && !error">' . esc_html__('Brak dokumentów deklaracji.', 'basemgmt') . '</p>'
				. '</div></div>'
		);
	}

	private function panel_equipment(): string {
		return $this->wrap_panel(
			__('Sprzęt', 'basemgmt'),
			'<div class="bm-ui bm-ui--card bm-ui--list-card" x-data="bmEquipment()" x-cloak x-show="$store.bm.authenticated" x-init="init()">'
				. '<div class="bm-ui__header"><h3>' . esc_html__('Sprzęt', 'basemgmt') . '</h3><button class="bm-ui__btn bm-ui__btn--small" @click="openForm()" x-show="!showForm">+ ' . esc_html__('Dodaj sprzęt', 'basemgmt') . '</button></div>'
				. '<div class="bm-ui__body">'
				. '<p class="bm-ui__success" x-show="success" x-text="success"></p>'
				. '<p class="bm-ui__error"   x-show="error"   x-text="error"></p>'
				// Form
				. '<div x-show="showForm" class="bm-ui__stack">'
				.   '<input class="bm-ui__input" type="text" x-model="form.name" placeholder="' . esc_attr__('Nazwa sprzętu', 'basemgmt') . '">'
				.   '<input class="bm-ui__input" type="text" x-model="form.equipment_type" placeholder="' . esc_attr__('Typ / kategoria', 'basemgmt') . '">'
				.   '<input class="bm-ui__input" type="number" min="1" x-model.number="form.issued_qty" placeholder="' . esc_attr__('Ilość', 'basemgmt') . '">'
				.   '<textarea class="bm-ui__input" rows="2" x-model="form.notes" placeholder="' . esc_attr__('Uwagi (opcjonalnie)', 'basemgmt') . '"></textarea>'
				.   '<div class="bm-ui__actions">'
				.     '<button class="bm-ui__btn" type="button" @click="submit()" :disabled="saving" x-text="saving ? \'Zapisywanie\u2026\' : \'Zapisz\'"></button>'
				.     '<button class="bm-ui__btn bm-ui__btn--ghost" type="button" @click="showForm = false">' . esc_html__('Anuluj', 'basemgmt') . '</button>'
				.   '</div>'
				. '</div>'
				// List
				. '<p class="bm-ui__muted" x-show="loading">' . esc_html__('Ładowanie…', 'basemgmt') . '</p>'
				. '<template x-for="item in items" :key="item.id">'
				.   '<div class="bm-ui__item">'
				.     '<div class="bm-ui__item-main">'
				.       '<strong x-text="item.name"></strong>'
				.       '<span class="bm-ui__muted" x-show="item.equipment_type" x-text="item.equipment_type"></span>'
				.     '</div>'
				.     '<button class="bm-ui__btn bm-ui__btn--ghost bm-ui__btn--small" @click="registerReturn(item.id)" x-show="item.issued_qty > (item.returned_qty ?? 0)">' . esc_html__('Zwrot', 'basemgmt') . '</button>'
				.     '<small class="bm-ui__muted" x-text="\'Wydano: \' + item.issued_qty + \' szt.\'"></small>'
				. '</template>'
				. '<p class="bm-ui__muted" x-show="!loading && !items.length">' . esc_html__('Brak zarejestrowanego sprzętu.', 'basemgmt') . '</p>'
				. '</div></div>'
		);
	}

	/**
	 * Wraps shortcode content in the shared outer panel container.
	 * Pass an empty section title to suppress the title row entirely.
	 */
	private function wrap_panel( string $section_title, string $content, string $extra_classes = '' ): string {
		// Section titles are only rendered for authenticated-only panels.
		$title_markup = $section_title !== ''
			? '<div class="bm-ui__section-title" x-data="{}" x-cloak x-show="($store.bm && $store.bm.authenticated)">' . esc_html( $section_title ) . '</div>'
			: '';

		$classes = trim( 'bm-ui bm-ui--panel ' . $extra_classes );

		return '<div class="' . esc_attr( $classes ) . '">' . $title_markup . $content . '</div>';
	}


	/**
	 * [bm_var var="name"] – outputs a single plain-text variable from the current session.
	 *
	 * Supported values for `var`:
	 *   Staff:  first_name, last_name, full_name, role_in_camp, phone
	 *   Camp:   camp_name, camp_start, camp_end, camp_status
	 *   Date:   today, today_long, year, time
	 *
	 * @param array<string,string>|string $atts
	 */
	public function render_var( $atts = [] ): string {
		$atts    = shortcode_atts( [ 'var' => '', 'fallback' => '' ], is_array($atts) ? $atts : [] );
		$var     = sanitize_key( $atts['var'] );
		$session = SessionManager::current();

		$staff = null;
		if ( $session && $session->staff_id ) {
			$staff = StaffRepository::get( (int) $session->staff_id );
		}

		$camp = null;
		if ( $session && $session->camp_id ) {
			$camp = CampRepository::get( (int) $session->camp_id );
		}

		// Pre-compute camp date helpers (avoid repetition in match).
		$today_ts  = strtotime( wp_date( 'Y-m-d' ) );
		$start_ts  = $camp ? strtotime( (string) $camp->start_date ) : 0;
		$end_ts    = $camp ? strtotime( (string) $camp->end_date )   : 0;
		$nights    = ( $start_ts && $end_ts ) ? max( 0, (int) round( ( $end_ts - $start_ts ) / DAY_IN_SECONDS ) ) : 0;
		$camp_day  = ( $start_ts && $today_ts >= $start_ts )
			? min( $nights, (int) floor( ( $today_ts - $start_ts ) / DAY_IN_SECONDS ) + 1 )
			: 0;
		$days_left = ( $end_ts && $today_ts <= $end_ts )
			? (int) ceil( ( $end_ts - $today_ts ) / DAY_IN_SECONDS )
			: 0;

		$value = match ( $var ) {
			// ── Kadra ─────────────────────────────────────────────────────────
			'first_name'      => $staff ? (string) $staff->first_name   : '',
			'last_name'       => $staff ? (string) $staff->last_name    : '',
			'full_name'       => $staff ? trim( $staff->first_name . ' ' . $staff->last_name ) : '',
			'role_in_camp'    => $staff ? (string) $staff->role_in_camp : '',
			'phone'           => $staff ? (string) $staff->phone        : '',
			'email'           => $staff ? (string) $staff->email        : '',
			// ── Obóz ──────────────────────────────────────────────────────────
			'camp_name'       => $camp  ? (string) $camp->name         : '',
			'camp_start'      => $camp  ? $this->format_date( (string) $camp->start_date ) : '',
			'camp_end'        => $camp  ? $this->format_date( (string) $camp->end_date )   : '',
			'camp_start_raw'  => $camp  ? wp_date( 'd.m.Y', $start_ts ) : '',
			'camp_end_raw'    => $camp  ? wp_date( 'd.m.Y', $end_ts   ) : '',
			'camp_status'     => $camp  ? (string) $camp->status       : '',
			'camp_nights'     => $nights > 0 ? (string) $nights        : '',
			'camp_day'        => $camp_day  > 0 ? (string) $camp_day   : '',
			'camp_days_left'  => $days_left > 0 ? (string) $days_left  : '',
			'camp_progress'   => ( $nights > 0 && $camp_day > 0 )
				? (string) min( 100, (int) round( $camp_day / $nights * 100 ) )
				: '',
			// ── Data / czas ───────────────────────────────────────────────────
			'today'           => wp_date( 'd.m.Y' ),
			'today_raw'       => wp_date( 'Y-m-d' ),
			'today_long'      => $this->format_date( wp_date( 'Y-m-d' ) ),
			'today_full'      => $this->format_date_full( wp_date( 'Y-m-d' ) ),
			'day_of_week'     => $this->day_of_week( wp_date( 'N' ) ),
			'month'           => $this->month_name( (int) wp_date( 'n' ) ),
			'year'            => wp_date( 'Y' ),
			'time'            => wp_date( 'H:i' ),
			// ── Sesja ─────────────────────────────────────────────────────────
			'session_expires' => $session
				? (string) max( 0, (int) ceil( ( strtotime( $session->expires_at ) - time() ) / 60 ) )
				: '',
			'logged_in'       => $session ? '1' : '0',
			default           => '',
		};

		if ( $value === '' ) {
			$value = sanitize_text_field( $atts['fallback'] );
		}

		return esc_html( $value );
	}

	/** Formats a Y-m-d date string to "17 sierpnia 2026". */
	private function format_date( string $date ): string {
		if ( $date === '' ) {
			return '';
		}
		$ts = strtotime( $date );
		if ( ! $ts ) {
			return $date;
		}
		return (int) date( 'j', $ts ) . ' ' . $this->month_name( (int) date( 'n', $ts ) ) . ' ' . date( 'Y', $ts );
	}

	/** Formats a Y-m-d date string to "niedziela, 17 sierpnia 2026". */
	private function format_date_full( string $date ): string {
		if ( $date === '' ) {
			return '';
		}
		$ts = strtotime( $date );
		if ( ! $ts ) {
			return $date;
		}
		return $this->day_of_week( (int) date( 'N', $ts ) ) . ', ' . $this->format_date( $date );
	}

	/** Returns Polish month name in genitive (for use after a number). */
	private function month_name( int $month ): string {
		return [
			1 => 'stycznia', 2 => 'lutego', 3 => 'marca', 4 => 'kwietnia',
			5 => 'maja', 6 => 'czerwca', 7 => 'lipca', 8 => 'sierpnia',
			9 => 'września', 10 => 'października', 11 => 'listopada', 12 => 'grudnia',
		][ $month ] ?? '';
	}

	/** Returns Polish day of week name (ISO day 1=Monday … 7=Sunday). */
	private function day_of_week( int $iso_day ): string {
		return [
			1 => 'poniedziałek', 2 => 'wtorek', 3 => 'środa', 4 => 'czwartek',
			5 => 'piątek', 6 => 'sobota', 7 => 'niedziela',
		][ $iso_day ] ?? '';
	}


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
			'wpNonce'       => wp_create_nonce('wp_rest'),
			'loginNonce'    => wp_create_nonce('bm_login'),
			'panelNonce'    => wp_create_nonce('bm_panel'),
			'authenticated' => (bool) $session,
			'campId'        => $camp_id,
			'staffId'       => $staff_id,
			'displayName'   => $display_name,
			// SEC-05: Zaokrąglamy do pełnych minut, aby zachować spójność z AuthController::status()
			// i nie ujawniać dokładnego czasu wygaśnięcia sesji frontendowi JS.
			'sessionExpires'=> $session ? max( 0, (int) ceil( ( strtotime( $session->expires_at ) - time() ) / 60 ) * 60 ) : null,
			'activeCamps'   => FrontendAuth::get_active_camps(),
		];
	}
}
