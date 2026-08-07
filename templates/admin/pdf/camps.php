<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title><?php esc_html_e('Stany osobowe obozów', 'basemgmt'); ?> – <?php echo esc_html(date_i18n('d.m.Y', strtotime($date))); ?></title>
<style>
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; font-size: 12px; margin: 20mm 15mm; color: #111; }
h1 { font-size: 18px; border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 8px; }
h2 { font-size: 14px; margin: 16px 0 6px; }
.meta { color: #555; font-size: 11px; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
th { background: #f0f0f0; font-weight: bold; }
.total-row { font-weight: bold; background: #e8f5e9; }
.missing { color: #c0392b; font-weight: bold; }
@media print {
  body { margin: 10mm; }
  .no-print { display: none; }
}
</style>
</head>
<body>
<div class="no-print" style="margin-bottom:16px;">
	<button onclick="window.print()" style="padding:8px 16px;font-size:14px;cursor:pointer;">🖨 <?php esc_html_e('Drukuj / Zapisz PDF', 'basemgmt'); ?></button>
	<button onclick="window.close()" style="padding:8px 16px;font-size:14px;cursor:pointer;margin-left:8px;"><?php esc_html_e('Zamknij', 'basemgmt'); ?></button>
</div>

<h1>📊 <?php esc_html_e('Stany osobowe obozów', 'basemgmt'); ?></h1>
<p class="meta">
	<?php printf(esc_html__('Data: %s | Wygenerowano: %s', 'basemgmt'), esc_html(date_i18n('d.m.Y', strtotime($date))), esc_html($generated_at)); ?>
	| <?php echo esc_html(get_bloginfo('name')); ?>
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
		<tr<?php echo ! $count ? ' class="missing"' : ''; ?>>
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
<h2 class="missing">⚠ <?php esc_html_e('Obozy bez meldunku:', 'basemgmt'); ?></h2>
<ul>
<?php foreach ($missing_camps as $mc): ?>
<li><?php echo esc_html($mc->name); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</body>
</html>
