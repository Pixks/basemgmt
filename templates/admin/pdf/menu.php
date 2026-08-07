<?php
defined('ABSPATH') || exit;
?>
<h1>🍽 <?php esc_html_e('Jadłospis', 'basemgmt'); ?> – <?php echo esc_html(date_i18n('l, d.m.Y', strtotime($date))); ?></h1>
<p class="meta">
	<?php printf(esc_html__('Wygenerowano: %s', 'basemgmt'), esc_html($generated_at)); ?>
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
		<th style="width:90px;"><?php esc_html_e('Godz.', 'basemgmt'); ?></th>
		<th><?php esc_html_e('Posiłek', 'basemgmt'); ?></th>
		<th style="width:150px;"><?php esc_html_e('Miejsce', 'basemgmt'); ?></th>
		<th style="width:150px;"><?php esc_html_e('Dieta', 'basemgmt'); ?></th>
	</tr></thead>
	<tbody>
	<?php foreach ($meal_items as $item): ?>
	<tr>
		<td><?php echo esc_html($item->time_from ?: '—'); ?></td>
		<td>
			<strong><?php echo esc_html($item->title); ?></strong>
			<?php if ($item->description): ?><br><small><?php echo esc_html($item->description); ?></small><?php endif; ?>
			<?php if ($item->allergens): ?><br><span class="warning">⚠ <?php echo esc_html($item->allergens); ?></span><?php endif; ?>
		</td>
		<td><?php echo esc_html($item->location ?: '—'); ?></td>
		<td><?php echo esc_html($item->diet_info ?: '—'); ?></td>
	</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php endforeach; ?>
<?php endif; ?>
