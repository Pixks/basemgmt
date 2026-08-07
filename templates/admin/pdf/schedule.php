<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title><?php esc_html_e('Plan dnia', 'basemgmt'); ?> – <?php echo esc_html(date_i18n('d.m.Y', strtotime($date))); ?></title>
<style>
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; font-size: 12px; margin: 20mm 15mm; color: #111; }
h1 { font-size: 18px; border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 8px; }
h2 { font-size: 14px; margin: 16px 0 6px; background: #f5f5f5; padding: 6px 10px; border-left: 4px solid #2271b1; }
.meta { color: #555; font-size: 11px; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
th { background: #f0f0f0; font-weight: bold; }
.cat { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 10px; background: #e3f2fd; }
.mandatory { color: #c0392b; font-weight: bold; }
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

<h1>📅 <?php esc_html_e('Plan dnia', 'basemgmt'); ?> – <?php echo esc_html(date_i18n('l, d.m.Y', strtotime($date))); ?></h1>
<p class="meta">
	<?php printf(esc_html__('Wygenerowano: %s', 'basemgmt'), esc_html($generated_at)); ?>
	| <?php echo esc_html(get_bloginfo('name')); ?>
</p>

<?php if (empty($plan_data)): ?>
<p><?php esc_html_e('Brak planu dnia dla wybranej daty.', 'basemgmt'); ?></p>
<?php else: ?>
<?php foreach ($plan_data as $pd):
	$header = $pd['header'];
	$items  = $pd['items'];
?>
<h2><?php echo esc_html($header->title ?: esc_html__('Plan dnia', 'basemgmt')); ?></h2>

<?php if (empty($items)): ?>
<p><em><?php esc_html_e('Brak pozycji.', 'basemgmt'); ?></em></p>
<?php else: ?>
<table>
	<thead><tr>
		<th style="width:70px;"><?php esc_html_e('Godzina', 'basemgmt'); ?></th>
		<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
		<th style="width:100px;"><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
	</tr></thead>
	<tbody>
	<?php foreach ($items as $item): ?>
	<tr>
		<td><?php echo esc_html($item->time_from ? $item->time_from . ($item->time_to ? '–' . $item->time_to : '') : '—'); ?></td>
		<td>
			<strong><?php echo esc_html($item->title); ?></strong>
			<?php if ($item->is_mandatory): ?> <span class="mandatory">*</span><?php endif; ?>
			<?php if ($item->description): ?><br><small><?php echo esc_html($item->description); ?></small><?php endif; ?>
		</td>
		<td><span class="cat"><?php echo esc_html($item->category); ?></span></td>
	</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
