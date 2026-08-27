<?php
defined('ABSPATH') || exit;
use BaseMgmt\Core\EmailService;
use BaseMgmt\Core\EmailTemplateRepository;
use BaseMgmt\Core\PdfSettings;
use BaseMgmt\Frontend\PanelStyleSettings;

$s          = EmailService::get_settings();
$pdf        = PdfSettings::get_settings();
$registry   = EmailTemplateRepository::get_registry();
$ui_style   = PanelStyleSettings::get_settings();
$ui_presets = PanelStyleSettings::presets();

// Enqueue CodeMirror for the HTML fields.
$cm_settings = wp_enqueue_code_editor(['type' => 'text/html', 'codemirror' => ['lineNumbers' => false, 'lineWrapping' => true]]);
wp_enqueue_script('wp-theme-plugin-editor');
wp_enqueue_style('wp-codemirror');

$current_tab = sanitize_key($_GET['tab'] ?? 'email');
$valid_tabs  = ['email', 'pdf', 'wyglad', 'powiadomienia', 'dane', 'info'];
if ( ! in_array($current_tab, $valid_tabs, true) ) {
	$current_tab = 'email';
}

$tab_url = fn(string $t) => esc_url(admin_url("admin.php?page=basemgmt-settings&tab=$t"));

$tabs = [
	'email'         => '📧 ' . __('Email', 'basemgmt'),
	'pdf'           => '🖨 ' . __('Wydruk / PDF', 'basemgmt'),
	'wyglad'        => '🎨 ' . __('Wygląd', 'basemgmt'),
	'powiadomienia' => '🔔 ' . __('Powiadomienia', 'basemgmt'),
	'dane'          => '🗄 ' . __('Dane', 'basemgmt'),
	'info'          => 'ℹ️ ' . __('O pluginie', 'basemgmt'),
];
?>
<div class="wrap bm-wrap">
    <h1><?php esc_html_e('Ustawienia – Baza Obozowa', 'basemgmt'); ?></h1>

    <!-- Tab navigation -->
    <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom:24px;">
        <?php foreach ($tabs as $slug => $label): ?>
        <a href="<?php echo $tab_url($slug); ?>"
           class="nav-tab<?php echo $current_tab === $slug ? ' nav-tab-active' : ''; ?>">
            <?php echo esc_html($label); ?>
        </a>
        <?php endforeach; ?>
    </nav>

<?php /* ═══════════════════════════════════════════════════════ EMAIL TAB ══ */ ?>
<?php if ($current_tab === 'email'): ?>

    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">📧 <?php esc_html_e('Ustawienia powiadomień email', 'basemgmt'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_settings'); ?>
            <input type="hidden" name="action" value="bm_save_settings">
            <input type="hidden" name="_bm_current_tab" value="<?php echo esc_attr($current_tab); ?>">
            <table class="form-table" style="margin:0;">
                <tr>
                    <th><label for="bm-from-name"><?php esc_html_e('Nazwa nadawcy', 'basemgmt'); ?></label></th>
                    <td><input type="text" id="bm-from-name" name="from_name" class="regular-text" value="<?php echo esc_attr($s['from_name']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="bm-from-email"><?php esc_html_e('Email nadawcy', 'basemgmt'); ?></label></th>
                    <td><input type="email" id="bm-from-email" name="from_email" class="regular-text" value="<?php echo esc_attr($s['from_email']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="bm-admin-email"><?php esc_html_e('Email admina (powiadomienia)', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="email" id="bm-admin-email" name="admin_notify_email" class="regular-text" value="<?php echo esc_attr($s['admin_notify_email']); ?>">
                        <p class="description"><?php esc_html_e('Na ten adres będą trafiać powiadomienia o nowych rezerwacjach i innych zdarzeniach systemowych.', 'basemgmt'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-header-color"><?php esc_html_e('Kolor nagłówka emaila', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="color" id="bm-header-color" name="header_color" value="<?php echo esc_attr($s['header_color']); ?>">
                        <code><?php echo esc_html($s['header_color']); ?></code>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-logo-url"><?php esc_html_e('URL logo (opcjonalnie)', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="url" id="bm-logo-url" name="logo_url" class="large-text" value="<?php echo esc_attr($s['logo_url']); ?>">
                        <?php if ($s['logo_url']): ?>
                        <br><img src="<?php echo esc_url($s['logo_url']); ?>" style="max-height:40px;margin-top:6px;" alt="logo preview">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-header-title"><?php esc_html_e('Tytuł w nagłówku emaila', 'basemgmt'); ?></label></th>
                    <td><input type="text" id="bm-header-title" name="header_title" class="regular-text" value="<?php echo esc_attr($s['header_title']); ?>">
                    <p class="description"><?php esc_html_e('Widoczny gdy nie ma logo i nie ma własnego HTML nagłówka.', 'basemgmt'); ?></p></td>
                </tr>
                <tr>
                    <th><label for="bm-header-html"><?php esc_html_e('Nagłówek emaila (HTML)', 'basemgmt'); ?></label></th>
                    <td>
                        <textarea id="bm-header-html" name="header_html" rows="6" class="large-text code"
                                  style="font-family:monospace;font-size:12px;"><?php echo esc_textarea($s['header_html']); ?></textarea>
                        <p class="description"><?php esc_html_e('Własny HTML nagłówka – nadpisuje logo/tytuł. Np. <img>, <p>, <table>. Zostaw puste, aby użyć logo/tytułu.', 'basemgmt'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-footer"><?php esc_html_e('Stopka emaila (HTML)', 'basemgmt'); ?></label></th>
                    <td>
                        <textarea id="bm-footer" name="footer_text" rows="6" class="large-text code"
                                  style="font-family:monospace;font-size:12px;"><?php echo esc_textarea($s['footer_text']); ?></textarea>
                        <p class="description"><?php esc_html_e('Np. adres ośrodka, dane kontaktowe. Obsługuje HTML (np. <a>, <strong>, <br>).', 'basemgmt'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Zapisz ustawienia', 'basemgmt')); ?>
        </form>
    </div>

    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">✏️ <?php esc_html_e('Szablony emaili', 'basemgmt'); ?></h2>
        <p class="description" style="margin:0 0 14px;">
            <?php esc_html_e('Każdy szablon można edytować jako HTML. Użyj zmiennych takich jak {{oboz}}, {{zasob}}, {{data}} do wstawiania danych dynamicznych. Jeśli szablon nie jest zmodyfikowany, używany jest wbudowany domyślny.', 'basemgmt'); ?>
        </p>
        <table class="wp-list-table widefat fixed striped" style="border:0;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Szablon', 'basemgmt'); ?></th>
                    <th style="width:120px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registry as $tpl_slug => $def): ?>
                <?php $is_custom = EmailTemplateRepository::get_saved($tpl_slug) !== null; ?>
                <tr>
                    <td><strong><?php echo esc_html($def['label']); ?></strong></td>
                    <td>
                        <?php if ($is_custom): ?>
                            <span style="color:#2271b1;font-weight:600;">● <?php esc_html_e('Własny', 'basemgmt'); ?></span>
                        <?php else: ?>
                            <span style="color:#6b7280;">○ <?php esc_html_e('Domyślny', 'basemgmt'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-settings&edit_template=$tpl_slug")); ?>"
                           class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;"><?php esc_html_e('Test emaila', 'basemgmt'); ?></h2>
        <p class="description"><?php esc_html_e('Wyślij testowy email aby sprawdzić wygląd szablonu (reservation_created) i konfigurację serwera pocztowego.', 'basemgmt'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;gap:8px;align-items:center;">
            <?php wp_nonce_field('bm_send_test_email'); ?>
            <input type="hidden" name="action" value="bm_send_test_email">
            <input type="email" name="test_email" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text" placeholder="test@example.com">
            <button type="submit" class="button button-secondary"><?php esc_html_e('Wyślij testowy email', 'basemgmt'); ?></button>
        </form>
    </div>

<?php /* ═══════════════════════════════════════════════════════ PDF TAB ══ */ ?>
<?php elseif ($current_tab === 'pdf'): ?>

    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">🖨 <?php esc_html_e('Wygląd widoków do druku', 'basemgmt'); ?></h2>
        <p class="description" style="margin:0 0 14px;">
            <?php esc_html_e('Ustaw branding i podstawowy wygląd raportów otwieranych w nowej karcie do wydruku lub zapisu jako PDF.', 'basemgmt'); ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_settings'); ?>
            <input type="hidden" name="action" value="bm_save_settings">
            <input type="hidden" name="_bm_current_tab" value="<?php echo esc_attr($current_tab); ?>">
            <table class="form-table" style="margin:0;">
                <tr>
                    <th><label for="bm-pdf-title"><?php esc_html_e('Tytuł nagłówka', 'basemgmt'); ?></label></th>
                    <td><input type="text" id="bm-pdf-title" name="pdf_header_title" class="regular-text" value="<?php echo esc_attr($pdf['header_title']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="bm-pdf-subtitle"><?php esc_html_e('Podtytuł', 'basemgmt'); ?></label></th>
                    <td><input type="text" id="bm-pdf-subtitle" name="pdf_header_subtitle" class="regular-text" value="<?php echo esc_attr($pdf['header_subtitle']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="bm-pdf-color"><?php esc_html_e('Kolor akcentu', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="color" id="bm-pdf-color" name="pdf_accent_color" value="<?php echo esc_attr($pdf['accent_color']); ?>">
                        <code><?php echo esc_html($pdf['accent_color']); ?></code>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-pdf-logo"><?php esc_html_e('URL logo', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="url" id="bm-pdf-logo" name="pdf_logo_url" class="large-text" value="<?php echo esc_attr($pdf['logo_url']); ?>">
                        <?php if ($pdf['logo_url']): ?>
                        <br><img src="<?php echo esc_url($pdf['logo_url']); ?>" style="max-height:50px;margin-top:6px;" alt="logo preview">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-pdf-footer"><?php esc_html_e('Stopka dokumentu', 'basemgmt'); ?></label></th>
                    <td><input type="text" id="bm-pdf-footer" name="pdf_footer_text" class="large-text" value="<?php echo esc_attr($pdf['footer_text']); ?>"></td>
                </tr>
            </table>
            <?php submit_button(__('Zapisz wygląd wydruków', 'basemgmt')); ?>
        </form>
    </div>

<?php /* ═══════════════════════════════════════════════════ WYGLĄD TAB ══ */ ?>
<?php elseif ($current_tab === 'wyglad'): ?>

<?php
$shadow_labels = [
	'none' => __('Brak', 'basemgmt'),
	'sm'   => __('Delikatny', 'basemgmt'),
	'md'   => __('Średni', 'basemgmt'),
	'lg'   => __('Wyraźny', 'basemgmt'),
];
$font_labels = [
	'lato'      => 'Lato',
	'open-sans' => 'Open Sans',
	'roboto'    => 'Roboto',
	'nunito'    => 'Nunito',
	'system'    => __('Systemowy', 'basemgmt'),
];
?>
<style>
/* Preview area styling */
#bm-style-preview {
    background: var(--bmp-bg, #F4F7F0);
    padding: 28px;
    border-radius: 12px;
    border: 1px solid #dde;
    font-family: var(--bmp-font, "Open Sans", sans-serif);
    color: var(--bmp-text, #333);
    transition: all .2s;
}
#bm-style-preview .prev-section-title {
    font-weight: 900;
    font-size: 1.4rem;
    color: var(--bmp-heading, #1A1A1A);
    letter-spacing: .02em;
    text-transform: uppercase;
    margin: 0 0 16px;
}
#bm-style-preview .prev-card {
    background: var(--bmp-surface, #fff);
    border: 1px solid var(--bmp-border, #E0E6E0);
    border-radius: var(--bmp-radius, 10px);
    box-shadow: var(--bmp-shadow, 0 2px 12px rgba(0,0,0,.10));
    overflow: hidden;
    margin-bottom: 14px;
}
#bm-style-preview .prev-card-header {
    background: var(--bmp-header-bg, #6EA82E);
    color: var(--bmp-btn-text, #fff);
    padding: 11px 16px;
    font-weight: 700;
    font-size: .95rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
#bm-style-preview .prev-card-body {
    padding: 14px 16px;
}
#bm-style-preview .prev-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--bmp-primary, #6EA82E);
    color: var(--bmp-btn-text, #fff);
    border: none;
    border-radius: 999px;
    padding: 9px 20px;
    font-weight: 700;
    font-size: .88rem;
    cursor: default;
    transition: background .15s;
    font-family: var(--bmp-font, "Open Sans", sans-serif);
}
#bm-style-preview .prev-btn-ghost {
    background: transparent;
    color: var(--bmp-primary, #6EA82E);
    border: 2px solid var(--bmp-primary, #6EA82E);
}
#bm-style-preview .prev-tag {
    display: inline-block;
    background: var(--bmp-badge-bg, #6EA82E);
    color: var(--bmp-badge-text, #fff);
    font-size: .72rem;
    font-weight: 700;
    border-radius: 999px;
    padding: 3px 10px;
    letter-spacing: .03em;
}
#bm-style-preview .prev-read-more {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: var(--bmp-link, #5A8D24);
    font-weight: 700;
    font-size: .88rem;
    text-decoration: none;
}
#bm-style-preview .prev-items-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 12px;
}
#bm-style-preview .prev-item {
    flex: 1 1 180px;
    background: var(--bmp-surface, #fff);
    border: 1px solid var(--bmp-border, #E0E6E0);
    border-radius: var(--bmp-radius, 10px);
    box-shadow: var(--bmp-shadow, 0 2px 12px rgba(0,0,0,.10));
    padding: 10px 12px;
    font-size: .88rem;
}
#bm-style-preview .prev-item-title {
    font-weight: 700;
    color: var(--bmp-heading, #1A1A1A);
    margin: 6px 0 4px;
}
#bm-style-preview .prev-item-meta {
    font-size: .78rem;
    color: #888;
}
#bm-style-preview .prev-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 10px;
}
.bm-style-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 32px;
}
.bm-style-form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 4px;
    font-size: .9rem;
}
.bm-style-form-group input[type=color] {
    width: 50px;
    height: 32px;
    padding: 2px 3px;
    border: 1px solid #ccc;
    border-radius: 5px;
    cursor: pointer;
    vertical-align: middle;
}
.bm-style-form-group .color-hex {
    font-size: .8rem;
    color: #555;
    margin-left: 6px;
    font-family: monospace;
    vertical-align: middle;
}
.bm-style-section-title {
    font-weight: 700;
    font-size: .82rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #666;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 6px;
    margin: 24px 0 16px;
}
</style>

<div style="display:grid;grid-template-columns:1fr 420px;gap:28px;align-items:start;max-width:1180px;">

    <!-- Left: form -->
    <div class="postbox" style="padding:20px 24px;">
        <h2 class="hndle" style="padding:0 0 10px;">🎨 <?php esc_html_e('Wygląd shortcode panelu kadry', 'basemgmt'); ?></h2>
        <p class="description" style="margin:0 0 16px;">
            <?php esc_html_e('Wybierz gotowy preset i dopasuj każdy szczegół wyglądu elementów wyświetlanych przez shortcode panelu kadry.', 'basemgmt'); ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="bm-style-form">
            <?php wp_nonce_field('bm_save_settings'); ?>
            <input type="hidden" name="action" value="bm_save_settings">
            <input type="hidden" name="_bm_current_tab" value="<?php echo esc_attr($current_tab); ?>">

            <!-- Preset selector -->
            <div class="bm-style-section-title"><?php esc_html_e('Szybki start – presety', 'basemgmt'); ?></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
                <?php foreach ($ui_presets as $pk => $pv): ?>
                <button type="button" class="button<?php echo $ui_style['preset'] === $pk ? ' button-primary' : ''; ?>"
                        data-bm-preset="<?php echo esc_attr(wp_json_encode($pv + ['key' => $pk])); ?>">
                    <?php echo esc_html($pv['label']); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="bm_ui_style_preset" id="bm-ui-style-preset" value="<?php echo esc_attr($ui_style['preset']); ?>">

            <!-- Colours: primary -->
            <div class="bm-style-section-title"><?php esc_html_e('Kolory główne', 'basemgmt'); ?></div>
            <div class="bm-style-form-grid">
                <div class="bm-style-form-group">
                    <label for="bm-ui-primary-color"><?php esc_html_e('Kolor główny (przyciski, akcenty)', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-primary-color" name="bm_ui_primary_color" value="<?php echo esc_attr($ui_style['primary_color']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['primary_color']); ?></span>
                </div>
                <div class="bm-style-form-group">
                    <label for="bm-ui-primary-hover-color"><?php esc_html_e('Kolor hover', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-primary-hover-color" name="bm_ui_primary_hover_color" value="<?php echo esc_attr($ui_style['primary_hover']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['primary_hover']); ?></span>
                </div>
                <div class="bm-style-form-group">
                    <label for="bm-ui-badge-color"><?php esc_html_e('Kolor badge / tagu', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-badge-color" name="bm_ui_badge_color" value="<?php echo esc_attr($ui_style['badge_color']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['badge_color']); ?></span>
                </div>
                <div class="bm-style-form-group">
                    <label for="bm-ui-badge-text-color"><?php esc_html_e('Tekst badge / tagu', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-badge-text-color" name="bm_ui_badge_text_color" value="<?php echo esc_attr($ui_style['badge_text_color']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['badge_text_color']); ?></span>
                </div>
                <div class="bm-style-form-group">
                    <label for="bm-ui-btn-text-color"><?php esc_html_e('Tekst przycisków', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-btn-text-color" name="bm_ui_btn_text_color" value="<?php echo esc_attr($ui_style['btn_text_color']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['btn_text_color']); ?></span>
                </div>
                <div class="bm-style-form-group">
                    <label for="bm-ui-link-color"><?php esc_html_e('Kolor linków', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-link-color" name="bm_ui_link_color" value="<?php echo esc_attr($ui_style['link_color']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['link_color']); ?></span>
                </div>
            </div>

            <!-- Colours: text & surfaces -->
            <div class="bm-style-section-title"><?php esc_html_e('Kolory tekstu i tła', 'basemgmt'); ?></div>
            <div class="bm-style-form-grid">
                <div class="bm-style-form-group">
                    <label for="bm-ui-text-color"><?php esc_html_e('Kolor tekstu podstawowego', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-text-color" name="bm_ui_text_color" value="<?php echo esc_attr($ui_style['text_color']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['text_color']); ?></span>
                </div>
                <div class="bm-style-form-group">
                    <label for="bm-ui-heading-color"><?php esc_html_e('Kolor nagłówków', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-heading-color" name="bm_ui_heading_color" value="<?php echo esc_attr($ui_style['heading_color']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['heading_color']); ?></span>
                </div>
                <div class="bm-style-form-group">
                    <label for="bm-ui-surface-color"><?php esc_html_e('Tło kart (surface)', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-surface-color" name="bm_ui_surface_color" value="<?php echo esc_attr($ui_style['surface_color']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['surface_color']); ?></span>
                </div>
                <div class="bm-style-form-group">
                    <label for="bm-ui-background-color"><?php esc_html_e('Kolor tła sekcji', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-background-color" name="bm_ui_background_color" value="<?php echo esc_attr($ui_style['background']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['background']); ?></span>
                </div>
                <div class="bm-style-form-group">
                    <label for="bm-ui-border-color"><?php esc_html_e('Kolor obramowań', 'basemgmt'); ?></label>
                    <input type="color" id="bm-ui-border-color" name="bm_ui_border_color" value="<?php echo esc_attr($ui_style['border_color']); ?>">
                    <span class="color-hex"><?php echo esc_html($ui_style['border_color']); ?></span>
                </div>
            </div>

            <!-- Shape & typography -->
            <div class="bm-style-section-title"><?php esc_html_e('Kształt, typografia i efekty', 'basemgmt'); ?></div>
            <table class="form-table" style="margin:0;">
                <tr>
                    <th style="width:220px;"><label for="bm-ui-radius"><?php esc_html_e('Zaokrąglenie rogów (px)', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="range" id="bm-ui-radius" name="bm_ui_radius" min="0" max="32" step="1"
                               value="<?php echo esc_attr($ui_style['radius']); ?>"
                               oninput="document.getElementById('bm-radius-val').textContent=this.value"
                               style="width:160px;vertical-align:middle;">
                        <strong id="bm-radius-val" style="margin-left:6px;font-family:monospace;"><?php echo esc_html($ui_style['radius']); ?></strong> px
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-ui-shadow"><?php esc_html_e('Cień kart', 'basemgmt'); ?></label></th>
                    <td>
                        <select id="bm-ui-shadow" name="bm_ui_shadow">
                            <?php foreach ($shadow_labels as $sv => $sl): ?>
                            <option value="<?php echo esc_attr($sv); ?>" <?php selected($ui_style['shadow'], $sv); ?>>
                                <?php echo esc_html($sl); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-ui-font-family"><?php esc_html_e('Czcionka', 'basemgmt'); ?></label></th>
                    <td>
                        <select id="bm-ui-font-family" name="bm_ui_font_family">
                            <?php foreach ($font_labels as $fv => $fl): ?>
                            <option value="<?php echo esc_attr($fv); ?>" <?php selected($ui_style['font_family'], $fv); ?>>
                                <?php echo esc_html($fl); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Nagłówek kart – gradient', 'basemgmt'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="bm_ui_header_gradient" value="1" <?php checked($ui_style['header_gradient'], '1'); ?>>
                            <?php esc_html_e('Włącz gradient w nagłówkach kart (primary → hover)', 'basemgmt'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Zapisz wygląd shortcode', 'basemgmt')); ?>
        </form>
    </div>

    <!-- Right: live preview -->
    <div style="position:sticky;top:40px;">
        <div class="postbox" style="padding:16px 18px;">
            <h2 class="hndle" style="padding:0 0 10px;font-size:.95rem;">
                👁 <?php esc_html_e('Podgląd na żywo', 'basemgmt'); ?>
            </h2>
            <p class="description" style="margin:0 0 14px;font-size:.8rem;">
                <?php esc_html_e('Zmiany widoczne są natychmiast – kliknij "Zapisz" aby zastosować.', 'basemgmt'); ?>
            </p>
            <div id="bm-style-preview">
                <div class="prev-section-title">AKTUALNOŚCI</div>

                <div class="prev-card">
                    <div class="prev-card-header">
                        <span>📋 <?php esc_html_e('Informacje o obozie', 'basemgmt'); ?></span>
                        <span style="font-size:.78rem;opacity:.85;"><?php esc_html_e('Kadra', 'basemgmt'); ?></span>
                    </div>
                    <div class="prev-card-body">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <span class="prev-tag"><?php esc_html_e('Aktualności', 'basemgmt'); ?></span>
                            <span class="prev-tag"><?php esc_html_e('Obóz', 'basemgmt'); ?></span>
                            <span style="margin-left:auto;font-size:.78rem;color:#888;">19.08.26</span>
                        </div>
                        <div style="font-weight:700;font-size:1rem;margin-bottom:6px;">
                            <?php esc_html_e('Meldunek dzienny – wszystko w porządku', 'basemgmt'); ?>
                        </div>
                        <div style="font-size:.84rem;margin-bottom:10px;">
                            <?php esc_html_e('Liczba uczestników: 48/50, stan sanitarny: dobry, brak incydentów.', 'basemgmt'); ?>
                        </div>
                        <a href="#" class="prev-read-more" onclick="return false;">
                            <?php esc_html_e('Przeczytaj więcej', 'basemgmt'); ?> →
                        </a>
                    </div>
                </div>

                <div class="prev-items-row">
                    <div class="prev-item">
                        <span class="prev-tag"><?php esc_html_e('Pogoda', 'basemgmt'); ?></span>
                        <div class="prev-item-title"><?php esc_html_e('Prognoza na dziś', 'basemgmt'); ?></div>
                        <div class="prev-item-meta">☀ 24°C, brak opadów</div>
                    </div>
                    <div class="prev-item">
                        <span class="prev-tag"><?php esc_html_e('Plan', 'basemgmt'); ?></span>
                        <div class="prev-item-title"><?php esc_html_e('Zajęcia 10:00', 'basemgmt'); ?></div>
                        <div class="prev-item-meta"><?php esc_html_e('Gra terenowa', 'basemgmt'); ?></div>
                    </div>
                </div>

                <div class="prev-actions" style="margin-top:16px;">
                    <button class="prev-btn" type="button"><?php esc_html_e('Wyślij meldunek', 'basemgmt'); ?> →</button>
                    <button class="prev-btn prev-btn-ghost" type="button"><?php esc_html_e('Historia', 'basemgmt'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div><!-- /grid -->

<script>
(function () {
    var shadows = <?php echo wp_json_encode(PanelStyleSettings::SHADOWS); ?>;
    var fonts   = <?php echo wp_json_encode(PanelStyleSettings::FONT_FAMILIES); ?>;
    var p       = document.getElementById('bm-style-preview');
    if (!p) return;

    function css(prop, val) {
        p.style.setProperty(prop, val);
    }

    // Update hex labels next to color pickers
    function bindColor(id, cssProp) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function () {
            css(cssProp, el.value);
            var hex = el.closest('.bm-style-form-group')?.querySelector('.color-hex');
            if (hex) hex.textContent = el.value;
            updateHeader();
        });
    }

    function updateHeader() {
        var grad    = document.querySelector('[name=bm_ui_header_gradient]');
        var primary = document.getElementById('bm-ui-primary-color');
        var hover   = document.getElementById('bm-ui-primary-hover-color');
        if (!grad || !primary || !hover) return;
        var bg = grad.checked
            ? 'linear-gradient(135deg,' + primary.value + ',' + hover.value + ')'
            : primary.value;
        css('--bmp-header-bg', bg);
    }

    bindColor('bm-ui-primary-color',     '--bmp-primary');
    bindColor('bm-ui-primary-hover-color','--bmp-hover');
    bindColor('bm-ui-badge-color',       '--bmp-badge-bg');
    bindColor('bm-ui-badge-text-color',  '--bmp-badge-text');
    bindColor('bm-ui-btn-text-color',    '--bmp-btn-text');
    bindColor('bm-ui-link-color',        '--bmp-link');
    bindColor('bm-ui-text-color',        '--bmp-text');
    bindColor('bm-ui-heading-color',     '--bmp-heading');
    bindColor('bm-ui-surface-color',     '--bmp-surface');
    bindColor('bm-ui-background-color',  '--bmp-bg');
    bindColor('bm-ui-border-color',      '--bmp-border');

    // Radius slider
    var rng = document.getElementById('bm-ui-radius');
    if (rng) {
        rng.addEventListener('input', function () {
            css('--bmp-radius', rng.value + 'px');
        });
    }

    // Shadow select
    var shd = document.getElementById('bm-ui-shadow');
    if (shd) {
        shd.addEventListener('change', function () {
            css('--bmp-shadow', shadows[shd.value] || 'none');
        });
    }

    // Font select
    var fnt = document.getElementById('bm-ui-font-family');
    if (fnt) {
        fnt.addEventListener('change', function () {
            css('--bmp-font', fonts[fnt.value] || 'sans-serif');
        });
    }

    // Gradient checkbox
    var grd = document.querySelector('[name=bm_ui_header_gradient]');
    if (grd) {
        grd.addEventListener('change', updateHeader);
    }

    // Preset buttons
    document.querySelectorAll('[data-bm-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = JSON.parse(btn.dataset.bmPreset || '{}');

            // Update form fields
            var map = {
                'bm-ui-primary-color':       'primary_color',
                'bm-ui-primary-hover-color': 'primary_hover',
                'bm-ui-badge-color':         'badge_color',
                'bm-ui-badge-text-color':    'badge_text_color',
                'bm-ui-btn-text-color':      'btn_text_color',
                'bm-ui-link-color':          'link_color',
                'bm-ui-text-color':          'text_color',
                'bm-ui-heading-color':       'heading_color',
                'bm-ui-surface-color':       'surface_color',
                'bm-ui-background-color':    'background',
                'bm-ui-border-color':        'border_color',
            };
            for (var id in map) {
                var el = document.getElementById(id);
                if (el && d[map[id]]) {
                    el.value = d[map[id]];
                    el.dispatchEvent(new Event('input'));
                }
            }

            // Radius
            var rv = document.getElementById('bm-ui-radius');
            if (rv && d.radius) {
                rv.value = d.radius;
                document.getElementById('bm-radius-val').textContent = d.radius;
                rv.dispatchEvent(new Event('input'));
            }

            // Shadow
            if (shd && d.shadow) { shd.value = d.shadow; shd.dispatchEvent(new Event('change')); }

            // Font
            if (fnt && d.font_family) { fnt.value = d.font_family; fnt.dispatchEvent(new Event('change')); }

            // Gradient
            if (grd) { grd.checked = (d.header_gradient === '1'); grd.dispatchEvent(new Event('change')); }

            // Preset key
            var pk = document.getElementById('bm-ui-style-preset');
            if (pk && d.key) pk.value = d.key;

            // Highlight active preset button
            document.querySelectorAll('[data-bm-preset]').forEach(function (b) {
                b.classList.remove('button-primary');
                b.classList.add('button');
            });
            btn.classList.add('button-primary');
        });
    });

    // Init preview vars from current values on page load
    (function initPreview() {
        var colorMap = {
            'bm-ui-primary-color':       '--bmp-primary',
            'bm-ui-primary-hover-color': '--bmp-hover',
            'bm-ui-badge-color':         '--bmp-badge-bg',
            'bm-ui-badge-text-color':    '--bmp-badge-text',
            'bm-ui-btn-text-color':      '--bmp-btn-text',
            'bm-ui-link-color':          '--bmp-link',
            'bm-ui-text-color':          '--bmp-text',
            'bm-ui-heading-color':       '--bmp-heading',
            'bm-ui-surface-color':       '--bmp-surface',
            'bm-ui-background-color':    '--bmp-bg',
            'bm-ui-border-color':        '--bmp-border',
        };
        for (var id in colorMap) {
            var el = document.getElementById(id);
            if (el) css(colorMap[id], el.value);
        }
        if (rng) css('--bmp-radius', rng.value + 'px');
        if (shd) css('--bmp-shadow', shadows[shd.value] || 'none');
        if (fnt) css('--bmp-font',   fonts[fnt.value]   || 'sans-serif');
        updateHeader();
    })();
})();
</script>

<?php /* ══════════════════════════════════════════ POWIADOMIENIA TAB ══ */ ?>
<?php elseif ($current_tab === 'powiadomienia'): ?>

    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">🔔 <?php esc_html_e('Konfiguracja powiadomień', 'basemgmt'); ?></h2>
        <p class="description" style="margin:0 0 14px;">
            <?php esc_html_e('Ustawienia powiadomień email oraz blokad kont kadry.', 'basemgmt'); ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_settings'); ?>
            <input type="hidden" name="action" value="bm_save_settings">
            <input type="hidden" name="_bm_current_tab" value="<?php echo esc_attr($current_tab); ?>">
            <!-- Pass email settings fields as hidden so existing values are preserved -->
            <input type="hidden" name="from_name"          value="<?php echo esc_attr($s['from_name']); ?>">
            <input type="hidden" name="from_email"         value="<?php echo esc_attr($s['from_email']); ?>">
            <input type="hidden" name="admin_notify_email" value="<?php echo esc_attr($s['admin_notify_email']); ?>">
            <input type="hidden" name="header_color"       value="<?php echo esc_attr($s['header_color']); ?>">
            <input type="hidden" name="logo_url"           value="<?php echo esc_attr($s['logo_url']); ?>">
            <input type="hidden" name="header_title"       value="<?php echo esc_attr($s['header_title']); ?>">
            <input type="hidden" name="header_html"        value="<?php echo esc_attr($s['header_html']); ?>">
            <input type="hidden" name="footer_text"        value="<?php echo esc_attr($s['footer_text']); ?>">
            <table class="form-table" style="margin:0;">
                <tr>
                    <th><label for="bm-missing-emails"><?php esc_html_e('Email(e) dla brakujących meldunków', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="text" id="bm-missing-emails" name="missing_report_emails" class="regular-text"
                               value="<?php echo esc_attr((string) get_option('bm_missing_report_emails', '')); ?>">
                        <p class="description"><?php esc_html_e('Adresy rozdzielone przecinkami. Powiadomienie wysyłane gdy obóz nie przysłał meldunku dziennego.', 'basemgmt'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-report-emails"><?php esc_html_e('Email(e) dla cyklicznych raportów', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="text" id="bm-report-emails" name="report_emails" class="regular-text"
                               value="<?php echo esc_attr((string) get_option('bm_report_emails', '')); ?>">
                        <p class="description"><?php esc_html_e('Adresy rozdzielone przecinkami. Zostaw puste aby wyłączyć cykliczne raporty.', 'basemgmt'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-report-interval"><?php esc_html_e('Interwał raportów', 'basemgmt'); ?></label></th>
                    <td>
                        <select id="bm-report-interval" name="report_interval">
                            <?php
                            $current_interval = get_option('bm_report_interval', 'daily');
                            $intervals = [
                                'hourly'      => __('Co godzinę', 'basemgmt'),
                                'twicedaily'  => __('Dwa razy dziennie', 'basemgmt'),
                                'daily'       => __('Raz dziennie', 'basemgmt'),
                            ];
                            foreach ($intervals as $k => $v):
                            ?>
                            <option value="<?php echo esc_attr($k); ?>" <?php selected($current_interval, $k); ?>><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-lockout-minutes"><?php esc_html_e('Czas blokady konta kadry (minuty)', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="number" id="bm-lockout-minutes" name="lockout_minutes" min="1" max="1440"
                               value="<?php echo esc_attr((string) (int) get_option('bm_lockout_minutes', 15)); ?>"
                               style="width:80px;">
                        <p class="description">
                            <?php printf(
                                esc_html__('Po %d nieudanych próbach logowania konto kadry zostaje zablokowane na podany czas. Po 1 kolejnej próbie – blokada trwała.', 'basemgmt'),
                                BASEMGMT_MAX_ATTEMPTS
                            ); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Zapisz ustawienia powiadomień', 'basemgmt')); ?>
        </form>
    </div>

<?php /* ═══════════════════════════════════════════════════════ DANE TAB ══ */ ?>
<?php elseif ($current_tab === 'dane'): ?>

    <!-- Translations -->
    <div class="postbox" id="translations" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">🌐 <?php esc_html_e('Tłumaczenia', 'basemgmt'); ?></h2>
        <p class="description" style="margin:0 0 12px;">
            <?php esc_html_e('Pliki tłumaczeń (.po) znajdują się w folderze', 'basemgmt'); ?>
            <code><?php echo esc_html(basename(BASEMGMT_DIR)); ?>/languages/</code>.
            <?php esc_html_e('Edytuj pliki .po i kliknij "Kompiluj", aby wygenerować binarne pliki .mo wymagane przez WordPress.', 'basemgmt'); ?>
        </p>
        <?php
        $lang_dir = BASEMGMT_DIR . 'languages/';
        $po_files = glob($lang_dir . '*.po') ?: [];
        ?>
        <?php if ($po_files) : ?>
            <table class="wp-list-table widefat fixed striped" style="border:0;margin-bottom:12px;">
                <thead><tr>
                    <th><?php esc_html_e('Plik .po', 'basemgmt'); ?></th>
                    <th style="width:120px;"><?php esc_html_e('Plik .mo', 'basemgmt'); ?></th>
                    <th style="width:140px;"><?php esc_html_e('Data modyfikacji', 'basemgmt'); ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($po_files as $po) :
                        $mo      = substr($po, 0, -3) . '.mo';
                        $mo_ok   = file_exists($mo);
                        $po_time = filemtime($po);
                        $mo_time = $mo_ok ? filemtime($mo) : 0;
                        $outdated = $mo_ok && ($po_time > $mo_time);
                    ?>
                    <tr>
                        <td><code><?php echo esc_html(basename($po)); ?></code></td>
                        <td>
                            <?php if ($mo_ok && !$outdated) : ?>
                                <span style="color:#2a9d2a;">✓ <?php esc_html_e('Aktualny', 'basemgmt'); ?></span>
                            <?php elseif ($outdated) : ?>
                                <span style="color:#d63638;">⚠ <?php esc_html_e('Nieaktualny', 'basemgmt'); ?></span>
                            <?php else : ?>
                                <span style="color:#d63638;">✗ <?php esc_html_e('Brak .mo', 'basemgmt'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo esc_html(date_i18n('d.m.Y H:i', $po_time)); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_compile_mo'); ?>
            <input type="hidden" name="action" value="bm_compile_mo">
            <button type="submit" class="button button-primary">
                🔄 <?php esc_html_e('Kompiluj tłumaczenia (.po → .mo)', 'basemgmt'); ?>
            </button>
        </form>
        <p class="description" style="margin-top:8px;">
            <?php esc_html_e('Dostępne języki: pl_PL (polski), en_US (angielski). Zmień język witryny w Ustawienia > Ogólne.', 'basemgmt'); ?>
        </p>
    </div>

    <!-- Backup / Import / Clear -->
    <div class="postbox" id="backup" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">🗄 <?php esc_html_e('Zarządzanie danymi wtyczki', 'basemgmt'); ?></h2>
        <p class="description" style="margin:0 0 16px;">
            <?php esc_html_e('Wykonaj pełny backup danych wtyczki, przywróć dane z pliku backupu lub wyczyść wszystkie dane.', 'basemgmt'); ?>
        </p>

        <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #dcdcde;">
            <h3 style="margin:0 0 6px;"><?php esc_html_e('Pobierz backup', 'basemgmt'); ?></h3>
            <p class="description"><?php esc_html_e('Eksportuje wszystkie tabele wtyczki do pliku JSON.', 'basemgmt'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('bm_backup_data'); ?>
                <input type="hidden" name="action" value="bm_backup_data">
                <button type="submit" class="button button-secondary">
                    ⬇ <?php esc_html_e('Pobierz backup (JSON)', 'basemgmt'); ?>
                </button>
            </form>
        </div>

        <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #dcdcde;">
            <h3 style="margin:0 0 6px;"><?php esc_html_e('Importuj z backupu', 'basemgmt'); ?></h3>
            <p class="description" style="color:#d63638;font-weight:600;">
                ⚠ <?php esc_html_e('Uwaga: import nadpisuje istniejące dane. Zalecane wykonanie backupu przed importem.', 'basemgmt'); ?>
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('bm_import_data'); ?>
                <input type="hidden" name="action" value="bm_import_data">
                <input type="file" name="backup_file" accept=".json" required style="margin-right:8px;">
                <button type="submit" class="button button-secondary"
                    data-bm-confirm="<?php esc_attr_e('Czy na pewno chcesz importować dane? Istniejące dane zostaną nadpisane.', 'basemgmt'); ?>">
                    ⬆ <?php esc_html_e('Importuj backup', 'basemgmt'); ?>
                </button>
            </form>
        </div>

        <div>
            <h3 style="margin:0 0 6px;"><?php esc_html_e('Wyczyść wszystkie dane', 'basemgmt'); ?></h3>
            <p class="description" style="color:#d63638;font-weight:600;">
                ⚠ <?php esc_html_e('Niebezpieczna operacja: permanentnie usuwa WSZYSTKIE dane wtyczki ze wszystkich tabel. Nie można cofnąć.', 'basemgmt'); ?>
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('bm_clear_data'); ?>
                <input type="hidden" name="action" value="bm_clear_data">
                <button type="submit" class="button bm-danger"
                    data-bm-confirm="<?php esc_attr_e('UWAGA! Ta operacja jest nieodwracalna i usunie WSZYSTKIE dane wtyczki. Czy na pewno chcesz kontynuować?', 'basemgmt'); ?>">
                    🗑 <?php esc_html_e('Wyczyść wszystkie dane', 'basemgmt'); ?>
                </button>
            </form>
        </div>
    </div>

<?php /* ═══════════════════════════════════════════════════════ INFO TAB ══ */ ?>
<?php elseif ($current_tab === 'info'): ?>

    <div class="postbox" style="max-width:700px;padding:16px 20px;">
        <h2 class="hndle" style="padding:0 0 10px;"><?php esc_html_e('O pluginie', 'basemgmt'); ?></h2>
        <p><?php printf(esc_html__('Baza Obozowa v%s', 'basemgmt'), esc_html(BASEMGMT_VERSION)); ?></p>
        <ul style="margin:8px 0;padding-left:20px;color:#555;font-size:13px;">
            <li><?php esc_html_e('Tabele danych: bm_camps, bm_staff, bm_daily_counts, bm_announcements, bm_sessions, bm_weather_alerts, bm_plan_headers, bm_plan_items, bm_resources, bm_resource_reservations, bm_resource_blocks', 'basemgmt'); ?></li>
            <li><?php esc_html_e('REST API: /wp-json/bm/v1/...', 'basemgmt'); ?></li>
            <li><?php esc_html_e('Wymagania: WordPress 6.0+, PHP 8.1+, MySQL InnoDB', 'basemgmt'); ?></li>
        </ul>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-logs')); ?>" class="button">
                🗒 <?php esc_html_e('Logi operacji', 'basemgmt'); ?>
            </a>
        </p>
    </div>

<?php endif; ?>
</div><!-- /.wrap -->

<script>
(function() {
    var cmSettings = <?php echo wp_json_encode($cm_settings ?: new stdClass()); ?>;
    if (typeof wp === 'undefined' || !wp.codeEditor || !Object.keys(cmSettings).length) return;
    ['bm-header-html', 'bm-footer'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) wp.codeEditor.initialize(el, cmSettings);
    });
})();
</script>
