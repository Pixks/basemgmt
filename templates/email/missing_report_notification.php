<?php
defined('ABSPATH') || exit;
?>
<h2><?php esc_html_e('Brak dziennych meldunków', 'basemgmt'); ?></h2>
<p><?php esc_html_e('Poniższe obozy nie wysłały jeszcze meldunku dziennego.', 'basemgmt'); ?></p>
<p>
	<strong><?php esc_html_e('Data sprawdzenia:', 'basemgmt'); ?></strong>
	<?php echo esc_html($report_date ?? ''); ?><br>
	<strong><?php esc_html_e('Liczba obozów:', 'basemgmt'); ?></strong>
	<?php echo esc_html((string) ($missing_count ?? 0)); ?>
</p>
<?php echo $missing_camps_html ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
