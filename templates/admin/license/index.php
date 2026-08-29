<?php
defined('ABSPATH') || exit;
use BaseMgmt\License\LicenseManager;
use BaseMgmt\License\LicenseClient;

// Variables provided by LicensePage::render():
// $manager       – LicenseManager instance
// $client        – LicenseClient instance
// $status        – current status array from API/cache
// $known_servers – array of preset key => URL

$license_key    = $client->get_license_key();
$api_base       = $client->get_api_base();
$update_channel = $client->get_update_channel();
$is_valid       = $manager->is_valid();
$known_servers  = $known_servers ?? LicenseClient::known_servers();

// Detect which preset matches current URL (if any).
$selected_preset = 'custom';
foreach ( $known_servers as $preset_key => $preset_url ) {
	if ( rtrim($api_base, '/') === rtrim($preset_url, '/') ) {
		$selected_preset = $preset_key;
		break;
	}
}

// Determine display state.
$error_code = $status['error']['code']    ?? '';
$error_msg  = $status['error']['message'] ?? '';

if ( '' === $license_key ) {
	$state_label = __('Brak klucza licencji', 'basemgmt');
	$state_class = 'bm-lic-badge--none';
	$state_icon  = '⬜';
} elseif ( $is_valid ) {
	$expiry      = $status['data']['expires_at'] ?? '';
	$state_label = $expiry
		? sprintf(/* translators: %s: expiry date */ __('Aktywna · wygasa %s', 'basemgmt'), esc_html($expiry))
		: __('Aktywna', 'basemgmt');
	$state_class = 'bm-lic-badge--active';
	$state_icon  = '✅';
} else {
	$blocking_labels = [
		'license_expired'   => __('Licencja wygasła', 'basemgmt'),
		'license_revoked'   => __('Licencja cofnięta', 'basemgmt'),
		'license_suspended' => __('Licencja zawieszona', 'basemgmt'),
	];
	$state_label = $blocking_labels[$error_code]
		?? ( $error_msg ?: __('Nieaktywna', 'basemgmt') );
	$state_class = 'bm-lic-badge--error';
	$state_icon  = '❌';
}

// Compute masked key display.
$masked_key = '' !== $license_key
	? substr($license_key, 0, 8) . str_repeat('•', max(0, strlen($license_key) - 8))
	: '';
?>
<style>
.bm-lic-wrap { max-width: 780px; }
.bm-lic-card {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 24px 28px;
	margin-bottom: 24px;
	box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.bm-lic-card h2 {
	margin: 0 0 16px;
	font-size: 15px;
	font-weight: 600;
	display: flex;
	align-items: center;
	gap: 8px;
}
.bm-lic-badge {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 6px 14px;
	border-radius: 20px;
	font-size: 14px;
	font-weight: 600;
	margin-bottom: 12px;
}
.bm-lic-badge--active  { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.bm-lic-badge--error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.bm-lic-badge--none    { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
.bm-lic-meta { margin: 8px 0 0; font-size: 13px; color: #555; }
.bm-lic-meta code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
.bm-lic-form-row { margin-bottom: 16px; }
.bm-lic-form-row label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 13px; }
.bm-lic-form-row input[type=text],
.bm-lic-form-row input[type=url],
.bm-lic-form-row select { width: 100%; max-width: 480px; }
.bm-lic-form-row .description { margin-top: 4px; font-size: 12px; color: #777; }
.bm-lic-custom-url { display: none; margin-top: 8px; }
.bm-lic-channel-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.bm-lic-channel-opt {
	display: flex; align-items: center; gap: 6px;
	padding: 8px 14px; border: 2px solid #ddd; border-radius: 6px; cursor: pointer;
	font-size: 13px; transition: border-color .15s;
}
.bm-lic-channel-opt:has(input:checked) { border-color: #2271b1; background: #f0f6fc; }
.bm-lic-deactivate-btn {
	background: none; border: 1px solid #c0392b; color: #c0392b; padding: 6px 14px;
	border-radius: 4px; cursor: pointer; font-size: 13px;
}
.bm-lic-deactivate-btn:hover { background: #fee2e2; }
</style>

<div class="wrap bm-lic-wrap">
	<h1>🔑 <?php esc_html_e('Licencja CampLink', 'basemgmt'); ?></h1>

	<?php /* ── STATUS CARD ── */ ?>
	<div class="bm-lic-card">
		<h2>📊 <?php esc_html_e('Status licencji', 'basemgmt'); ?></h2>
		<div class="bm-lic-badge <?php echo esc_attr($state_class); ?>">
			<?php echo esc_html($state_icon . ' ' . $state_label); ?>
		</div>
		<?php if ( $masked_key ): ?>
		<p class="bm-lic-meta">
			<?php esc_html_e('Klucz:', 'basemgmt'); ?>
			<code><?php echo esc_html($masked_key); ?></code>
		</p>
		<?php endif; ?>
		<?php if ( $api_base ): ?>
		<p class="bm-lic-meta">
			<?php esc_html_e('Serwer:', 'basemgmt'); ?>
			<code><?php echo esc_html($api_base); ?></code>
			&nbsp;·&nbsp;
			<?php esc_html_e('Kanał:', 'basemgmt'); ?>
			<code><?php echo esc_html( 'beta' === $update_channel ? __('testy (beta)', 'basemgmt') : __('produkcja', 'basemgmt') ); ?></code>
		</p>
		<?php endif; ?>
	</div>

	<?php /* ── ACTIVATE CARD ── */ ?>
	<div class="bm-lic-card">
		<h2>⚡ <?php esc_html_e('Konfiguracja i aktywacja', 'basemgmt'); ?></h2>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('bm_activate_license'); ?>
			<input type="hidden" name="action" value="bm_activate_license">

			<?php /* Server dropdown */ ?>
			<div class="bm-lic-form-row">
				<label for="bm-server-preset"><?php esc_html_e('Serwer licencji', 'basemgmt'); ?></label>
				<select id="bm-server-preset" name="license_server_preset" onchange="bmLicServerChange(this.value)">
					<?php foreach ( $known_servers as $pkey => $purl ): ?>
					<option value="<?php echo esc_attr($pkey); ?>" <?php selected($selected_preset, $pkey); ?>>
						<?php
						$labels = [
							'pixks_prod' => __('Pixks · Produkcja (pixks.pl)', 'basemgmt'),
						];
						echo esc_html($labels[$pkey] ?? $purl);
						?>
					</option>
					<?php endforeach; ?>
					<option value="custom" <?php selected($selected_preset, 'custom'); ?>>
						<?php esc_html_e('Własny URL…', 'basemgmt'); ?>
					</option>
				</select>
				<div class="bm-lic-custom-url" id="bm-custom-url-row">
					<label for="bm-license-api-url"><?php esc_html_e('URL własnego serwera', 'basemgmt'); ?></label>
					<input type="url" id="bm-license-api-url" name="license_api_url"
						   value="<?php echo 'custom' === $selected_preset ? esc_attr($api_base) : ''; ?>"
						   placeholder="https://twoj-serwer.example.com">
					<p class="description"><?php esc_html_e('Adres serwera LicenseManager z przedrostkiem https://.', 'basemgmt'); ?></p>
				</div>
			</div>

			<?php /* License key */ ?>
			<div class="bm-lic-form-row">
				<label for="bm-license-key"><?php esc_html_e('Klucz licencji', 'basemgmt'); ?></label>
				<input type="text" id="bm-license-key" name="license_key"
					   value="<?php echo esc_attr($license_key); ?>"
					   placeholder="XXXX-XXXX-XXXX-XXXX"
					   autocomplete="off">
			</div>

			<?php /* Update channel */ ?>
			<div class="bm-lic-form-row">
				<label><?php esc_html_e('Kanał aktualizacji', 'basemgmt'); ?></label>
				<div class="bm-lic-channel-row">
					<label class="bm-lic-channel-opt">
						<input type="radio" name="update_channel" value="stable" <?php checked($update_channel, 'stable'); ?>>
						🚀 <?php esc_html_e('Produkcja (stable)', 'basemgmt'); ?>
					</label>
					<label class="bm-lic-channel-opt">
						<input type="radio" name="update_channel" value="beta" <?php checked($update_channel, 'beta'); ?>>
						🧪 <?php esc_html_e('Testy (beta)', 'basemgmt'); ?>
					</label>
				</div>
				<p class="description"><?php esc_html_e('Kanał beta dostarcza wersje przedpremierowe. Zalecany tylko w środowiskach testowych.', 'basemgmt'); ?></p>
			</div>

			<?php submit_button(__('Zapisz i aktywuj licencję', 'basemgmt'), 'primary', 'submit', false, ['id' => 'bm-btn-activate']); ?>
		</form>
	</div>

	<?php /* ── DEACTIVATE CARD ── */ ?>
	<?php if ( $license_key ): ?>
	<div class="bm-lic-card">
		<h2>🔓 <?php esc_html_e('Dezaktywacja licencji', 'basemgmt'); ?></h2>
		<p style="font-size:13px;color:#555;margin:0 0 12px;">
			<?php esc_html_e('Dezaktywacja zwalnia slot aktywacji na serwerze, umożliwiając przeniesienie licencji na inną domenę.', 'basemgmt'); ?>
		</p>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('bm_deactivate_license'); ?>
			<input type="hidden" name="action" value="bm_deactivate_license">
			<button type="submit" class="bm-lic-deactivate-btn"
					onclick="return confirm('<?php echo esc_js(__('Czy na pewno chcesz dezaktywować licencję?', 'basemgmt')); ?>')">
				🚫 <?php esc_html_e('Dezaktywuj licencję', 'basemgmt'); ?>
			</button>
		</form>
	</div>
	<?php endif; ?>
</div>

<script>
function bmLicServerChange(preset) {
	var row = document.getElementById('bm-custom-url-row');
	if (row) row.style.display = (preset === 'custom') ? 'block' : 'none';
}
// Init on load.
(function() {
	var sel = document.getElementById('bm-server-preset');
	if (sel) bmLicServerChange(sel.value);
})();
</script>
