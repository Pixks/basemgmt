<?php
defined('ABSPATH') || exit;
/**
 * @var object|null $template    – template row (null = new)
 * @var array       $items       – template items
 * @var array       $recurrences – recurrence labels
 * @var array       $day_names   – day name labels
 * @var array       $categories  – category labels from ScheduleRepository
 */
$is_new = ! $template;
?>
<div class="wrap bm-admin-wrap">
	<h1>
		<?php echo $is_new ? esc_html__('Nowy szablon planu dnia', 'basemgmt') : sprintf(esc_html__('Szablon: %s', 'basemgmt'), esc_html($template->name)); ?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-plan-templates')); ?>" class="page-title-action">
			<?php esc_html_e('← Lista', 'basemgmt'); ?>
		</a>
	</h1>

	<!-- Template meta form -->
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:640px;margin-bottom:28px;">
		<?php wp_nonce_field('bm_save_plan_template'); ?>
		<input type="hidden" name="action"      value="bm_save_plan_template">
		<input type="hidden" name="template_id" value="<?php echo esc_attr((string) ($template->id ?? 0)); ?>">
		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e('Nazwa', 'basemgmt'); ?> *</label></th>
				<td><input type="text" name="template_name" value="<?php echo esc_attr($template->name ?? ''); ?>" class="regular-text" required></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e('Opis', 'basemgmt'); ?></label></th>
				<td><textarea name="template_description" rows="2" class="large-text"><?php echo esc_textarea($template->description ?? ''); ?></textarea></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e('Powtarzalność', 'basemgmt'); ?></label></th>
				<td>
					<select name="recurrence" id="bm-tpl-recurrence" onchange="bmToggleDays()">
						<?php foreach ($recurrences as $k => $v): ?>
						<option value="<?php echo esc_attr($k); ?>"
							<?php selected($template->recurrence ?? 'once', $k); ?>>
							<?php echo esc_html($v); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e('Codzienny / tygodniowy – szablon można zastosować automatycznie na pasujące dni w "Plan dnia".', 'basemgmt'); ?></p>
				</td>
			</tr>
			<tr id="bm-days-row" style="<?php echo ($template->recurrence ?? 'once') === 'weekly' ? '' : 'display:none'; ?>">
				<th><?php esc_html_e('Dni tygodnia', 'basemgmt'); ?></th>
				<td>
					<?php
					$selected_days = array_map('intval', explode(',', $template->days_of_week ?? ''));
					foreach ($day_names as $num => $name):
					?>
					<label style="display:inline-block;margin-right:12px;">
						<input type="checkbox" name="days_of_week[]" value="<?php echo esc_attr((string) $num); ?>"
							<?php checked(in_array($num, $selected_days, true)); ?>>
						<?php echo esc_html($name); ?>
					</label>
					<?php endforeach; ?>
				</td>
			</tr>
		</table>
		<?php submit_button($is_new ? __('Utwórz szablon', 'basemgmt') : __('Zapisz', 'basemgmt')); ?>
	</form>

	<?php if (! $is_new): ?>
	<!-- Template items -->
	<h2><?php esc_html_e('Pozycje szablonu', 'basemgmt'); ?></h2>

	<?php if (empty($items)): ?>
	<p style="color:#888;"><?php esc_html_e('Brak pozycji. Dodaj pierwsze poniżej.', 'basemgmt'); ?></p>
	<?php else: ?>
	<table class="wp-list-table widefat fixed striped" style="margin-bottom:24px;">
		<thead><tr>
			<th style="width:80px;"><?php esc_html_e('Od', 'basemgmt'); ?></th>
			<th style="width:80px;"><?php esc_html_e('Do', 'basemgmt'); ?></th>
			<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
			<th style="width:130px;"><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
			<th style="width:60px;"><?php esc_html_e('Obowiązkowe', 'basemgmt'); ?></th>
			<th style="width:120px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
		</tr></thead>
		<tbody>
		<?php foreach ($items as $item):
			$del_url = wp_nonce_url(
				admin_url('admin-post.php?action=bm_delete_template_item&item_id=' . $item->id . '&template_id=' . $template->id),
				'bm_delete_template_item_' . $item->id
			);
		?>
		<tr>
			<td><?php echo esc_html($item->time_from ?: '—'); ?></td>
			<td><?php echo esc_html($item->time_to ?: '—'); ?></td>
			<td>
				<strong><?php echo esc_html($item->title); ?></strong>
				<?php if ($item->description): ?><br><small style="color:#666;"><?php echo esc_html(wp_trim_words($item->description, 8)); ?></small><?php endif; ?>
			</td>
			<td><?php echo esc_html($categories[$item->category] ?? $item->category); ?></td>
			<td><?php echo $item->is_mandatory ? '✓' : '—'; ?></td>
			<td>
				<a href="#bm-item-form" onclick="bmFillItem(<?php echo esc_js(wp_json_encode($item)); ?>)" class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
				<a href="<?php echo esc_url($del_url); ?>" class="button button-small"
				   data-bm-confirm="<?php esc_attr_e('Usunąć pozycję?', 'basemgmt'); ?>"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<!-- Add/edit item form -->
	<h3 id="bm-item-form"><?php esc_html_e('Dodaj / edytuj pozycję', 'basemgmt'); ?></h3>
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:640px;">
		<?php wp_nonce_field('bm_save_template_item'); ?>
		<input type="hidden" name="action"      value="bm_save_template_item">
		<input type="hidden" name="template_id" value="<?php echo esc_attr((string) $template->id); ?>">
		<input type="hidden" name="item_id"     id="bm-item-id" value="0">
		<table class="form-table">
			<tr>
				<th><?php esc_html_e('Czas od', 'basemgmt'); ?></th>
				<td><input type="time" name="time_from" id="bm-time-from" value=""></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Czas do', 'basemgmt'); ?></th>
				<td><input type="time" name="time_to" id="bm-time-to" value=""></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Nazwa *', 'basemgmt'); ?></th>
				<td><input type="text" name="item_title" id="bm-title" value="" class="regular-text" required></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Opis', 'basemgmt'); ?></th>
				<td><textarea name="description" id="bm-desc" rows="2" class="large-text"></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
				<td>
					<select name="category" id="bm-category">
						<?php foreach ($categories as $k => $v): ?>
						<option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Obowiązkowe', 'basemgmt'); ?></th>
				<td><label><input type="checkbox" name="is_mandatory" id="bm-mandatory" value="1"> <?php esc_html_e('Tak', 'basemgmt'); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
				<td><input type="number" name="sort_order" id="bm-order" value="0" min="0" style="width:80px;"></td>
			</tr>
		</table>
		<?php submit_button(__('Zapisz pozycję', 'basemgmt')); ?>
	</form>

	<script>
	function bmToggleDays() {
		var rec = document.getElementById('bm-tpl-recurrence').value;
		document.getElementById('bm-days-row').style.display = rec === 'weekly' ? '' : 'none';
	}
	function bmFillItem(item) {
		document.getElementById('bm-item-id').value    = item.id;
		document.getElementById('bm-time-from').value  = item.time_from;
		document.getElementById('bm-time-to').value    = item.time_to;
		document.getElementById('bm-title').value      = item.title;
		document.getElementById('bm-desc').value       = item.description;
		document.getElementById('bm-category').value   = item.category;
		document.getElementById('bm-mandatory').checked= item.is_mandatory == 1;
		document.getElementById('bm-order').value      = item.sort_order;
		document.getElementById('bm-item-form').scrollIntoView({behavior:'smooth'});
	}
	</script>
	<?php endif; ?>
</div>
