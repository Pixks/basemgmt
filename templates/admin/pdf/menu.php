<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title><?php esc_html_e('Jadłospis', 'basemgmt'); ?> – <?php echo esc_html(date_i18n('d.m.Y', strtotime($date))); ?></title>
<style>
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; font-size: 12px; margin: 20mm 15mm; color: #111; }
h1 { font-size: 18px; border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 8px; }
h2 { font-size: 14px; margin: 16px 0 6px; background: #fff8e1; padding: 6px 10px; border-left: 4px solid #f59e0b; }
.meta { color: #555; font-size: 11px; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
th { background: #f0f0f0; font-weight: bold; }
.allergen { color: #c0392b; font-size:10px; }
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

<h1>🍽 <?php esc_html_e('Jadłospis', 'basemgmt'); ?> – <?php echo esc_html(date_i18n('l, d.m.Y', strtotime($date))); ?></h1>
<p class="meta">
	<?php printf(esc_html__('Wygenerowano: %s', 'basemgmt'), esc_html($generated_at)); ?>
	| <?php echo esc_html(get_bloginfo('name')); ?>
</p>

<?php if (! $day_data): ?>
<p><?php esc_html_e('Brak opublikowanego jadłospisu dla wybranej daty.', 'basemgmt'); ?></p>
<?php else: ?>

<?php if ($day_data['notes']): ?>
<p><em><?php echo esc_html($day_data['notes']); ?></em></p>
<?php endif; ?>

<?php
$meal_type_labels = \BaseMgmt\Modules\Menu\MealRepository::MEAL_TYPES;
foreach ($day_data['grouped'] as $meal_type => $meal_items):
?>
<h2><?php echo esc_html($meal_type_labels[$meal_type] ?? ucfirst($meal_type)); ?></h2>
<table>
	<thead><tr>
		<th style="width:70px;"><?php esc_html_e('Godz.', 'basemgmt'); ?></th>
		<th><?php esc_html_e('Posiłek', 'basemgmt'); ?></th>
		<th style="width:120px;"><?php esc_html_e('Miejsce', 'basemgmt'); ?></th>
		<th style="width:130px;"><?php esc_html_e('Dieta', 'basemgmt'); ?></th>
	</tr></thead>
	<tbody>
	<?php foreach ($meal_items as $item): ?>
	<tr>
		<td><?php echo esc_html($item->time_from ?: '—'); ?></td>
		<td>
			<strong><?php echo esc_html($item->title); ?></strong>
			<?php if ($item->description): ?><br><small><?php echo esc_html($item->description); ?></small><?php endif; ?>
			<?php if ($item->allergens): ?><br><span class="allergen">⚠ <?php echo esc_html($item->allergens); ?></span><?php endif; ?>
		</td>
		<td><?php echo esc_html($item->location ?: '—'); ?></td>
		<td><?php echo esc_html($item->diet_info ?: '—'); ?></td>
	</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
