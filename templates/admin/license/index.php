<?php
defined('ABSPATH') || exit;
use BaseMgmt\License\LicenseManager;
use BaseMgmt\License\LicenseClient;

// Self-initialises – works both as a standalone include and as an embedded settings tab.
$manager = LicenseManager::instance();
$client  = $manager->client();
$status  = $manager->get_status();

$license_key    = $client->get_license_key();
$api_base       = $client->get_api_base();
$update_channel = $client->get_update_channel();
$is_valid       = $manager->is_valid();
$is_enterprise  = $manager->is_enterprise();
$edition        = $manager->get_edition();
$developer_override_active    = $manager->is_developer_override_active();
$developer_override_constant  = \BaseMgmt\License\DeveloperOverride::CONFIG_CONSTANT;
$known_servers  = LicenseClient::known_servers();

$plan             = $manager->get_plan();
$active_channel   = $manager->get_active_channel();
$allowed_channels = $manager->get_allowed_channels();
$updates_allowed  = $manager->updates_allowed();
$support_active   = $manager->support_active();

$beta_locked  = ( 'beta' === $active_channel );
$beta_allowed = str_contains($allowed_channels, 'beta');

$selected_preset = 'custom';
foreach ( $known_servers as $preset_key => $preset_url ) {
	if ( rtrim($api_base, '/') === rtrim($preset_url, '/') ) {
		$selected_preset = $preset_key;
		break;
	}
}

$error_code = $status['error']['code']    ?? '';
$error_msg  = $status['error']['message'] ?? '';

if ( $developer_override_active ) {
	$ent_state_label = __('Aktywna · tryb deweloperski', 'basemgmt');
	$ent_state_class = 'bm-lic-badge--info';
	$ent_state_icon  = '🛠️';
} elseif ( $is_enterprise ) {
	$expiry          = $status['data']['expires_at'] ?? '';
	$ent_state_label = $expiry
		? sprintf(/* translators: %s: expiry date */ __('Aktywna · wygasa %s', 'basemgmt'), esc_html($expiry))
		: __('Aktywna', 'basemgmt');
	$ent_state_class = 'bm-lic-badge--active';
	$ent_state_icon  = '✅';
} else {
	$blocking_labels = [
		'license_expired'   => __('Licencja wygasła', 'basemgmt'),
		'license_revoked'   => __('Licencja cofnięta', 'basemgmt'),
		'license_suspended' => __('Licencja zawieszona', 'basemgmt'),
	];
	$ent_state_label = '' !== $license_key
		? ( $blocking_labels[$error_code] ?? ( $error_msg ?: __('Nieaktywna', 'basemgmt') ) )
		: '';
	$ent_state_class = 'bm-lic-badge--error';
	$ent_state_icon  = '❌';
}

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
.bm-lic-badge--warn    { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
.bm-lic-badge--info    { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
.bm-lic-badge--std     { background: #f3f4f6; color: #374151; border: 1px solid #9ca3af; }
.bm-lic-badge--ent     { background: #ede9fe; color: #4c1d95; border: 1px solid #a78bfa; }
.bm-lic-meta { margin: 8px 0 0; font-size: 13px; color: #555; }
.bm-lic-meta code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
.bm-lic-detail-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 10px 24px;
	margin-top: 16px;
}
.bm-lic-detail-item { display: flex; flex-direction: column; gap: 2px; }
.bm-lic-detail-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #888; }
.bm-lic-detail-value { font-size: 13px; color: #333; display: flex; align-items: center; gap: 6px; }
.bm-lic-pill { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.bm-pill-ok   { background: #d1fae5; color: #065f46; }
.bm-pill-no   { background: #fee2e2; color: #991b1b; }
.bm-pill-beta { background: #fef3c7; color: #92400e; }
.bm-pill-stable { background: #dbeafe; color: #1e40af; }
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
.bm-lic-channel-opt.bm-channel-locked { opacity: .7; cursor: not-allowed; }
.bm-lic-deactivate-btn {
	background: none; border: 1px solid #c0392b; color: #c0392b; padding: 6px 14px;
	border-radius: 4px; cursor: pointer; font-size: 13px;
}
.bm-lic-deactivate-btn:hover { background: #fee2e2; }
.bm-lic-refresh-btn {
	background: none; border: 1px solid #2271b1; color: #2271b1; padding: 6px 14px;
	border-radius: 4px; cursor: pointer; font-size: 13px; margin-left: 8px;
}
.bm-lic-refresh-btn:hover { background: #f0f6fc; }
.bm-lic-features { list-style: none; margin: 12px 0 0; padding: 0; }
.bm-lic-features li { padding: 5px 0; font-size: 13px; color: #374151; display: flex; align-items: center; gap: 8px; }
.bm-lic-tiers { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 4px; }
.bm-lic-tier { border: 2px solid #e5e7eb; border-radius: 8px; padding: 16px 18px; }
.bm-lic-tier.bm-tier-active { border-color: #a78bfa; background: #faf5ff; }
.bm-lic-tier h3 { margin: 0 0 8px; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
/* Beta warning modal */
.bm-modal-overlay {
	display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5);
	z-index: 99999; align-items: center; justify-content: center;
}
.bm-modal-overlay.bm-modal-open { display: flex; }
.bm-modal-box {
	background: #fff; border-radius: 10px; max-width: 480px; width: 90%;
	padding: 28px 32px; box-shadow: 0 8px 32px rgba(0,0,0,.18);
}
.bm-modal-box h3 { margin: 0 0 10px; font-size: 16px; color: #92400e; display: flex; gap: 8px; align-items: center; }
.bm-modal-box p { margin: 0 0 16px; font-size: 13px; color: #444; line-height: 1.6; }
.bm-modal-box .bm-modal-warn { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: #78350f; }
.bm-modal-checkbox { display: flex; align-items: flex-start; gap: 8px; font-size: 13px; margin-bottom: 16px; }
.bm-modal-actions { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; }
</style>

<div class="bm-lic-wrap">

	<?php /* ── EDITION OVERVIEW ── */ ?>
	<div class="bm-lic-card">
		<h2>🔑 <?php esc_html_e('Wersja wtyczki', 'basemgmt'); ?></h2>

		<?php if ( $developer_override_active ): ?>
		<div class="bm-lic-badge bm-lic-badge--info">
			🛠️ <?php esc_html_e('Tryb deweloperski', 'basemgmt'); ?>
		</div>
		<p class="bm-lic-meta" style="margin-top:8px;">
			<?php esc_html_e('Aktywowano poprzez stałą deweloperską w wp-config.php.', 'basemgmt'); ?>
			<code><?php echo esc_html($developer_override_constant); ?></code>
		</p>
		<?php else: ?>

		<div class="bm-lic-tiers">
			<div class="bm-lic-tier<?php echo $edition === 'standard' ? ' bm-tier-active' : ''; ?>">
				<h3>🥈 <?php esc_html_e('Standard', 'basemgmt'); ?>
					<?php if ( $edition === 'standard' ): ?>
					<span class="bm-lic-badge bm-lic-badge--none" style="font-size:11px;padding:2px 8px;margin:0;"><?php esc_html_e('aktywna', 'basemgmt'); ?></span>
					<?php endif; ?>
				</h3>
				<p style="font-size:13px;color:#555;margin:0 0 8px;"><?php esc_html_e('Wszystkie funkcje wtyczki są dostępne bez klucza licencji.', 'basemgmt'); ?></p>
				<ul class="bm-lic-features">
					<li>✅ <?php esc_html_e('Pełna funkcjonalność wtyczki', 'basemgmt'); ?></li>
					<li>✅ <?php esc_html_e('Brak ograniczeń danych ani użytkowników', 'basemgmt'); ?></li>
					<li>❌ <?php esc_html_e('Brak wsparcia producenta', 'basemgmt'); ?></li>
					<li>❌ <?php esc_html_e('Brak automatycznych aktualizacji', 'basemgmt'); ?></li>
				</ul>
			</div>

			<div class="bm-lic-tier<?php echo $edition === 'enterprise' ? ' bm-tier-active' : ''; ?>">
				<h3>💎 <?php esc_html_e('Enterprise', 'basemgmt'); ?>
					<?php if ( $edition === 'enterprise' ): ?>
					<span class="bm-lic-badge bm-lic-badge--ent" style="font-size:11px;padding:2px 8px;margin:0;"><?php esc_html_e('aktywna', 'basemgmt'); ?></span>
					<?php endif; ?>
				</h3>
				<p style="font-size:13px;color:#555;margin:0 0 8px;"><?php esc_html_e('Wymaga klucza licencji. Dodaje:', 'basemgmt'); ?></p>
				<ul class="bm-lic-features">
					<li>✅ <?php esc_html_e('Wszystko z wersji Standard', 'basemgmt'); ?></li>
					<li>✅ <?php esc_html_e('Wsparcie producenta (priorytetowa pomoc)', 'basemgmt'); ?></li>
					<li>✅ <?php esc_html_e('Automatyczne aktualizacje wtyczki', 'basemgmt'); ?></li>
					<li>✅ <?php esc_html_e('Dostęp do kanału beta (opcjonalnie)', 'basemgmt'); ?></li>
				</ul>
			</div>
		</div>

		<?php /* Show Enterprise status details if active */ ?>
		<?php if ( $is_enterprise ): ?>
		<div style="margin-top:16px;">
			<div class="bm-lic-badge <?php echo esc_attr($ent_state_class); ?>">
				<?php echo esc_html($ent_state_icon . ' ' . $ent_state_label); ?>
			</div>

			<?php if ( $masked_key ): ?>
			<p class="bm-lic-meta"><?php esc_html_e('Klucz:', 'basemgmt'); ?> <code><?php echo esc_html($masked_key); ?></code></p>
			<?php endif; ?>
			<?php if ( $api_base ): ?>
			<p class="bm-lic-meta"><?php esc_html_e('Serwer:', 'basemgmt'); ?> <code><?php echo esc_html($api_base); ?></code></p>
			<?php endif; ?>

			<div class="bm-lic-detail-grid">
				<?php if ( '' !== $plan ): ?>
				<div class="bm-lic-detail-item">
					<span class="bm-lic-detail-label"><?php esc_html_e('Plan', 'basemgmt'); ?></span>
					<span class="bm-lic-detail-value">
						<span class="bm-lic-pill bm-pill-stable"><?php echo esc_html(strtoupper($plan)); ?></span>
					</span>
				</div>
				<?php endif; ?>

				<div class="bm-lic-detail-item">
					<span class="bm-lic-detail-label"><?php esc_html_e('Kanał aktywny', 'basemgmt'); ?></span>
					<span class="bm-lic-detail-value">
						<?php if ( $beta_locked ): ?>
						<span class="bm-lic-pill bm-pill-beta">🧪 <?php esc_html_e('beta', 'basemgmt'); ?></span>
						<span style="font-size:11px;color:#92400e;">🔒 <?php esc_html_e('nieodwracalne', 'basemgmt'); ?></span>
						<?php else: ?>
						<span class="bm-lic-pill bm-pill-stable">🚀 <?php esc_html_e('stable', 'basemgmt'); ?></span>
						<?php endif; ?>
					</span>
				</div>

				<div class="bm-lic-detail-item">
					<span class="bm-lic-detail-label"><?php esc_html_e('Aktualizacje', 'basemgmt'); ?></span>
					<span class="bm-lic-detail-value">
						<?php if ( $updates_allowed ): ?>
						<span class="bm-lic-pill bm-pill-ok">✔ <?php esc_html_e('dostępne', 'basemgmt'); ?></span>
						<?php else: ?>
						<span class="bm-lic-pill bm-pill-no">✖ <?php esc_html_e('niedostępne', 'basemgmt'); ?></span>
						<?php endif; ?>
					</span>
				</div>

				<div class="bm-lic-detail-item">
					<span class="bm-lic-detail-label"><?php esc_html_e('Wsparcie', 'basemgmt'); ?></span>
					<span class="bm-lic-detail-value">
						<?php if ( $support_active ): ?>
						<span class="bm-lic-pill bm-pill-ok">✔ <?php esc_html_e('aktywne', 'basemgmt'); ?></span>
						<?php else: ?>
						<span class="bm-lic-pill bm-pill-no">✖ <?php esc_html_e('nieaktywne', 'basemgmt'); ?></span>
						<?php endif; ?>
					</span>
				</div>

				<?php
				$activations_in_use = $status['data']['activations_in_use'] ?? null;
				$activation_limit   = $status['data']['activation_limit']   ?? null;
				if ( null !== $activations_in_use ):
				?>
				<div class="bm-lic-detail-item">
					<span class="bm-lic-detail-label"><?php esc_html_e('Aktywacje', 'basemgmt'); ?></span>
					<span class="bm-lic-detail-value">
						<?php echo esc_html($activations_in_use); ?>
						<?php if ( null !== $activation_limit ): ?>/ <?php echo esc_html($activation_limit); ?><?php endif; ?>
					</span>
				</div>
				<?php endif; ?>

				<?php
				$grace_days = $status['data']['grace_period_days'] ?? null;
				if ( null !== $grace_days ):
				?>
				<div class="bm-lic-detail-item">
					<span class="bm-lic-detail-label"><?php esc_html_e('Okres karencji', 'basemgmt'); ?></span>
					<span class="bm-lic-detail-value">
						<?php echo esc_html($grace_days); ?> <?php esc_html_e('dni', 'basemgmt'); ?>
					</span>
				</div>
				<?php endif; ?>
			</div>

			<div style="margin-top:16px;">
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
					<?php wp_nonce_field('bm_refresh_license'); ?>
					<input type="hidden" name="action" value="bm_refresh_license">
					<button type="submit" class="bm-lic-refresh-btn">
						🔄 <?php esc_html_e('Odśwież status', 'basemgmt'); ?>
					</button>
				</form>
			</div>
		</div>
		<?php endif; /* is_enterprise */ ?>

		<?php /* Show error state if key is set but blocked */ ?>
		<?php if ( '' !== $license_key && ! $is_enterprise && ! $developer_override_active ): ?>
		<div style="margin-top:16px;">
			<div class="bm-lic-badge <?php echo esc_attr($ent_state_class); ?>">
				<?php echo esc_html($ent_state_icon . ' ' . $ent_state_label); ?>
			</div>
			<p class="bm-lic-meta">
				<?php esc_html_e('Wtyczka działa w trybie Standard. Dezaktywuj licencję, aby wyczyścić konfigurację.', 'basemgmt'); ?>
			</p>
		</div>
		<?php endif; ?>

		<?php endif; /* not developer_override */ ?>
	</div>

	<?php /* ── ENTERPRISE ACTIVATION CARD ── */ ?>
	<?php if ( ! $developer_override_active ): ?>
	<div class="bm-lic-card">
		<h2>⚡ <?php esc_html_e('Aktywacja Enterprise', 'basemgmt'); ?></h2>
		<p style="font-size:13px;color:#555;margin:0 0 16px;">
			<?php
			if ( $is_enterprise ) {
				esc_html_e('Możesz zaktualizować klucz lub zmienić serwer licencji.', 'basemgmt');
			} else {
				esc_html_e('Wprowadź klucz licencji Enterprise, aby odblokować wsparcie producenta i automatyczne aktualizacje.', 'basemgmt');
			}
			?>
		</p>

		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="bm-activate-form">
			<?php wp_nonce_field('bm_activate_license'); ?>
			<input type="hidden" name="action" value="bm_activate_license">

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

			<div class="bm-lic-form-row">
				<label for="bm-license-key"><?php esc_html_e('Klucz licencji Enterprise', 'basemgmt'); ?></label>
				<input type="text" id="bm-license-key" name="license_key"
					   value="<?php echo esc_attr($license_key); ?>"
					   placeholder="XXXX-XXXX-XXXX-XXXX"
					   autocomplete="off">
			</div>

			<div class="bm-lic-form-row">
				<label><?php esc_html_e('Kanał aktualizacji', 'basemgmt'); ?></label>

				<?php if ( $beta_locked ): ?>
				<div class="bm-lic-badge bm-lic-badge--warn" style="margin-bottom:10px;">
					🔒 <?php esc_html_e('Kanał beta zablokowany nieodwracalnie przez serwer licencji.', 'basemgmt'); ?>
				</div>
				<input type="hidden" name="update_channel" value="beta">
				<?php endif; ?>

				<div class="bm-lic-channel-row" id="bm-channel-row">
					<label class="bm-lic-channel-opt<?php echo $beta_locked ? ' bm-channel-locked' : ''; ?>">
						<input type="radio" name="update_channel" value="stable"
							   id="bm-channel-stable"
							   <?php checked($update_channel, 'stable'); ?>
							   <?php echo $beta_locked ? 'disabled' : ''; ?>>
						🚀 <?php esc_html_e('Produkcja (stable)', 'basemgmt'); ?>
					</label>
					<?php if ( $beta_allowed ): ?>
					<label class="bm-lic-channel-opt<?php echo $beta_locked ? ' bm-channel-locked' : ''; ?>"
						   id="bm-channel-beta-label">
						<input type="radio" name="update_channel" value="beta"
							   id="bm-channel-beta"
							   <?php checked($update_channel, 'beta'); ?>
							   <?php echo $beta_locked ? 'disabled' : ''; ?>>
						🧪 <?php esc_html_e('Testy (beta)', 'basemgmt'); ?>
					</label>
					<?php endif; ?>
				</div>

				<?php if ( $beta_locked ): ?>
				<p class="description" style="color:#92400e;">
					⚠️ <?php esc_html_e('Ta licencja jest trwale przypisana do kanału beta i nie może wrócić do kanału stable.', 'basemgmt'); ?>
				</p>
				<?php elseif ( $beta_allowed ): ?>
				<p class="description">
					<?php esc_html_e('Kanał beta dostarcza wersje przedpremierowe. Zalecany tylko w środowiskach testowych.', 'basemgmt'); ?>
					<strong style="color:#92400e;"> <?php esc_html_e('Uwaga: Przejście na kanał beta jest nieodwracalne dla tej licencji.', 'basemgmt'); ?></strong>
				</p>
				<?php else: ?>
				<p class="description"><?php esc_html_e('Kanał beta nie jest dostępny dla tego planu licencji.', 'basemgmt'); ?></p>
				<?php endif; ?>
			</div>

			<?php submit_button(__('Zapisz i aktywuj Enterprise', 'basemgmt'), 'primary', 'submit', false, ['id' => 'bm-btn-activate']); ?>
		</form>
	</div>

	<?php /* ── DEACTIVATE CARD ── */ ?>
	<?php if ( $license_key ): ?>
	<div class="bm-lic-card">
		<h2>🔓 <?php esc_html_e('Dezaktywacja Enterprise', 'basemgmt'); ?></h2>
		<p style="font-size:13px;color:#555;margin:0 0 12px;">
			<?php esc_html_e('Dezaktywacja zwalnia slot aktywacji na serwerze i przywraca tryb Standard. Wszystkie dane wtyczki pozostają bez zmian.', 'basemgmt'); ?>
		</p>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('bm_deactivate_license'); ?>
			<input type="hidden" name="action" value="bm_deactivate_license">
			<button type="submit" class="bm-lic-deactivate-btn"
					onclick="return confirm('<?php echo esc_js(__('Czy na pewno chcesz dezaktywować licencję Enterprise? Wtyczka wróci do trybu Standard.', 'basemgmt')); ?>')">
				🚫 <?php esc_html_e('Dezaktywuj Enterprise', 'basemgmt'); ?>
			</button>
		</form>
	</div>
	<?php endif; ?>

	<?php endif; /* not developer_override */ ?>

</div>

<?php /* ── BETA CHANNEL WARNING MODAL ── */ ?>
<?php if ( $beta_allowed && ! $beta_locked && ! $developer_override_active ): ?>
<div class="bm-modal-overlay" id="bm-beta-modal" role="dialog" aria-modal="true"
	 aria-labelledby="bm-beta-modal-title">
	<div class="bm-modal-box">
		<h3 id="bm-beta-modal-title">⚠️ <?php esc_html_e('Uwaga: Nieodwracalna zmiana kanału', 'basemgmt'); ?></h3>
		<p><?php esc_html_e('Zamierzasz przełączyć tę licencję na kanał beta.', 'basemgmt'); ?></p>
		<div class="bm-modal-warn">
			<strong><?php esc_html_e('Operacja jest nieodwracalna.', 'basemgmt'); ?></strong>
			<?php esc_html_e(' Po przełączeniu na kanał beta ta licencja nie będzie mogła wrócić do kanału stable. Wersje beta mogą zawierać błędy i nie są zalecane do użytku produkcyjnego.', 'basemgmt'); ?>
		</div>
		<label class="bm-modal-checkbox">
			<input type="checkbox" id="bm-beta-confirm-check">
			<?php esc_html_e('Rozumiem i akceptuję, że zmiana kanału na beta jest trwała i nieodwracalna.', 'basemgmt'); ?>
		</label>
		<div class="bm-modal-actions">
			<button type="button" class="button" id="bm-beta-cancel">
				<?php esc_html_e('Anuluj', 'basemgmt'); ?>
			</button>
			<button type="button" class="button button-primary" id="bm-beta-confirm" disabled>
				<?php esc_html_e('Potwierdzam — przełącz na beta', 'basemgmt'); ?>
			</button>
		</div>
	</div>
</div>
<?php endif; ?>

<script>
(function () {
	'use strict';

	function bmLicServerChange(preset) {
		var row = document.getElementById('bm-custom-url-row');
		if (row) row.style.display = (preset === 'custom') ? 'block' : 'none';
	}
	window.bmLicServerChange = bmLicServerChange;

	document.addEventListener('DOMContentLoaded', function () {
		var sel = document.getElementById('bm-server-preset');
		if (sel) bmLicServerChange(sel.value);

		var betaRadio   = document.getElementById('bm-channel-beta');
		var stableRadio = document.getElementById('bm-channel-stable');
		var modal       = document.getElementById('bm-beta-modal');
		var cancelBtn   = document.getElementById('bm-beta-cancel');
		var confirmBtn  = document.getElementById('bm-beta-confirm');
		var confirmChk  = document.getElementById('bm-beta-confirm-check');

		if (!betaRadio || !modal) return;

		betaRadio.addEventListener('change', function () {
			if (betaRadio.checked) {
				betaRadio.checked = false;
				if (stableRadio) stableRadio.checked = true;
				openBetaModal();
			}
		});

		function openBetaModal() {
			if (confirmChk) confirmChk.checked = false;
			if (confirmBtn) confirmBtn.disabled = true;
			modal.classList.add('bm-modal-open');
		}

		function closeBetaModal() {
			modal.classList.remove('bm-modal-open');
		}

		if (cancelBtn) {
			cancelBtn.addEventListener('click', function () {
				closeBetaModal();
				if (stableRadio) stableRadio.checked = true;
			});
		}

		if (confirmChk) {
			confirmChk.addEventListener('change', function () {
				if (confirmBtn) confirmBtn.disabled = !confirmChk.checked;
			});
		}

		if (confirmBtn) {
			confirmBtn.addEventListener('click', function () {
				if (!confirmChk || !confirmChk.checked) return;
				closeBetaModal();
				betaRadio.checked = true;
				if (stableRadio) stableRadio.checked = false;
				var form = document.getElementById('bm-activate-form');
				if (form) form.submit();
			});
		}

		modal.addEventListener('click', function (e) {
			if (e.target === modal) {
				closeBetaModal();
				if (stableRadio) stableRadio.checked = true;
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && modal.classList.contains('bm-modal-open')) {
				closeBetaModal();
				if (stableRadio) stableRadio.checked = true;
			}
		});
	});
})();
</script>

