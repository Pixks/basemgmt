<?php
defined('ABSPATH') || exit;
$today = gmdate('Y-m-d');
?>
<div class="wrap bm-admin-wrap">
	<h1>🖨 <?php esc_html_e('Drukuj / Pobierz PDF', 'basemgmt'); ?></h1>
	<p class="description">
		<?php esc_html_e('Poniższe raporty generują widok gotowy do wydruku lub zapisu jako PDF przez przeglądarkę (Ctrl+P → Zapisz jako PDF).', 'basemgmt'); ?>
	</p>

	<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;margin-top:24px;">

		<!-- Camps status -->
		<div class="postbox" style="padding:16px;">
			<h2 style="margin-top:0;">📊 <?php esc_html_e('Stany osobowe obozów', 'basemgmt'); ?></h2>
			<p class="description"><?php esc_html_e('Aktualne liczby uczestników, kadry i pracowników na bazie.', 'basemgmt'); ?></p>
			<form method="get" target="_blank" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="bm_render_pdf">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('bm_render_pdf')); ?>">
				<input type="hidden" name="type" value="camps">
				<p>
					<label><?php esc_html_e('Data:', 'basemgmt'); ?>
					<input type="date" name="date" value="<?php echo esc_attr($today); ?>">
					</label>
				</p>
				<?php submit_button(__('Drukuj / PDF', 'basemgmt'), 'secondary'); ?>
			</form>
		</div>

		<!-- Schedule -->
		<div class="postbox" style="padding:16px;">
			<h2 style="margin-top:0;">📅 <?php esc_html_e('Plan dnia', 'basemgmt'); ?></h2>
			<p class="description"><?php esc_html_e('Plan dnia dla wybranej daty.', 'basemgmt'); ?></p>
			<form method="get" target="_blank" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="bm_render_pdf">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('bm_render_pdf')); ?>">
				<input type="hidden" name="type" value="schedule">
				<p>
					<label><?php esc_html_e('Data:', 'basemgmt'); ?>
					<input type="date" name="date" value="<?php echo esc_attr($today); ?>">
					</label>
				</p>
				<?php submit_button(__('Drukuj / PDF', 'basemgmt'), 'secondary'); ?>
			</form>
		</div>

		<!-- Meal menu -->
		<div class="postbox" style="padding:16px;">
			<h2 style="margin-top:0;">🍽 <?php esc_html_e('Jadłospis', 'basemgmt'); ?></h2>
			<p class="description"><?php esc_html_e('Jadłospis na wybrany dzień.', 'basemgmt'); ?></p>
			<form method="get" target="_blank" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="bm_render_pdf">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('bm_render_pdf')); ?>">
				<input type="hidden" name="type" value="menu">
				<p>
					<label><?php esc_html_e('Data:', 'basemgmt'); ?>
					<input type="date" name="date" value="<?php echo esc_attr($today); ?>">
					</label>
				</p>
				<?php submit_button(__('Drukuj / PDF', 'basemgmt'), 'secondary'); ?>
			</form>
		</div>

	</div>
</div>
