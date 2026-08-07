<?php
defined('ABSPATH') || exit;
use BaseMgmt\Modules\Schedule\ScheduleRepository;
?>
<h1>📅 <?php esc_html_e('Plan dnia', 'basemgmt'); ?> – <?php echo esc_html(date_i18n('l, d.m.Y', strtotime($date))); ?></h1>
<p class="meta">
	<?php printf(esc_html__('Wygenerowano: %s', 'basemgmt'), esc_html($generated_at)); ?>
</p>

<?php if (empty($plan_data)): ?>
<p><?php esc_html_e('Brak planu dnia dla wybranej daty.', 'basemgmt'); ?></p>
<?php else: ?>
<?php foreach ($plan_data as $pd):
	$header = $pd['header'];
	$items  = $pd['items'];
?>
<h2><?php echo esc_html($header->title ?: __('Plan dnia', 'basemgmt')); ?></h2>

<?php if (empty($items)): ?>
<p><em><?php esc_html_e('Brak pozycji.', 'basemgmt'); ?></em></p>
<?php else: ?>
<table>
	<thead><tr>
		<th style="width:110px;"><?php esc_html_e('Godzina', 'basemgmt'); ?></th>
		<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
		<th style="width:140px;"><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
	</tr></thead>
	<tbody>
	<?php foreach ($items as $item): ?>
	<tr>
		<td><?php echo esc_html($item->time_from ? $item->time_from . ($item->time_to ? '–' . $item->time_to : '') : '—'); ?></td>
		<td>
			<strong><?php echo esc_html($item->title); ?></strong>
			<?php if ($item->is_mandatory): ?> <span class="danger">*</span><?php endif; ?>
			<?php if ($item->description): ?><br><small><?php echo esc_html($item->description); ?></small><?php endif; ?>
		</td>
		<td><span class="pill"><?php echo esc_html(ScheduleRepository::CATEGORIES[$item->category] ?? $item->category); ?></span></td>
	</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
