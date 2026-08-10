<?php
defined('ABSPATH') || exit;
use BaseMgmt\License\LicenseManager;

// Variables provided by LicensePage::render():
// $manager – LicenseManager instance
// $client  – LicenseClient instance
// $status  – current status array from API/cache

$license_key = $client->get_license_key();
$api_base    = $client->get_api_base();
$is_valid    = $manager->is_valid();

// Determine display state.
$error_code = $status['error']['code'] ?? '';
$error_msg  = $status['error']['message'] ?? '';

if ( '' === $license_key ) {
	$state_label = __('Brak klucza', 'basemgmt');
	$state_color = '#6b7280';
	$state_icon  = '○';
} elseif ( $is_valid ) {
	$expiry      = $status['data']['expires_at'] ?? '';
	$state_label = $expiry
		? sprintf(/* translators: %s: expiry date */ __('Aktywna (wygasa %s)', 'basemgmt'), esc_html($expiry))
		: __('Aktywna', 'basemgmt');
	$state_color = '#10b981';
	$state_icon  = '●';
} else {
	$blocking_labels = [
		'license_expired'   => __('Licencja wygasła', 'basemgmt'),
		'license_revoked'   => __('Licencja cofnięta', 'basemgmt'),
		'license_suspended' => __('Licencja zawieszona', 'basemgmt'),
	];
	$state_label = $blocking_labels[$error_code]
		?? ( $error_msg ?: __('Nieaktywna', 'basemgmt') );
	$state_color = '#ef4444';
	$state_icon  = '✕';
}
?>
<div class="wrap bm-wrap">
    <h1>🔑 <?php esc_html_e('Licencja CampLink', 'basemgmt'); ?></h1>

    <!-- Status -->
    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;"><?php esc_html_e('Status licencji', 'basemgmt'); ?></h2>
        <p style="font-size:15px;">
            <span style="color:<?php echo esc_attr($state_color); ?>;font-weight:700;">
                <?php echo esc_html($state_icon . ' ' . $state_label); ?>
            </span>
        </p>
        <?php if ( $license_key ): ?>
        <p class="description">
            <?php esc_html_e('Klucz licencji:', 'basemgmt'); ?>
            <code><?php echo esc_html(substr($license_key, 0, 8) . str_repeat('•', max(0, strlen($license_key) - 8))); ?></code>
        </p>
        <?php endif; ?>
        <?php if ( $api_base ): ?>
        <p class="description">
            <?php esc_html_e('Serwer licencji:', 'basemgmt'); ?>
            <code><?php echo esc_html($api_base); ?></code>
        </p>
        <?php endif; ?>
    </div>

    <!-- Activate form -->
    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">⚡ <?php esc_html_e('Aktywacja licencji', 'basemgmt'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_activate_license'); ?>
            <input type="hidden" name="action" value="bm_activate_license">
            <table class="form-table" style="margin:0;">
                <tr>
                    <th><label for="bm-license-api-url"><?php esc_html_e('URL serwera licencji', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="url" id="bm-license-api-url" name="license_api_url" class="large-text"
                               value="<?php echo esc_attr($api_base); ?>"
                               placeholder="https://licencje.example.com">
                        <p class="description"><?php esc_html_e('Adres Twojego serwera LicenseManager (np. https://pixks.pl/licencje).', 'basemgmt'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-license-key"><?php esc_html_e('Klucz licencji', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="text" id="bm-license-key" name="license_key" class="regular-text"
                               value="<?php echo esc_attr($license_key); ?>"
                               placeholder="XXXX-XXXX-XXXX-XXXX"
                               autocomplete="off">
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Aktywuj licencję', 'basemgmt'), 'primary', 'submit', true, ['id' => 'bm-btn-activate']); ?>
        </form>
    </div>

    <!-- Deactivate -->
    <?php if ( $license_key ): ?>
    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 10px;">🚫 <?php esc_html_e('Dezaktywacja licencji', 'basemgmt'); ?></h2>
        <p class="description"><?php esc_html_e('Dezaktywacja zwalnia slot aktywacji na serwerze licencji, umożliwiając przeniesienie licencji na inną domenę.', 'basemgmt'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_deactivate_license'); ?>
            <input type="hidden" name="action" value="bm_deactivate_license">
            <?php submit_button(__('Dezaktywuj licencję', 'basemgmt'), 'delete', 'submit', false); ?>
        </form>
    </div>
    <?php endif; ?>
</div>
