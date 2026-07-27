<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<h1><?php esc_html_e('Baza Obozowa – Dashboard', 'basemgmt'); ?></h1>

	<div class="bm-stats-grid">
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Aktywne obozy', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html($summary['camps']); ?></span>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Uczestnicy', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html($summary['participants']); ?></span>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Kadra', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html($summary['staff']); ?></span>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Pracownicy', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html($summary['workers']); ?></span>
		</div>
		<div class="bm-stat-card <?php echo $pending ? 'bm-stat-card--alert' : ''; ?>">
			<span class="bm-stat-label"><?php esc_html_e('Ogłoszenia oczekujące', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html($pending); ?></span>
			<?php if ($pending) : ?>
				<a class="bm-stat-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-announcements&filter_status=pending')); ?>">
					<?php esc_html_e('Zatwierdź', 'basemgmt'); ?> &rarr;
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="bm-dashboard-links">
		<a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps&action=new')); ?>">
			<?php esc_html_e('+ Nowy obóz', 'basemgmt'); ?>
		</a>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-staff&action=new')); ?>">
			<?php esc_html_e('+ Nowa osoba kadry', 'basemgmt'); ?>
		</a>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-announcements&action=new')); ?>">
			<?php esc_html_e('+ Nowe ogłoszenie', 'basemgmt'); ?>
		</a>
	</div>

	<p class="description">
		<?php
		printf(
			/* translators: 1: plugin version */
			esc_html__('Baza Obozowa v%s', 'basemgmt'),
			esc_html(BASEMGMT_VERSION)
		);
		?>
	</p>
</div>
