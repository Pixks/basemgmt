<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

defined('ABSPATH') || exit;

/**
 * Registry and DB storage for plugin email templates.
 *
 * Each template can be customised via the admin settings panel.
 * When a DB override exists it replaces the built-in PHP file template.
 * Variables inside templates use {{double_braces}} notation, e.g. {{oboz}}.
 *
 * To register a new template (e.g. for a new module):
 *   Add an entry to get_registry() with label, default_subject, variables and default_html.
 *   Then call EmailService::send(..., 'your_slug', $data) as usual.
 */
final class EmailTemplateRepository {

	private const OPTION_PREFIX = 'basemgmt_email_tpl_';

	// ── Registry ──────────────────────────────────────────────────────────────

	/**
	 * Full registry of all plugin email templates.
	 *
	 * Structure per entry:
	 *   label           – shown in settings list
	 *   default_subject – subject with optional {{tokens}}
	 *   variables       – map of {{token}} => description (shown as hints in editor)
	 *   default_html    – built-in HTML body (used when no DB override exists)
	 *
	 * @return array<string, array{label:string, default_subject:string, variables:array, default_html:string}>
	 */
	public static function get_registry(): array {
		return [
			'reservation_created' => [
				'label'           => __('Rezerwacja – nowe zgłoszenie', 'basemgmt'),
				'default_subject' => __('Potwierdzenie rezerwacji: {{zasob}}', 'basemgmt'),
				'variables'       => array_merge(self::reservation_vars(), [
					'{{link_panelu_admin}}' => __('Link do listy rezerwacji w panelu admina', 'basemgmt'),
				]),
				'default_html'    => self::default_reservation_created(),
			],
			'reservation_approved' => [
				'label'           => __('Rezerwacja – zatwierdzona', 'basemgmt'),
				'default_subject' => __('Rezerwacja zatwierdzona: {{zasob}}', 'basemgmt'),
				'variables'       => self::reservation_vars(),
				'default_html'    => self::default_reservation_approved(),
			],
			'reservation_rejected' => [
				'label'           => __('Rezerwacja – odrzucona', 'basemgmt'),
				'default_subject' => __('Rezerwacja odrzucona: {{zasob}}', 'basemgmt'),
				'variables'       => array_merge(self::reservation_vars(), [
					'{{komentarz}}' => __('Komentarz administratora', 'basemgmt'),
				]),
				'default_html'    => self::default_reservation_rejected(),
			],
			'reservation_cancelled' => [
				'label'           => __('Rezerwacja – anulowana', 'basemgmt'),
				'default_subject' => __('Rezerwacja anulowana: {{zasob}}', 'basemgmt'),
				'variables'       => array_merge(self::reservation_vars(), [
					'{{komentarz}}' => __('Komentarz administratora', 'basemgmt'),
				]),
				'default_html'    => self::default_reservation_cancelled(),
			],
			'missing_report_notification' => [
				'label'           => __('Meldunki – brak raportu dziennego', 'basemgmt'),
				'default_subject' => __('Brak dziennego meldunku – {{raport_data}}', 'basemgmt'),
				'variables'       => self::missing_report_vars(),
				'default_html'    => self::default_missing_report_notification(),
			],
			'periodic_staff_report' => [
				'label'           => __('Meldunki – cykliczny raport stanów', 'basemgmt'),
				'default_subject' => __('Raport stanów osobowych – {{raport_data}} {{raport_godzina}}', 'basemgmt'),
				'variables'       => self::periodic_staff_report_vars(),
				'default_html'    => self::default_periodic_staff_report(),
			],
		];
	}

	// ── Storage ───────────────────────────────────────────────────────────────

	/**
	 * Returns the DB-saved template override or null if not customised.
	 *
	 * @return array{subject:string, html_body:string}|null
	 */
	public static function get_saved(string $slug): ?array {
		$value = get_option(self::OPTION_PREFIX . sanitize_key($slug), null);
		if ( ! is_array($value) || empty($value['html_body']) ) {
			return null;
		}
		return $value;
	}

	/**
	 * Persists a template override to wp_options.
	 * Strips disallowed HTML tags via wp_kses_post.
	 */
	public static function save(string $slug, string $subject, string $html_body): bool {
		$registry = self::get_registry();
		if ( ! array_key_exists($slug, $registry) ) {
			return false;
		}
		return update_option(
			self::OPTION_PREFIX . sanitize_key($slug),
			[
				'subject'   => sanitize_text_field($subject),
				'html_body' => wp_kses_post($html_body),
			],
			false  // don't autoload every option on every page
		);
	}

	/**
	 * Removes the DB override so the built-in default is used again.
	 */
	public static function reset(string $slug): void {
		delete_option(self::OPTION_PREFIX . sanitize_key($slug));
	}

	// ── Rendering ─────────────────────────────────────────────────────────────

	/**
	 * Renders the body for a template using DB override if available.
	 * Returns null when no DB override exists (caller should use PHP file template).
	 */
	public static function render_body(string $slug, array $data): ?string {
		$saved = self::get_saved($slug);
		if ( ! $saved ) {
			return null;
		}
		return self::substitute($saved['html_body'], self::build_vars($slug, $data));
	}

	/**
	 * Returns the customised subject for a template, or null to use the hard-coded one.
	 */
	public static function get_subject_override(string $slug, array $data = []): ?string {
		$saved = self::get_saved($slug);
		if ( ! $saved || empty($saved['subject']) ) {
			return null;
		}
		return self::substitute($saved['subject'], self::build_vars($slug, $data));
	}

	// ── Variable substitution ─────────────────────────────────────────────────

	/**
	 * Replaces {{token}} placeholders in $text with values from $vars.
	 *
	 * @param array<string, string> $vars  keys include braces: '{{zasob}}' => 'Boisko'
	 */
	public static function substitute(string $text, array $vars): string {
		return str_replace(array_keys($vars), array_values($vars), $text);
	}

	/**
	 * Builds a flat map of {{token}} => value for a specific template and runtime data.
	 *
	 * @return array<string, string>
	 */
	private static function build_vars(string $slug, array $data): array {
		$res = is_array($data['reservation'] ?? null) ? $data['reservation'] : [];

		$vars = [
			'{{nazwa_systemu}}'    => esc_html(get_bloginfo('name')),
			'{{oboz}}'             => esc_html((string) ($data['camp_name']     ?? '')),
			'{{zasob}}'            => esc_html((string) ($data['resource_name'] ?? '')),
			'{{data}}'             => esc_html(isset($res['res_date'])   ? date_i18n('d.m.Y', strtotime($res['res_date'])) : ''),
			'{{godzina_od}}'       => esc_html((string) ($res['start_time'] ?? '')),
			'{{godzina_do}}'       => esc_html((string) ($res['end_time']   ?? '')),
			'{{cel}}'              => esc_html((string) ($res['purpose']    ?? '')),
			'{{komentarz}}'        => esc_html((string) ($data['admin_comment'] ?? $res['admin_comment'] ?? '')),
			'{{link_panelu_admin}}' => esc_url(admin_url('admin.php?page=basemgmt-reservations&filter_status=pending')),
			'{{raport_data}}'      => esc_html((string) ($data['report_date'] ?? '')),
			'{{raport_godzina}}'   => esc_html((string) ($data['report_time'] ?? '')),
			'{{liczba_obozow}}'    => esc_html((string) ($data['missing_count'] ?? '')),
			'{{lista_obozow_html}}' => (string) ($data['missing_camps_html'] ?? ''),
			'{{lista_stanow_html}}' => (string) ($data['report_lines_html'] ?? ''),
			'{{suma_uczestnikow}}'  => esc_html((string) ($data['total_participants'] ?? '0')),
			'{{suma_kadra}}'        => esc_html((string) ($data['total_staff'] ?? '0')),
			'{{suma_pracownikow}}'  => esc_html((string) ($data['total_workers'] ?? '0')),
		];

		return $vars;
	}

	// ── Common variable descriptions ──────────────────────────────────────────

	/** @return array<string, string> */
	private static function reservation_vars(): array {
		return [
			'{{oboz}}'          => __('Nazwa obozu', 'basemgmt'),
			'{{zasob}}'         => __('Nazwa zasobu (boisko, sala itp.)', 'basemgmt'),
			'{{data}}'          => __('Data rezerwacji (dd.mm.rrrr)', 'basemgmt'),
			'{{godzina_od}}'    => __('Godzina rozpoczęcia', 'basemgmt'),
			'{{godzina_do}}'    => __('Godzina zakończenia', 'basemgmt'),
			'{{cel}}'           => __('Cel rezerwacji', 'basemgmt'),
			'{{nazwa_systemu}}' => __('Nazwa strony / systemu', 'basemgmt'),
		];
	}

	/** @return array<string, string> */
	private static function missing_report_vars(): array {
		return [
			'{{nazwa_systemu}}'   => __('Nazwa strony / systemu', 'basemgmt'),
			'{{raport_data}}'     => __('Data sprawdzenia raportów', 'basemgmt'),
			'{{liczba_obozow}}'   => __('Liczba obozów bez raportu', 'basemgmt'),
			'{{lista_obozow_html}}' => __('Lista obozów bez raportu (HTML)', 'basemgmt'),
		];
	}

	/** @return array<string, string> */
	private static function periodic_staff_report_vars(): array {
		return [
			'{{nazwa_systemu}}'     => __('Nazwa strony / systemu', 'basemgmt'),
			'{{raport_data}}'       => __('Data raportu', 'basemgmt'),
			'{{raport_godzina}}'    => __('Godzina raportu', 'basemgmt'),
			'{{lista_stanow_html}}' => __('Zestawienie obozów (HTML)', 'basemgmt'),
			'{{suma_uczestnikow}}'  => __('Łączna liczba uczestników', 'basemgmt'),
			'{{suma_kadra}}'        => __('Łączna liczba kadry', 'basemgmt'),
			'{{suma_pracownikow}}'  => __('Łączna liczba pracowników', 'basemgmt'),
		];
	}

	// ── Default HTML bodies ───────────────────────────────────────────────────

	private static function default_reservation_created(): string {
		return '<h2>' . esc_html__('Potwierdzenie złożenia rezerwacji', 'basemgmt') . '</h2>
<p>' . esc_html__('Twoja prośba o rezerwację została przyjęta i oczekuje na zatwierdzenie przez administratora.', 'basemgmt') . '</p>
<table border="0" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:520px;">
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;white-space:nowrap;font-weight:600;">' . esc_html__('Zasób', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{zasob}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Obóz', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{oboz}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Data', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{data}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Godziny', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{godzina_od}} – {{godzina_do}}</td>
  </tr>
  <tr>
    <th align="left" style="padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Cel', 'basemgmt') . '</th>
    <td style="padding:6px;">{{cel}}</td>
  </tr>
</table>
<p style="margin-top:16px;"><a href="{{link_panelu_admin}}" style="color:#2271b1;">' . esc_html__('Zarządzaj rezerwacjami →', 'basemgmt') . '</a></p>';
	}

	private static function default_reservation_approved(): string {
		return '<h2>' . esc_html__('Rezerwacja zatwierdzona ✓', 'basemgmt') . '</h2>
<p>' . esc_html__('Twoja rezerwacja została <strong>zatwierdzona</strong>.', 'basemgmt') . '</p>
<table border="0" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:520px;">
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Zasób', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{zasob}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Obóz', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{oboz}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Data', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{data}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Godziny', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{godzina_od}} – {{godzina_do}}</td>
  </tr>
  <tr>
    <th align="left" style="padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Cel', 'basemgmt') . '</th>
    <td style="padding:6px;">{{cel}}</td>
  </tr>
</table>';
	}

	private static function default_reservation_rejected(): string {
		return '<h2>' . esc_html__('Rezerwacja odrzucona', 'basemgmt') . '</h2>
<p>' . esc_html__('Niestety, Twoja prośba o rezerwację została <strong>odrzucona</strong>.', 'basemgmt') . '</p>
<table border="0" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:520px;">
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Zasób', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{zasob}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Obóz', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{oboz}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Data', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{data}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Godziny', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{godzina_od}} – {{godzina_do}}</td>
  </tr>
  <tr>
    <th align="left" style="padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Cel', 'basemgmt') . '</th>
    <td style="padding:6px;">{{cel}}</td>
  </tr>
</table>
<p style="margin-top:16px;"><strong>' . esc_html__('Komentarz administratora:', 'basemgmt') . '</strong><br>{{komentarz}}</p>';
	}

	private static function default_reservation_cancelled(): string {
		return '<h2>' . esc_html__('Rezerwacja anulowana', 'basemgmt') . '</h2>
<p>' . esc_html__('Rezerwacja została <strong>anulowana</strong>.', 'basemgmt') . '</p>
<table border="0" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:520px;">
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Zasób', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{zasob}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Obóz', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{oboz}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Data', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{data}}</td>
  </tr>
  <tr>
    <th align="left" style="border-bottom:1px solid #e5e7eb;padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Godziny', 'basemgmt') . '</th>
    <td style="border-bottom:1px solid #e5e7eb;padding:6px;">{{godzina_od}} – {{godzina_do}}</td>
  </tr>
  <tr>
    <th align="left" style="padding:6px 16px 6px 0;color:#6b7280;font-weight:600;">' . esc_html__('Cel', 'basemgmt') . '</th>
    <td style="padding:6px;">{{cel}}</td>
  </tr>
</table>
<p style="margin-top:16px;"><strong>' . esc_html__('Komentarz:', 'basemgmt') . '</strong><br>{{komentarz}}</p>';
	}

	private static function default_missing_report_notification(): string {
		return '<h2>' . esc_html__('Brak dziennych meldunków', 'basemgmt') . '</h2>
<p>' . esc_html__('Dla części obozów nadal brakuje meldunku dziennego.', 'basemgmt') . '</p>
<p><strong>' . esc_html__('Data sprawdzenia:', 'basemgmt') . '</strong> {{raport_data}}</p>
<p><strong>' . esc_html__('Liczba brakujących meldunków:', 'basemgmt') . '</strong> {{liczba_obozow}}</p>
{{lista_obozow_html}}';
	}

	private static function default_periodic_staff_report(): string {
		return '<h2>' . esc_html__('Raport stanów osobowych', 'basemgmt') . '</h2>
<p><strong>' . esc_html__('Data:', 'basemgmt') . '</strong> {{raport_data}}<br><strong>' . esc_html__('Godzina:', 'basemgmt') . '</strong> {{raport_godzina}}</p>
{{lista_stanow_html}}
<p style="margin-top:16px;"><strong>' . esc_html__('Suma:', 'basemgmt') . '</strong> {{suma_uczestnikow}} / {{suma_kadra}} / {{suma_pracownikow}}</p>';
	}
}
