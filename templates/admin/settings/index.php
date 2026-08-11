<?php
defined('ABSPATH') || exit;
use BaseMgmt\Core\EmailService;
use BaseMgmt\Core\EmailTemplateRepository;
use BaseMgmt\Core\PdfSettings;
$s        = EmailService::get_settings();
$pdf      = PdfSettings::get_settings();
$registry = EmailTemplateRepository::get_registry();

// Enqueue CodeMirror for the HTML fields.
$cm_settings = wp_enqueue_code_editor(['type' => 'text/html', 'codemirror' => ['lineNumbers' => false, 'lineWrapping' => true]]);
wp_enqueue_script('wp-theme-plugin-editor');
wp_enqueue_style('wp-codemirror');
?>
<div class="wrap bm-wrap">
    <h1><?php esc_html_e('Ustawienia – Baza Obozowa', 'basemgmt'); ?></h1>

    <!-- Email settings -->
    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">📧 <?php esc_html_e('Ustawienia powiadomień email', 'basemgmt'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_settings'); ?>
            <input type="hidden" name="action" value="bm_save_settings">
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

    <!-- Email templates -->
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
                    <td>
                        <strong><?php echo esc_html($def['label']); ?></strong>
                    </td>
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

    <!-- PDF settings -->
    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">🖨 <?php esc_html_e('Wygląd widoków do druku', 'basemgmt'); ?></h2>
        <p class="description" style="margin:0 0 14px;">
            <?php esc_html_e('Ustaw branding i podstawowy wygląd raportów otwieranych w nowej karcie do wydruku lub zapisu jako PDF.', 'basemgmt'); ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_settings'); ?>
            <input type="hidden" name="action" value="bm_save_settings">
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

    <!-- Test email -->
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

    <!-- Notification settings -->
    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">🔔 <?php esc_html_e('Konfiguracja powiadomień', 'basemgmt'); ?></h2>
        <p class="description" style="margin:0 0 14px;">
            <?php esc_html_e('Ustawienia powiadomień email oraz blokad kont kadry.', 'basemgmt'); ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_settings'); ?>
            <input type="hidden" name="action" value="bm_save_settings">
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

        <!-- Backup download -->
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

        <!-- Import -->
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
                    onclick="return confirm('<?php esc_attr_e('Czy na pewno chcesz importować dane? Istniejące dane zostaną nadpisane.', 'basemgmt'); ?>')">
                    ⬆ <?php esc_html_e('Importuj backup', 'basemgmt'); ?>
                </button>
            </form>
        </div>

        <!-- Clear all data -->
        <div>
            <h3 style="margin:0 0 6px;"><?php esc_html_e('Wyczyść wszystkie dane', 'basemgmt'); ?></h3>
            <p class="description" style="color:#d63638;font-weight:600;">
                ⚠ <?php esc_html_e('Niebezpieczna operacja: permanentnie usuwa WSZYSTKIE dane wtyczki ze wszystkich tabel. Nie można cofnąć.', 'basemgmt'); ?>
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('bm_clear_data'); ?>
                <input type="hidden" name="action" value="bm_clear_data">
                <button type="submit" class="button bm-danger"
                    onclick="return confirm('<?php esc_attr_e('UWAGA! Ta operacja jest nieodwracalna i usunie WSZYSTKIE dane wtyczki. Czy na pewno chcesz kontynuować?', 'basemgmt'); ?>')">
                    🗑 <?php esc_html_e('Wyczyść wszystkie dane', 'basemgmt'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Plugin info -->
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
</div>

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
