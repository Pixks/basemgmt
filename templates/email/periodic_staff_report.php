<?php
defined('ABSPATH') || exit;
?>
<h2><?php esc_html_e('Raport stanów osobowych', 'basemgmt'); ?></h2>
<p>
	<strong><?php esc_html_e('Data:', 'basemgmt'); ?></strong>
	<?php echo esc_html($report_date ?? ''); ?><br>
	<strong><?php esc_html_e('Godzina:', 'basemgmt'); ?></strong>
	<?php echo esc_html($report_time ?? ''); ?>
</p>
<?php echo $report_lines_html ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<p>
	<strong><?php esc_html_e('Suma:', 'basemgmt'); ?></strong>
	<?php
	printf(
		esc_html__('%1$d uczestników, %2$d kadra, %3$d pracownicy', 'basemgmt'),
		(int) ($total_participants ?? 0),
		(int) ($total_staff ?? 0),
		(int) ($total_workers ?? 0)
	);
	?>
</p>
