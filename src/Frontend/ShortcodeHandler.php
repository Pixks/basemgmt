<?php

declare(strict_types=1);

namespace BaseMgmt\Frontend;

use BaseMgmt\Auth\FrontendAuth;
use BaseMgmt\Auth\SessionManager;
use BaseMgmt\Modules\Camps\StaffRepository;

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
			$redirect_url    = esc_url( (string) $atts['redirect'] );
			$js_condition    = $show_if_logged ? 'v' : '!v';
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
			default:
				return sprintf(
					'<div class="bm-ui bm-ui--notice">%s</div>',
					esc_html__('Nieznany element shortcode panelu.', 'basemgmt')
				);
		}
	}

	private function panel_login( array $atts = [] ): string {
		$redirect_url = isset( $atts['redirect_url'] ) ? esc_url( (string) $atts['redirect_url'] ) : '';
		$redirect_attr = $redirect_url ? ' data-bm-redirect="' . esc_attr( $redirect_url ) . '"' : '';
		return $this->wrap_panel(
			'',
			'<div class="bm-ui bm-ui--card bm-ui--auth-card" x-data="bmLogin()" x-init="init()" ' . $redirect_attr . '>'
				. '<div class="bm-ui__header"><h3>' . esc_html__('Panel kadry obozowej', 'basemgmt') . '</h3></div>'
				. '<div class="bm-ui__body">'
				. '<div class="bm-ui__stack">'
				. '<div class="bm-ui__field">'
				. '<label class="bm-ui__label">' . esc_html__('Obóz', 'basemgmt') . '</label>'
				. '<select class="bm-ui__input" x-model="campId" @change="loadStaff()"><option value="">—</option><template x-for="c in camps" :key="c.id"><option :value="c.id" x-text="c.name"></option></template></select>'
				. '</div>'
				. '<div class="bm-ui__field" x-show="campId && staffList.length">'
				. '<label class="bm-ui__label">' . esc_html__('Kadra', 'basemgmt') . '</label>'
				. '<select class="bm-ui__input" x-model="staffId"><option value="">—</option><template x-for="s in staffList" :key="s.id"><option :value="s.id" x-text="s.display_name"></option></template></select>'
				. '</div>'
				. '<div class="bm-ui__field" x-show="staffId">'
				. '<label class="bm-ui__label">' . esc_html__('Kod bezpieczeństwa', 'basemgmt') . '</label>'
				. '<input class="bm-ui__input" type="password" x-model="code" maxlength="6" inputmode="numeric" @keydown.enter="submit()">'
				. '</div>'
				. '</div>'
				. '<p class="bm-ui__error" x-show="error" x-text="error"></p>'
				. '<div class="bm-ui__actions"><button type="button" class="bm-ui__btn bm-ui__btn--login" @click="submit()" :disabled="loading || !campId || !staffId || !code" x-text="loading ? \'Logowanie…\' : \'Zaloguj\'"></button></div>'
				. '</div></div>',
			false,
			'bm-ui--panel-login'
		);
	}

	private function panel_camp_header(): string {
		return $this->wrap_panel(
			__('Przegląd obozu', 'basemgmt'),
			'<div class="bm-ui bm-ui--card" x-data="bmCamp()" x-init="init()" >'
				. '<div class="bm-ui__header"><h3 x-text="camp ? camp.name : \'Obóz\'"></h3></div>'
				. '<div class="bm-ui__body">'
				. '<p class="bm-ui__muted" x-show="!camp">' . esc_html__('Ładowanie danych obozu…', 'basemgmt') . '</p>'
				. '<template x-if="camp"><div><p><span x-text="camp.start_date"></span> – <span x-text="camp.end_date"></span></p>'
				. '<p x-show="submittedToday" class="bm-ui__success">✓ ' . esc_html__('Meldunek złożony', 'basemgmt') . '</p>'
				. '<p x-show="!submittedToday" class="bm-ui__warn">⚠ ' . esc_html__('Brak meldunku', 'basemgmt') . '</p>'
				. '<div class="bm-ui__stats" x-show="latestCount"><span><strong x-text="latestCount.participants ?? 0"></strong> ' . esc_html__('Uczestnicy', 'basemgmt') . '</span><span><strong x-text="latestCount.staff ?? 0"></strong> ' . esc_html__('Kadra', 'basemgmt') . '</span><span><strong x-text="latestCount.workers ?? 0"></strong> ' . esc_html__('Pracownicy', 'basemgmt') . '</span></div></div></template>'
				. '</div></div>',
			true
		);
	}

	private function panel_logout(): string {
		return $this->wrap_panel( __('Sesja', 'basemgmt'), '<div class="bm-ui bm-ui--inline" x-data="bmLogout()" >'
			. '<span class="bm-ui__muted" x-text="\'Zalogowany: \' + $store.bm.displayName"></span>'
			. '<button class="bm-ui__btn bm-ui__btn--small" @click="logout()">' . esc_html__('Wyloguj', 'basemgmt') . '</button>'
			. '</div>', true );
	}

	private function panel_announcements(): string {
		return $this->wrap_panel( __('Aktualności', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmAnnouncements()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Ogłoszenia', 'basemgmt') . '</h3><button class="bm-ui__btn bm-ui__btn--light bm-ui__btn--small" @click="refresh()">↻</button></div>'
			. '<div class="bm-ui__body">'
			. '<template x-for="ann in active" :key="ann.id"><div class="bm-ui__item"><strong x-text="ann.title"></strong><div x-html="ann.content"></div><small class="bm-ui__muted" x-text="ann.valid_until"></small></div></template>'
			. '<p class="bm-ui__muted" x-show="!active.length">' . esc_html__('Brak aktywnych ogłoszeń.', 'basemgmt') . '</p>'
			. '</div></div>', true );
	}

	private function panel_announcement_form(): string {
		return $this->wrap_panel( __('Nowe ogłoszenie', 'basemgmt'), '<form class="bm-ui bm-ui--card" x-data="bmAnnForm()" @submit.prevent="submit()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Nowe ogłoszenie', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<input class="bm-ui__input" type="text" x-model="title" placeholder="' . esc_attr__('Tytuł', 'basemgmt') . '">' 
			. '<textarea class="bm-ui__input" x-model="content" rows="4" placeholder="' . esc_attr__('Treść', 'basemgmt') . '"></textarea>'
			. '<div class="bm-ui__grid"><input class="bm-ui__input" type="date" x-model="valid_from"><input class="bm-ui__input" type="date" x-model="valid_until"></div>'
			. '<input class="bm-ui__input" type="url" x-model="attachment_url" placeholder="https://">'
			. '<p class="bm-ui__success" x-show="success" x-text="success"></p><p class="bm-ui__error" x-show="error" x-text="error"></p>'
			. '<button class="bm-ui__btn" type="submit" :disabled="loading" x-text="loading ? \'Wysyłanie…\' : \'Wyślij\'"></button>'
			. '</div></form>', true );
	}

	private function panel_reports(): string {
		return $this->wrap_panel( __('Meldunek dzienny', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmReports()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Meldunek dzienny', 'basemgmt') . '</h3><span x-text="statusLabel"></span></div><div class="bm-ui__body">'
			. '<div class="bm-ui__grid"><input class="bm-ui__input" type="number" x-model.number="form.participants" min="0" placeholder="' . esc_attr__('Uczestnicy', 'basemgmt') . '"><input class="bm-ui__input" type="number" x-model.number="form.staff" min="0" placeholder="' . esc_attr__('Kadra', 'basemgmt') . '"><input class="bm-ui__input" type="number" x-model.number="form.workers" min="0" placeholder="' . esc_attr__('Pracownicy', 'basemgmt') . '"></div>'
			. '<textarea class="bm-ui__input" x-model="form.notes" rows="3" placeholder="' . esc_attr__('Uwagi', 'basemgmt') . '"></textarea>'
			. '<p class="bm-ui__muted">' . esc_html__('Łącznie:', 'basemgmt') . ' <strong x-text="total"></strong></p>'
			. '<p class="bm-ui__success" x-show="success" x-text="success"></p><p class="bm-ui__error" x-show="error" x-text="error"></p>'
			. '<div class="bm-ui__actions"><button type="button" class="bm-ui__btn bm-ui__btn--ghost" @click="saveDraft()" :disabled="loading || isSubmitted">' . esc_html__('Zapisz roboczo', 'basemgmt') . '</button><button type="button" class="bm-ui__btn" @click="submit()" :disabled="loading || isSubmitted">' . esc_html__('Wyślij', 'basemgmt') . '</button></div>'
			. '</div></div>', true );
	}

	private function panel_weather(): string {
		return $this->wrap_panel( __('Pogoda', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmWeather()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Pogoda i alerty', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<p class="bm-ui__muted" x-show="loading">' . esc_html__('Ładowanie…', 'basemgmt') . '</p>'
			. '<p class="bm-ui__error" x-show="error" x-text="error"></p>'
			. '<template x-if="current"><div><p><strong x-text="current.icon"></strong> <span x-text="current.label"></span></p><p><span x-text="current.temperature"></span>°C · 💨 <span x-text="current.windspeed"></span> km/h</p></div></template>'
			. '<template x-for="day in forecast" :key="day.date"><div class="bm-ui__item"><strong x-text="day.date"></strong><span x-text="day.icon + \' \' + day.label"></span></div></template>'
			. '<template x-for="alert in alerts" :key="alert.id"><div class="bm-ui__item" :class="alert.is_urgent ? \'bm-ui__item--urgent\' : \'\'"><strong x-text="alert.title"></strong><p x-text="alert.message"></p></div></template>'
			. '</div></div>', true );
	}

	private function panel_schedule(): string {
		return $this->wrap_panel( __('Plan dnia', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmSchedule()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Plan dnia', 'basemgmt') . '</h3><input class="bm-ui__input bm-ui__input--small" type="date" x-model="selectedDate" @change="loadSchedule()"></div><div class="bm-ui__body">'
			. '<template x-for="plan in plans" :key="plan.id"><div class="bm-ui__item"><strong x-text="plan.title || \'Plan\'"></strong><template x-for="item in plan.items" :key="item.id"><div class="bm-ui__line"><span x-text="item.time_from"></span> <span x-text="item.title"></span></div></template></div></template>'
			. '<p class="bm-ui__muted" x-show="!loading && !plans.length">' . esc_html__('Brak planu na wybrany dzień.', 'basemgmt') . '</p>'
			. '</div></div>', true );
	}

	private function panel_reservations(): string {
		return $this->wrap_panel( __('Rezerwacje', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmReservations()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Rezerwacje', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<template x-for="res in resources" :key="res.id"><div class="bm-ui__item"><strong x-text="res.name"></strong><button type="button" class="bm-ui__btn bm-ui__btn--small" @click="openForm(res)">' . esc_html__('Zarezerwuj', 'basemgmt') . '</button></div></template>'
			. '<template x-if="selectedResource"><div class="bm-ui__item"><p><strong x-text="selectedResource.name"></strong></p><input class="bm-ui__input" type="date" x-model="form.res_date" @change="loadSlots()"><div class="bm-ui__grid"><input class="bm-ui__input" type="time" x-model="form.start_time"><input class="bm-ui__input" type="time" x-model="form.end_time"></div><input class="bm-ui__input" type="text" x-model="form.purpose" placeholder="' . esc_attr__('Cel rezerwacji', 'basemgmt') . '"><p class="bm-ui__error" x-show="formError" x-text="formError"></p><button class="bm-ui__btn" type="button" @click="submitReservation()">' . esc_html__('Wyślij rezerwację', 'basemgmt') . '</button></div></template>'
			. '<template x-for="r in myReservations" :key="r.id"><div class="bm-ui__line"><span x-text="r.res_date + \' \' + r.start_time + \'-\' + r.end_time"></span><button type="button" class="bm-ui__btn bm-ui__btn--small bm-ui__btn--ghost" x-show="r.status === \'pending\'" @click="cancel(r.id)">' . esc_html__('Anuluj', 'basemgmt') . '</button></div></template>'
			. '</div></div>', true );
	}

	private function panel_menu_day(): string {
		return $this->wrap_panel( __('Jadłospis', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmMenu()" x-init="init(); setViewMode(\'day\')" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Jadłospis dzienny', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<select class="bm-ui__input" x-model="selectedDate" @change="selectDate(selectedDate)"><template x-for="d in availableDates" :key="d"><option :value="d" x-text="d"></option></template></select>'
			. '<template x-if="day"><div class="bm-ui__item"><template x-for="item in day.items" :key="item.id"><div class="bm-ui__line"><span x-text="mealTypeLabel(item.meal_type)"></span><strong x-text="item.name"></strong></div></template></div></template>'
			. '<p class="bm-ui__error" x-show="error" x-text="error"></p></div></div>', true );
	}

	private function panel_menu_week(): string {
		return $this->wrap_panel( __('Jadłospis', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmMenu()" x-init="init(); setViewMode(\'week\')" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Jadłospis tygodniowy', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<template x-for="dayItem in weekDays" :key="dayItem.date"><div class="bm-ui__item"><strong x-text="dayItem.date"></strong><template x-for="item in dayItem.items" :key="item.id"><div class="bm-ui__line"><span x-text="mealTypeLabel(item.meal_type)"></span><span x-text="item.name"></span></div></template></div></template>'
			. '<p class="bm-ui__error" x-show="error" x-text="error"></p></div></div>', true );
	}

	private function panel_conversations(): string {
		return $this->wrap_panel( __('Wiadomości', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmConversations()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Wiadomości', 'basemgmt') . '</h3><button type="button" class="bm-ui__btn bm-ui__btn--small" @click="view=\'new\'">' . esc_html__('Nowy', 'basemgmt') . '</button></div><div class="bm-ui__body">'
			. '<template x-for="thread in threads" :key="thread.id"><button type="button" class="bm-ui__item bm-ui__item--button" @click="openThread(thread.id)"><strong x-text="thread.subject"></strong><small x-text="thread.unread_camp ? thread.unread_camp + \' nowe\' : \'\'"></small></button></template>'
			. '</div></div>', true );
	}

	private function panel_conversation_new(): string {
		return $this->wrap_panel( __('Nowa wiadomość', 'basemgmt'), '<form class="bm-ui bm-ui--card" x-data="bmConversations()" x-init="init(); view=\'new\'" @submit.prevent="createThread()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Nowy wątek', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<input class="bm-ui__input" type="text" x-model="form.subject" placeholder="' . esc_attr__('Temat', 'basemgmt') . '"><textarea class="bm-ui__input" rows="4" x-model="form.content" placeholder="' . esc_attr__('Treść', 'basemgmt') . '"></textarea><select class="bm-ui__input" x-model="form.priority"><option value="low">' . esc_html__('Niski', 'basemgmt') . '</option><option value="normal">' . esc_html__('Normalny', 'basemgmt') . '</option><option value="high">' . esc_html__('Wysoki', 'basemgmt') . '</option><option value="urgent">' . esc_html__('Pilny', 'basemgmt') . '</option></select>'
			. '<p class="bm-ui__success" x-show="success" x-text="success"></p><p class="bm-ui__error" x-show="error" x-text="error"></p>'
			. '<button class="bm-ui__btn" type="submit" :disabled="loading">' . esc_html__('Wyślij', 'basemgmt') . '</button></div></form>', true );
	}

	private function panel_conversation_thread(): string {
		return $this->wrap_panel( __('Wątek wiadomości', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmConversations()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Widok wątku', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<p class="bm-ui__muted" x-show="!currentThread">' . esc_html__('Wybierz wątek z listy wiadomości.', 'basemgmt') . '</p>'
			. '<template x-if="currentThread"><div><h4 x-text="currentThread.subject"></h4><template x-for="m in messages" :key="m.id"><div class="bm-ui__item"><strong x-text="m.author_type"></strong><div x-text="m.content"></div></div></template><textarea class="bm-ui__input" rows="3" x-model="replyContent"></textarea><button class="bm-ui__btn" type="button" @click="sendReply()">' . esc_html__('Odpowiedz', 'basemgmt') . '</button></div></template>'
			. '</div></div>', true );
	}

	private function panel_help_list(): string {
		return $this->wrap_panel( __('Pomoc', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmHelp()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Baza pomocy', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<div class="bm-ui__grid"><input class="bm-ui__input" type="text" x-model="search" placeholder="' . esc_attr__('Szukaj…', 'basemgmt') . '"><button type="button" class="bm-ui__btn bm-ui__btn--small" @click="applyFilters()">' . esc_html__('Filtruj', 'basemgmt') . '</button></div>'
			. '<template x-for="article in articles" :key="article.id"><button type="button" class="bm-ui__item bm-ui__item--button" @click="openArticle(article.id)"><strong x-text="article.title"></strong></button></template>'
			. '</div></div>', true );
	}

	private function panel_help_article(): string {
		return $this->wrap_panel( __('Artykuł pomocy', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmHelp()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Artykuł pomocy', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<p class="bm-ui__muted" x-show="!current">' . esc_html__('Wybierz artykuł na liście pomocy.', 'basemgmt') . '</p>'
			. '<template x-if="current"><div><h4 x-text="current.title"></h4><div x-html="current.content"></div></div></template>'
			. '</div></div>', true );
	}

	private function panel_forms_list(): string {
		return $this->wrap_panel( __('Formularze', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmForms()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Formularze', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<select class="bm-ui__input" x-model="filterCategory"><option value="">' . esc_html__('Wszystkie kategorie', 'basemgmt') . '</option><template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template></select>'
			. '<template x-for="formItem in filtered" :key="formItem.id"><button type="button" class="bm-ui__item bm-ui__item--button" @click="openForm(formItem.id)"><strong x-text="formItem.title"></strong></button></template>'
			. '</div></div>', true );
	}

	private function panel_form(): string {
		return $this->wrap_panel( __('Wypełnianie formularza', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmForms()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Wypełnij formularz', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<p class="bm-ui__muted" x-show="!currentForm">' . esc_html__('Wybierz formularz z listy formularzy.', 'basemgmt') . '</p>'
			. '<template x-if="currentForm"><div><h4 x-text="currentForm.title"></h4><template x-for="f in fields" :key="f.id"><div><label class="bm-ui__label" x-text="f.label"></label><input class="bm-ui__input" type="text" x-model="formValues[f.field_key]"><small class="bm-ui__error" x-text="fieldError(f.field_key)"></small></div></template><p class="bm-ui__error" x-show="error" x-text="error"></p><button class="bm-ui__btn" type="button" @click="submit()" :disabled="submitting">' . esc_html__('Wyślij', 'basemgmt') . '</button><p class="bm-ui__success" x-show="submitted">' . esc_html__('Formularz wysłany.', 'basemgmt') . '</p></div></template>'
			. '</div></div>', true );
	}

	private function panel_submissions_list(): string {
		return $this->wrap_panel( __('Moje zgłoszenia', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmSubmissions()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Moje zgłoszenia', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<select class="bm-ui__input" x-model="filterStatus" @change="applyFilter()"><option value="">' . esc_html__('Wszystkie statusy', 'basemgmt') . '</option><option value="new">' . esc_html__('Nowe', 'basemgmt') . '</option><option value="in_progress">' . esc_html__('W trakcie', 'basemgmt') . '</option><option value="waiting">' . esc_html__('Oczekuje', 'basemgmt') . '</option><option value="closed">' . esc_html__('Zamknięte', 'basemgmt') . '</option><option value="cancelled">' . esc_html__('Anulowane', 'basemgmt') . '</option></select>'
			. '<template x-for="s in submissions" :key="s.id"><button type="button" class="bm-ui__item bm-ui__item--button" @click="openSubmission(s.id)"><strong x-text="s.form_title || s.category"></strong><small x-text="statusLabel(s.status)"></small></button></template>'
			. '</div></div>', true );
	}

	private function panel_submission(): string {
		return $this->wrap_panel( __('Szczegóły zgłoszenia', 'basemgmt'), '<div class="bm-ui bm-ui--card" x-data="bmSubmissions()" x-init="init()" >'
			. '<div class="bm-ui__header"><h3>' . esc_html__('Szczegóły zgłoszenia', 'basemgmt') . '</h3></div><div class="bm-ui__body">'
			. '<p class="bm-ui__muted" x-show="!current">' . esc_html__('Wybierz zgłoszenie z listy.', 'basemgmt') . '</p>'
			. '<template x-if="current"><div><p><strong>' . esc_html__('Status:', 'basemgmt') . '</strong> <span x-text="statusLabel(current.submission.status)"></span></p><p><strong>' . esc_html__('Priorytet:', 'basemgmt') . '</strong> <span x-text="priorityLabel(current.submission.priority)"></span></p><pre class="bm-ui__json" x-text="JSON.stringify(current.submission_data, null, 2)"></pre></div></template>'
			. '</div></div>', true );
	}

	private function panel_unread_counter(): string {
		return '<div class="bm-ui bm-ui--badge" x-data="{}" x-cloak x-show="$store.bm.authenticated"><span x-text="$store.bm.unreadCount"></span></div>';
	}

	private function wrap_panel( string $section_title, string $content, ?bool $show_when_authenticated = null, string $extra_classes = '' ): string {
		$attrs = ' x-data="{}" x-cloak';
		if ( $show_when_authenticated !== null ) {
			$attrs .= ' x-show="' . ( $show_when_authenticated ? '($store.bm && $store.bm.authenticated)' : '!($store.bm && $store.bm.authenticated)' ) . '"';
		}

		$title_markup = $section_title !== ''
			? '<div class="bm-ui__section-title">' . esc_html( $section_title ) . '</div>'
			: '';

		$classes = trim( 'bm-ui bm-ui--panel ' . $extra_classes );

		return '<div class="' . esc_attr( $classes ) . '"' . $attrs . '>' . $title_markup . $content . '</div>';
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
