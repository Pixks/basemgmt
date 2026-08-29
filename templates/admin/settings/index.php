<?php
defined('ABSPATH') || exit;
use BaseMgmt\Core\EmailService;
use BaseMgmt\Core\EmailTemplateRepository;
use BaseMgmt\Core\OperationLogger;
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
$valid_tabs  = ['email', 'pdf', 'wyglad', 'powiadomienia', 'dane', 'shortcodes', 'logi', 'info'];
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
	'shortcodes'    => '[ ]  ' . __('Shortcodes', 'basemgmt'),
	'logi'          => __('Logi', 'basemgmt'),
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
	'custom'    => __('Własna czcionka', 'basemgmt'),
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
    border-radius: var(--bmp-radius-pill, 999px);
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
    border-radius: var(--bmp-radius-pill, 999px);
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
.bm-prev-tabs {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}
.bm-prev-tab {
    padding: 5px 12px;
    font-size: .78rem;
    font-weight: 600;
    border: 1px solid #c9d0d8;
    border-radius: 4px;
    background: #f6f7f9;
    color: #444;
    cursor: pointer;
}
.bm-prev-tab.active {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}
.bm-prev-pane { display: none; }
.bm-prev-pane.active { display: block; }
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
                    <th style="width:220px;"><label for="bm-ui-btn-radius"><?php esc_html_e('Zaokrąglenie przycisków / etykiet (px)', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="range" id="bm-ui-btn-radius" min="0" max="32" step="1"
                               value="<?php echo esc_attr(min(32, (int)$ui_style['btn_radius'])); ?>"
                               style="width:160px;vertical-align:middle;">
                        <input type="hidden" id="bm-ui-btn-radius-actual" name="bm_ui_btn_radius"
                               value="<?php echo esc_attr($ui_style['btn_radius']); ?>">
                        <strong id="bm-btn-radius-val" style="margin-left:6px;font-family:monospace;">
                            <?php
                            $bv = (int)$ui_style['btn_radius'];
                            echo $bv >= 32 ? esc_html__('Pill', 'basemgmt') : esc_html((string)$bv);
                            ?>
                        </strong> px
                        <span class="description" style="display:block;margin-top:3px;"><?php esc_html_e('Przesuń na max = styl pill (999px). Dotyczy przycisków, badge i tagów.', 'basemgmt'); ?></span>
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
                <tr id="bm-row-custom-font-url">
                    <th><label for="bm-ui-custom-font-url"><?php esc_html_e('URL własnej czcionki', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="url" id="bm-ui-custom-font-url" name="bm_ui_custom_font_url" class="large-text"
                               value="<?php echo esc_attr($ui_style['custom_font_url']); ?>"
                               placeholder="https://fonts.googleapis.com/css2?family=...">
                        <p class="description"><?php esc_html_e('URL do pliku CSS z czcionką (np. Google Fonts). Wklej link z atrybutu href tagu <link>.', 'basemgmt'); ?></p>
                    </td>
                </tr>
                <tr id="bm-row-custom-font-name">
                    <th><label for="bm-ui-custom-font-name"><?php esc_html_e('Nazwa własnej czcionki', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="text" id="bm-ui-custom-font-name" name="bm_ui_custom_font_name" class="regular-text"
                               value="<?php echo esc_attr($ui_style['custom_font_name']); ?>"
                               placeholder="<?php esc_attr_e('np. Montserrat', 'basemgmt'); ?>">
                        <p class="description"><?php esc_html_e('Dokładna nazwa font-family, np. "Montserrat". Wymagana gdy wybrana czcionka to "Własna".', 'basemgmt'); ?></p>
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
            <p class="description" style="margin:0 0 10px;font-size:.8rem;">
                <?php esc_html_e('Zmiany widoczne są natychmiast – kliknij "Zapisz" aby zastosować.', 'basemgmt'); ?>
            </p>
            <div class="bm-prev-tabs">
                <button type="button" class="bm-prev-tab active" data-pane="prev-announcements"><?php esc_html_e('Aktualności', 'basemgmt'); ?></button>
                <button type="button" class="bm-prev-tab" data-pane="prev-login"><?php esc_html_e('Logowanie', 'basemgmt'); ?></button>
                <button type="button" class="bm-prev-tab" data-pane="prev-schedule"><?php esc_html_e('Plan dnia', 'basemgmt'); ?></button>
                <button type="button" class="bm-prev-tab" data-pane="prev-reservations"><?php esc_html_e('Rezerwacje', 'basemgmt'); ?></button>
            </div>
            <div id="bm-style-preview">

                <!-- Pane: Aktualności -->
                <div id="prev-announcements" class="bm-prev-pane active">
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
                </div><!-- /prev-announcements -->

                <!-- Pane: Logowanie -->
                <div id="prev-login" class="bm-prev-pane">
                    <div class="prev-section-title"><?php esc_html_e('PANEL KADRY', 'basemgmt'); ?></div>
                    <div class="prev-card">
                        <div class="prev-card-header">
                            <span>🔐 <?php esc_html_e('Logowanie', 'basemgmt'); ?></span>
                        </div>
                        <div class="prev-card-body">
                            <div style="margin-bottom:10px;">
                                <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e('Adres e-mail', 'basemgmt'); ?></label>
                                <input type="text" readonly value="kadra@oboz.pl" style="width:100%;padding:7px 10px;border:1px solid var(--bmp-border,#E0E6E0);border-radius:6px;font-size:.88rem;background:var(--bmp-surface,#fff);">
                            </div>
                            <div style="margin-bottom:14px;">
                                <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e('Hasło', 'basemgmt'); ?></label>
                                <input type="text" readonly value="••••••••" style="width:100%;padding:7px 10px;border:1px solid var(--bmp-border,#E0E6E0);border-radius:6px;font-size:.88rem;background:var(--bmp-surface,#fff);">
                            </div>
                            <div class="prev-actions">
                                <button class="prev-btn" type="button"><?php esc_html_e('Zaloguj się', 'basemgmt'); ?></button>
                            </div>
                            <div style="margin-top:10px;font-size:.8rem;">
                                <a href="#" class="prev-read-more" onclick="return false;"><?php esc_html_e('Nie pamiętam hasła', 'basemgmt'); ?></a>
                            </div>
                        </div>
                    </div>
                </div><!-- /prev-login -->

                <!-- Pane: Plan dnia -->
                <div id="prev-schedule" class="bm-prev-pane">
                    <div class="prev-section-title"><?php esc_html_e('PLAN DNIA', 'basemgmt'); ?></div>
                    <?php
                    $sched = [
                        ['07:30', __('Pobudka i poranna zbiórka', 'basemgmt'),      __('Obowiązkowa', 'basemgmt')],
                        ['09:00', __('Śniadanie', 'basemgmt'),                      __('Stołówka', 'basemgmt')],
                        ['10:30', __('Gra terenowa – Las Północny', 'basemgmt'),    __('Aktywność', 'basemgmt')],
                        ['13:00', __('Obiad', 'basemgmt'),                          __('Stołówka', 'basemgmt')],
                        ['15:00', __('Czas wolny / warsztaty', 'basemgmt'),         __('Opcjonalne', 'basemgmt')],
                        ['19:00', __('Kolacja i wieczorny apel', 'basemgmt'),       __('Obowiązkowa', 'basemgmt')],
                    ];
                    ?>
                    <div class="prev-card">
                        <div class="prev-card-header">
                            <span>📅 <?php esc_html_e('Czwartek, 28 sierpnia', 'basemgmt'); ?></span>
                        </div>
                        <div class="prev-card-body" style="padding:8px 16px;">
                            <?php foreach ($sched as [$time, $title, $cat]): ?>
                            <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--bmp-border,#E0E6E0);">
                                <span style="font-size:.78rem;font-weight:700;color:var(--bmp-primary,#6EA82E);min-width:36px;"><?php echo esc_html($time); ?></span>
                                <span style="flex:1;font-size:.88rem;"><?php echo esc_html($title); ?></span>
                                <span class="prev-tag"><?php echo esc_html($cat); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div><!-- /prev-schedule -->

                <!-- Pane: Rezerwacje -->
                <div id="prev-reservations" class="bm-prev-pane">
                    <div class="prev-section-title"><?php esc_html_e('REZERWACJE', 'basemgmt'); ?></div>
                    <div class="prev-card">
                        <div class="prev-card-header">
                            <span>🏕 <?php esc_html_e('Lista rezerwacji', 'basemgmt'); ?></span>
                        </div>
                        <div class="prev-card-body" style="padding:8px 16px;">
                            <?php
                            $resv = [
                                [__('Kowalski Jan', 'basemgmt'),     __('Uczestnik', 'basemgmt'),   __('Opłacona', 'basemgmt')],
                                [__('Nowak Anna', 'basemgmt'),       __('Uczestnik', 'basemgmt'),   __('Oczekuje', 'basemgmt')],
                                [__('Wiśniewska Ola', 'basemgmt'),  __('Kadra', 'basemgmt'),       __('Opłacona', 'basemgmt')],
                            ];
                            ?>
                            <?php foreach ($resv as [$name, $role, $status]): ?>
                            <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--bmp-border,#E0E6E0);">
                                <span style="flex:1;font-size:.88rem;font-weight:600;"><?php echo esc_html($name); ?></span>
                                <span class="prev-tag"><?php echo esc_html($role); ?></span>
                                <span class="prev-tag" style="background:var(--bmp-surface,#fff);color:var(--bmp-text,#333);border:1px solid var(--bmp-border,#E0E6E0);"><?php echo esc_html($status); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="prev-actions" style="margin-top:12px;">
                                <button class="prev-btn" type="button"><?php esc_html_e('Nowa rezerwacja', 'basemgmt'); ?> +</button>
                                <button class="prev-btn prev-btn-ghost" type="button"><?php esc_html_e('Eksportuj', 'basemgmt'); ?></button>
                            </div>
                        </div>
                    </div>
                </div><!-- /prev-reservations -->

            </div><!-- /bm-style-preview -->
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

    // Card radius slider
    var rng = document.getElementById('bm-ui-radius');
    if (rng) {
        rng.addEventListener('input', function () {
            css('--bmp-radius', rng.value + 'px');
        });
    }

    // Button / badge radius slider
    var btnRng    = document.getElementById('bm-ui-btn-radius');
    var btnActual = document.getElementById('bm-ui-btn-radius-actual');
    var btnLabel  = document.getElementById('bm-btn-radius-val');
    function updateBtnRadius() {
        var v = parseInt(btnRng.value, 10);
        var actual = (v >= 32) ? 999 : v;
        if (btnActual) btnActual.value = actual;
        if (btnLabel)  btnLabel.textContent = (v >= 32) ? '<?php echo esc_js(__('Pill', 'basemgmt')); ?>' : v;
        css('--bmp-radius-pill', actual + 'px');
    }
    if (btnRng) {
        btnRng.addEventListener('input', updateBtnRadius);
    }

    // Shadow select
    var shd = document.getElementById('bm-ui-shadow');
    if (shd) {
        shd.addEventListener('change', function () {
            css('--bmp-shadow', shadows[shd.value] || 'none');
        });
    }

    // Font select + custom font rows visibility
    var fnt         = document.getElementById('bm-ui-font-family');
    var rowFontUrl  = document.getElementById('bm-row-custom-font-url');
    var rowFontName = document.getElementById('bm-row-custom-font-name');
    var fontName    = document.getElementById('bm-ui-custom-font-name');
    function updateFontPreview() {
        if (!fnt) return;
        var isCustom = fnt.value === 'custom';
        if (rowFontUrl)  rowFontUrl.style.display  = isCustom ? '' : 'none';
        if (rowFontName) rowFontName.style.display  = isCustom ? '' : 'none';
        if (isCustom && fontName && fontName.value) {
            css('--bmp-font', '"' + fontName.value + '", sans-serif');
        } else {
            css('--bmp-font', fonts[fnt.value] || 'sans-serif');
        }
    }
    if (fnt) {
        fnt.addEventListener('change', updateFontPreview);
    }
    if (fontName) {
        fontName.addEventListener('input', updateFontPreview);
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
                if (el && map[id] in d && d[map[id]] !== undefined) {
                    el.value = d[map[id]];
                    el.dispatchEvent(new Event('input'));
                }
            }

            // Card radius
            var rv = document.getElementById('bm-ui-radius');
            if (rv && d.radius) {
                rv.value = d.radius;
                document.getElementById('bm-radius-val').textContent = d.radius;
                rv.dispatchEvent(new Event('input'));
            }

            // Button radius
            if (btnRng && d.btn_radius !== undefined) {
                var bv = Math.min(32, parseInt(d.btn_radius, 10));
                btnRng.value = bv;
                updateBtnRadius();
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

    // Preview tab switching
    document.querySelectorAll('.bm-prev-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.bm-prev-tab').forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.bm-prev-pane').forEach(function (pane) { pane.classList.remove('active'); });
            tab.classList.add('active');
            var target = document.getElementById(tab.dataset.pane);
            if (target) target.classList.add('active');
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
        if (rng)    css('--bmp-radius', rng.value + 'px');
        if (btnRng) updateBtnRadius();
        if (shd)    css('--bmp-shadow', shadows[shd.value] || 'none');
        updateFontPreview();
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

<?php /* ═══════════════════════════════════════════════ SHORTCODES TAB ══ */ ?>
<?php elseif ($current_tab === 'shortcodes'): ?>

    <div class="postbox" style="max-width:900px;padding:20px 24px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 12px;">[ ] <?php esc_html_e('Lista shortcode\'ów', 'basemgmt'); ?></h2>
        <p style="font-size:13px;color:#444;margin:0 0 16px;">
            <?php esc_html_e('Poniżej znajdziesz wszystkie dostępne shortcode\'y wtyczki CampLink wraz z opisem. Możesz ich używać na dowolnych stronach lub postach WordPress.', 'basemgmt'); ?>
        </p>
        <?php
        // Each entry: [ 'desc' => '...', 'params' => [ 'name' => 'opis' ] ]
        $shortcode_groups = [
            __('Inicjalizacja i stan sesji', 'basemgmt') => [
                '[bm_init]' => [
                    'desc'   => __('Inicjalizuje frontend wtyczki (skrypty, style). Umieść na każdej stronie, która używa panelu.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_auth_state]' => [
                    'desc'   => __('Renderuje blok warunkowy zależny od stanu zalogowania. Używany wewnętrznie przez inne shortcody.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_session_guard]' => [
                    'desc'   => __('Blokuje lub pokazuje zawartość w zależności od stanu zalogowania. Opakowuje inne shortcody.', 'basemgmt'),
                    'params' => [
                        'logged'   => __('Domyślnie <code>1</code> – pokaż zalogowanym. Ustaw <code>0</code>, aby pokazać tylko niezalogowanym.', 'basemgmt'),
                        'redirect' => __('URL przekierowania – zalogowani (lub niezalogowani, zależnie od <code>logged</code>) zostaną automatycznie przeniesieni pod wskazany adres.', 'basemgmt'),
                    ],
                ],
                '[bm_panel_element]' => [
                    'desc'   => __('Renderuje dowolny element panelu po nazwie. Zaawansowane użycie.', 'basemgmt'),
                    'params' => [
                        'type' => __('Nazwa elementu, np. <code>login</code>, <code>announcements</code>. Domyślnie: <code>login</code>.', 'basemgmt'),
                    ],
                ],
            ],
            __('Uwierzytelnianie', 'basemgmt') => [
                '[bm_panel_login]' => [
                    'desc'   => __('Formularz logowania kadry do panelu.', 'basemgmt'),
                    'params' => [
                        'redirect_url' => __('URL przekierowania po pomyślnym zalogowaniu, np. <code>redirect_url="/panel"</code>.', 'basemgmt'),
                    ],
                ],
                '[bm_panel_logout]' => [
                    'desc'   => __('Przycisk / link wylogowania z panelu.', 'basemgmt'),
                    'params' => [],
                ],
            ],
            __('Obóz – informacje ogólne', 'basemgmt') => [
                '[bm_panel_camp_header]' => [
                    'desc'   => __('Nagłówek obozu: nazwa, lokalizacja, daty.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_unread_counter]' => [
                    'desc'   => __('Licznik nieprzeczytanych powiadomień i wiadomości.', 'basemgmt'),
                    'params' => [],
                ],
            ],
            __('Aktualności i meldunki', 'basemgmt') => [
                '[bm_panel_announcements]' => [
                    'desc'   => __('Lista aktualności / komunikatów dla kadry.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_announcement_form]' => [
                    'desc'   => __('Formularz dodawania nowej aktualności.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_reports]' => [
                    'desc'   => __('Lista meldunków dziennych i formularz ich składania.', 'basemgmt'),
                    'params' => [],
                ],
            ],
            __('Plan dnia i jadłospis', 'basemgmt') => [
                '[bm_panel_schedule]' => [
                    'desc'   => __('Plan dnia obozu (widok listy aktywności z godzinami).', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_menu_day]' => [
                    'desc'   => __('Jadłospis na bieżący dzień.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_menu_week]' => [
                    'desc'   => __('Jadłospis tygodniowy.', 'basemgmt'),
                    'params' => [],
                ],
            ],
            __('Rezerwacje', 'basemgmt') => [
                '[bm_panel_reservations]' => [
                    'desc'   => __('Lista rezerwacji zasobów i formularz nowej rezerwacji.', 'basemgmt'),
                    'params' => [],
                ],
            ],
            __('Wiadomości / konwersacje', 'basemgmt') => [
                '[bm_panel_conversations]' => [
                    'desc'   => __('Lista wątków konwersacji kadry.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_conversation_new]' => [
                    'desc'   => __('Formularz nowej wiadomości / wątku.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_conversation_thread]' => [
                    'desc'   => __('Widok pojedynczego wątku konwersacji.', 'basemgmt'),
                    'params' => [],
                ],
            ],
            __('Baza wiedzy', 'basemgmt') => [
                '[bm_panel_help_list]' => [
                    'desc'   => __('Lista artykułów bazy wiedzy.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_help_article]' => [
                    'desc'   => __('Widok pojedynczego artykułu bazy wiedzy.', 'basemgmt'),
                    'params' => [],
                ],
            ],
            __('Formularze i zgłoszenia', 'basemgmt') => [
                '[bm_panel_forms_list]' => [
                    'desc'   => __('Lista dostępnych formularzy zgłoszeniowych.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_form]' => [
                    'desc'   => __('Widok i wypełnianie konkretnego formularza.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_submissions_list]' => [
                    'desc'   => __('Lista złożonych zgłoszeń.', 'basemgmt'),
                    'params' => [],
                ],
                '[bm_panel_submission]' => [
                    'desc'   => __('Szczegóły pojedynczego zgłoszenia.', 'basemgmt'),
                    'params' => [],
                ],
            ],
            __('Pogoda', 'basemgmt') => [
                '[bm_panel_weather]' => [
                    'desc'   => __('Widżet pogody dla lokalizacji obozu.', 'basemgmt'),
                    'params' => [],
                ],
            ],
            __('Shortcody legacy (zachowane dla wstecznej zgodności)', 'basemgmt') => [
                '[camp_panel]'         => [ 'desc' => __('Alias [bm_init] – inicjalizacja panelu.', 'basemgmt'), 'params' => [] ],
                '[camp_access]'        => [ 'desc' => __('Alias [bm_init].', 'basemgmt'), 'params' => [] ],
                '[camp_overview]'      => [ 'desc' => __('Alias [bm_init].', 'basemgmt'), 'params' => [] ],
                '[camp_announcements]' => [ 'desc' => __('Alias [bm_init].', 'basemgmt'), 'params' => [] ],
                '[camp_daily_count]'   => [ 'desc' => __('Alias [bm_init].', 'basemgmt'), 'params' => [] ],
            ],
        ];
        foreach ($shortcode_groups as $group_name => $items):
        ?>
        <h3 style="font-size:13px;font-weight:700;margin:20px 0 8px;padding-bottom:4px;border-bottom:1px solid #e0e0e0;">
            <?php echo esc_html($group_name); ?>
        </h3>
        <table class="wp-list-table widefat fixed" style="border:0;margin-bottom:8px;">
            <thead>
            <tr>
                <th style="width:260px;padding:6px 12px;font-size:12px;font-weight:600;color:#646970;"><?php esc_html_e('Shortcode', 'basemgmt'); ?></th>
                <th style="width:40%;padding:6px 12px;font-size:12px;font-weight:600;color:#646970;"><?php esc_html_e('Opis', 'basemgmt'); ?></th>
                <th style="padding:6px 12px;font-size:12px;font-weight:600;color:#646970;"><?php esc_html_e('Parametry', 'basemgmt'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $tag => $item): ?>
            <tr>
                <td style="padding:7px 12px;font-family:monospace;font-size:13px;white-space:nowrap;background:#f6f7f7;vertical-align:top;">
                    <?php echo esc_html($tag); ?>
                </td>
                <td style="padding:7px 12px;font-size:13px;color:#444;vertical-align:top;"><?php echo esc_html($item['desc']); ?></td>
                <td style="padding:7px 12px;font-size:12px;color:#444;vertical-align:top;">
                    <?php if ( empty($item['params']) ): ?>
                        <span style="color:#aaa;">—</span>
                    <?php else: ?>
                        <dl style="margin:0;padding:0;">
                        <?php foreach ($item['params'] as $param => $param_desc): ?>
                            <dt style="font-family:monospace;font-weight:600;margin:0 0 2px;"><?php echo esc_html($param); ?></dt>
                            <dd style="margin:0 0 8px;padding-left:10px;color:#555;"><?php echo wp_kses($param_desc, ['code' => []]); ?></dd>
                        <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>
    </div>

<?php /* ══════════════════════════════════════════════════════ LOGI TAB ══ */ ?>
<?php elseif ($current_tab === 'logi'): ?>

    <?php
    $filter_action    = sanitize_key($_GET['filter_action'] ?? '');
    $filter_date_from = sanitize_text_field($_GET['filter_date_from'] ?? '');
    $filter_date_to   = sanitize_text_field($_GET['filter_date_to']   ?? '');
    $page             = max(1, (int) ($_GET['paged'] ?? 1));
    $per_page         = 50;

    $log_filters = [];
    if ( $filter_action )    $log_filters['action']    = $filter_action;
    if ( $filter_date_from ) $log_filters['date_from'] = $filter_date_from;
    if ( $filter_date_to )   $log_filters['date_to']   = $filter_date_to;

    $logs         = OperationLogger::get_all($log_filters, $per_page, $page);
    $total        = OperationLogger::count($log_filters);
    $pages        = (int) ceil($total / $per_page);
    $action_types = OperationLogger::get_action_types();

    $bm_embedded = true;
    include BASEMGMT_DIR . 'templates/admin/logs/list.php';
    ?>

<?php /* ═══════════════════════════════════════════════════════ INFO TAB ══ */ ?>
<?php elseif ($current_tab === 'info'): ?>

    <?php /* ── About the plugin ── */ ?>
    <div class="postbox" style="max-width:780px;padding:20px 24px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 12px;">ℹ️ <?php esc_html_e('CampLink – Baza Obozowa', 'basemgmt'); ?></h2>
        <p style="font-size:14px;font-weight:600;margin:0 0 8px;">
            <?php printf(esc_html__('Wersja %s', 'basemgmt'), esc_html(BASEMGMT_VERSION)); ?>
        </p>
        <p style="font-size:13px;color:#444;line-height:1.6;margin:0 0 10px;">
            <?php esc_html_e('CampLink to kompleksowy system zarządzania obozami harcerskimi i koloniami, zintegrowany bezpośrednio z WordPress. Wtyczka zapewnia narzędzia dla organizatorów i kadry obozowej: ewidencję obozów, zarządzanie kadrą, meldunki dzienne, jadłospis, plan dnia, rezerwacje zasobów, komunikację wewnętrzną, formularze zgłoszeniowe oraz eksport dokumentów PDF.', 'basemgmt'); ?>
        </p>
        <p style="font-size:13px;color:#444;line-height:1.6;margin:0;">
            <?php esc_html_e('Wtyczka jest przeznaczona dla komend hufców, komend chorągwi oraz innych organizacji prowadzących wypoczynek dla dzieci i młodzieży. Interfejs frontendowy (panel kadry) dostępny jest za pomocą shortcode\'ów [bm_panel_*] i może być stylizowany zgodnie z identyfikacją wizualną organizacji.', 'basemgmt'); ?>
        </p>
    </div>

    <?php /* ── Requirements & tables ── */ ?>
    <div class="postbox" style="max-width:780px;padding:20px 24px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 12px;">⚙️ <?php esc_html_e('Wymagania systemowe', 'basemgmt'); ?></h2>
        <table style="border-collapse:collapse;font-size:13px;width:100%;max-width:480px;">
            <tbody>
                <?php
                $reqs = [
                    [ __('WordPress', 'basemgmt'),  '6.2+' ],
                    [ __('PHP', 'basemgmt'),         '8.1+' ],
                    [ __('MySQL', 'basemgmt'),        '5.7+ / MariaDB 10.3+' ],
                    [ __('Silnik tabel', 'basemgmt'), 'InnoDB' ],
                    [ __('REST API', 'basemgmt'),     '/wp-json/bm/v1/…' ],
                ];
                foreach ( $reqs as [$label, $value] ):
                ?>
                <tr>
                    <td style="padding:5px 16px 5px 0;color:#666;white-space:nowrap;"><?php echo esc_html($label); ?></td>
                    <td style="padding:5px 0;font-weight:600;color:#222;"><?php echo esc_html($value); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php /* ── Database tables ── */ ?>
    <div class="postbox" style="max-width:780px;padding:20px 24px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 12px;">🗄 <?php esc_html_e('Tabele bazy danych', 'basemgmt'); ?></h2>
        <?php
        $table_groups = [
            __('Obozy', 'basemgmt') => [
                'bm_camps', 'bm_camp_cases', 'bm_camp_case_history', 'bm_camp_organizers',
                'bm_camp_checklist_items', 'bm_camp_workflow_events', 'bm_camp_prearrival',
                'bm_camp_documents', 'bm_camp_document_versions', 'bm_camp_payment_schedules',
                'bm_camp_payments', 'bm_camp_actual_stays', 'bm_camp_actual_meals',
                'bm_camp_service_usages', 'bm_camp_pricing_tables', 'bm_camp_pricing_rules',
                'bm_camp_settlements', 'bm_camp_settlement_lines', 'bm_camp_settlement_issues',
                'bm_camp_closures', 'bm_camp_equipment', 'bm_camp_declarations',
                'bm_camp_damages', 'bm_camp_decl_docs',
            ],
            __('Kadra i sesje', 'basemgmt') => [
                'bm_staff', 'bm_sessions', 'bm_daily_counts',
            ],
            __('Komunikacja i ogłoszenia', 'basemgmt') => [
                'bm_announcements', 'bm_announcement_camps',
                'bm_conv_threads', 'bm_conv_messages',
            ],
            __('Plan dnia i jadłospis', 'basemgmt') => [
                'bm_plan_headers', 'bm_plan_items', 'bm_plan_item_revisions', 'bm_plan_camps',
                'bm_plan_templates', 'bm_plan_template_items',
                'bm_meal_days', 'bm_meal_items', 'bm_meal_diets', 'bm_meal_diet_costs',
                'bm_meal_locations', 'bm_meal_templates', 'bm_meal_template_items',
            ],
            __('Rezerwacje i zasoby', 'basemgmt') => [
                'bm_resources', 'bm_resource_reservations', 'bm_resource_blocks',
            ],
            __('Formularze', 'basemgmt') => [
                'bm_forms', 'bm_form_fields', 'bm_form_camps',
                'bm_submissions', 'bm_submission_attachments', 'bm_submission_history',
            ],
            __('Organizacja', 'basemgmt') => [
                'bm_doc_templates', 'bm_doc_library', 'bm_doc_attachments',
                'bm_payment_packages', 'bm_payment_package_lines', 'bm_payment_pkg_accom',
                'bm_payment_pkg_diet_slots', 'bm_task_templates',
                'bm_accommodation_types', 'bm_camp_declaration_days',
                'bm_camp_declaration_diet_lines', 'bm_camp_declaration_accommodation_lines',
                'bm_decl_templates',
            ],
            __('Pogoda, pomoc i inne', 'basemgmt') => [
                'bm_weather_alerts', 'bm_help_articles', 'bm_operation_logs',
            ],
        ];
        ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
        <?php foreach ( $table_groups as $group_name => $tables ): ?>
            <div>
                <p style="font-weight:600;font-size:12px;text-transform:uppercase;color:#666;margin:0 0 4px;"><?php echo esc_html($group_name); ?></p>
                <ul style="margin:0;padding:0;list-style:none;">
                    <?php foreach ( $tables as $tbl ): ?>
                    <li style="font-size:12px;color:#333;padding:2px 0;"><code style="background:#f3f4f6;padding:1px 5px;border-radius:3px;font-size:11px;"><?php echo esc_html($tbl); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

    <?php /* ── Support / Contact ── */ ?>
    <div class="postbox" style="max-width:780px;padding:20px 24px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 12px;">💬 <?php esc_html_e('Kontakt i wsparcie', 'basemgmt'); ?></h2>
        <p style="font-size:13px;color:#444;margin:0 0 10px;">
            <?php esc_html_e('W razie pytań, problemów lub propozycji nowych funkcji – skontaktuj się z twórcą wtyczki:', 'basemgmt'); ?>
        </p>
        <table style="border-collapse:collapse;font-size:13px;">
            <tbody>
                <tr>
                    <td style="padding:5px 16px 5px 0;color:#666;white-space:nowrap;">🌐 <?php esc_html_e('Strona', 'basemgmt'); ?></td>
                    <td><a href="https://pixks.pl" target="_blank" rel="noopener noreferrer">pixks.pl</a></td>
                </tr>
                <tr>
                    <td style="padding:5px 16px 5px 0;color:#666;white-space:nowrap;">📧 <?php esc_html_e('Email', 'basemgmt'); ?></td>
                    <td><a href="mailto:kontakt@pixks.pl">kontakt@pixks.pl</a></td>
                </tr>
                <tr>
                    <td style="padding:5px 16px 5px 0;color:#666;white-space:nowrap;">🐛 <?php esc_html_e('Zgłoszenia błędów', 'basemgmt'); ?></td>
                    <td><a href="https://github.com/Pixks/basemgmt/issues" target="_blank" rel="noopener noreferrer">github.com/Pixks/basemgmt</a></td>
                </tr>
            </tbody>
        </table>
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
