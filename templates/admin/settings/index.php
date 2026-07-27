<?php
defined('ABSPATH') || exit;
use BaseMgmt\Core\EmailService;
$s = EmailService::get_settings();
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
                    <p class="description"><?php esc_html_e('Widoczny gdy nie ma logo.', 'basemgmt'); ?></p></td>
                </tr>
                <tr>
                    <th><label for="bm-footer"><?php esc_html_e('Stopka emaila', 'basemgmt'); ?></label></th>
                    <td>
                        <textarea id="bm-footer" name="footer_text" rows="3" class="large-text"><?php echo esc_textarea($s['footer_text']); ?></textarea>
                        <p class="description"><?php esc_html_e('Np. adres ośrodka, dane kontaktowe. Widoczna w stopce każdego emaila.', 'basemgmt'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Zapisz ustawienia', 'basemgmt')); ?>
        </form>
    </div>

    <!-- Test email -->
    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;"><?php esc_html_e('Test emaila', 'basemgmt'); ?></h2>
        <p class="description"><?php esc_html_e('Wyślij testowy email aby sprawdzić wygląd szablonu i konfigurację serwera pocztowego.', 'basemgmt'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;gap:8px;align-items:center;">
            <?php wp_nonce_field('bm_send_test_email'); ?>
            <input type="hidden" name="action" value="bm_send_test_email">
            <input type="email" name="test_email" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text" placeholder="test@example.com">
            <button type="submit" class="button button-secondary"><?php esc_html_e('Wyślij testowy email', 'basemgmt'); ?></button>
        </form>
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
    </div>
</div>
