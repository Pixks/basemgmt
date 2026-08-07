<?php
defined('ABSPATH') || exit;
?>
<h1>📊 <?php esc_html_e('Stany osobowe obozów', 'basemgmt'); ?></h1>
<p class="meta">
	<?php printf(esc_html__('Data: %s | Wygenerowano: %s', 'basemgmt'), esc_html(date_i18n('d.m.Y', strtotime($date))), esc_html($generated_at)); ?>
</p>

<table>
	<thead>
		<tr>
			<th><?php esc_html_e('Obóz', 'basemgmt'); ?></th>
			<th><?php esc_html_e('Uczestnicy', 'basemgmt'); ?></th>
			<th><?php esc_html_e('Kadra', 'basemgmt'); ?></th>
			<th><?php esc_html_e('Pracownicy', 'basemgmt'); ?></th>
			<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($camps as $camp):
			$count = $camp_counts[$camp->id] ?? null;
		?>
		<tr<?php echo ! $count ? ' class="danger"' : ''; ?>>
			<td><?php echo esc_html($camp->name); ?></td>
			<td><?php echo $count ? esc_html((string) $count->participants) : '—'; ?></td>
			<td><?php echo $count ? esc_html((string) $count->staff) : '—'; ?></td>
			<td><?php echo $count ? esc_html((string) $count->workers) : '—'; ?></td>
			<td><?php
				if ( ! $count ) {
					esc_html_e('Brak meldunku', 'basemgmt');
				} elseif ( $count->status === 'submitted' ) {
					esc_html_e('Wysłany', 'basemgmt');
				} else {
					esc_html_e('Roboczy', 'basemgmt');
				}
			?></td>
		</tr>
		<?php endforeach; ?>
		<tr class="total-row">
			<td><?php esc_html_e('SUMA', 'basemgmt'); ?></td>
			<td><?php echo esc_html((string) $report_totals->total_participants); ?></td>
			<td><?php echo esc_html((string) $report_totals->total_staff); ?></td>
			<td><?php echo esc_html((string) $report_totals->total_workers); ?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<?php if (! empty($missing_camps)): ?>
<h2 class="danger">⚠ <?php esc_html_e('Obozy bez meldunku', 'basemgmt'); ?></h2>
<ul>
<?php foreach ($missing_camps as $mc): ?>
	<li><?php echo esc_html($mc->name); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
