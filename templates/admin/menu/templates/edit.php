<?php
defined('ABSPATH') || exit;
/**
 * @var object|null $template    – template row (null = new)
 * @var array       $items       – template items
 * @var array       $meal_types  – MEAL_TYPES constant
 * @var array       $diet_names
 * @var array       $location_names
 */
$is_new = ! $template;
?>
<div class="wrap bm-admin-wrap">
	<h1>
		<?php echo $is_new ? esc_html__('Nowy szablon jadłospisu', 'basemgmt') : sprintf(esc_html__('Szablon: %s', 'basemgmt'), esc_html($template->name)); ?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-meal-templates')); ?>" class="page-title-action"><?php esc_html_e('← Lista', 'basemgmt'); ?></a>
	</h1>

	<!-- Template header form -->
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:600px;margin-bottom:28px;">
		<?php wp_nonce_field('bm_save_meal_template'); ?>
		<input type="hidden" name="action" value="bm_save_meal_template">
		<input type="hidden" name="template_id" value="<?php echo esc_attr((string) ($template->id ?? 0)); ?>">
		<table class="form-table">
			<tr>
				<th><label for="bm-tpl-name"><?php esc_html_e('Nazwa szablonu *', 'basemgmt'); ?></label></th>
				<td><input type="text" id="bm-tpl-name" name="template_name" value="<?php echo esc_attr($template->name ?? ''); ?>" class="regular-text" required></td>
			</tr>
			<tr>
				<th><label for="bm-tpl-desc"><?php esc_html_e('Opis', 'basemgmt'); ?></label></th>
				<td><textarea id="bm-tpl-desc" name="template_description" rows="3" class="large-text"><?php echo esc_textarea($template->description ?? ''); ?></textarea></td>
			</tr>
		</table>
		<?php submit_button($is_new ? __('Utwórz szablon', 'basemgmt') : __('Zapisz zmiany', 'basemgmt')); ?>
	</form>

	<?php if ( ! $is_new ): ?>
	<!-- Items list -->
	<h2><?php esc_html_e('Pozycje szablonu', 'basemgmt'); ?></h2>

	<?php if ( empty($items) ): ?>
	<p style="color:#888;"><?php esc_html_e('Brak pozycji. Dodaj pierwszy posiłek poniżej.', 'basemgmt'); ?></p>
	<?php else: ?>
	<table class="wp-list-table widefat fixed striped" style="margin-bottom:24px;">
		<thead>
			<tr>
				<th style="width:130px;"><?php esc_html_e('Rodzaj', 'basemgmt'); ?></th>
				<th style="width:70px;"><?php esc_html_e('Godz.', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
				<th style="width:100px;"><?php esc_html_e('Miejsce', 'basemgmt'); ?></th>
				<th style="width:120px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($items as $item):
			$del_url = wp_nonce_url(
				admin_url('admin-post.php?action=bm_delete_meal_template_item&item_id=' . $item->id . '&template_id=' . $template->id),
				'bm_delete_meal_template_item_' . $item->id
			);
		?>
		<tr>
			<td><?php echo esc_html($meal_types[$item->meal_type] ?? $item->meal_type); ?></td>
			<td><?php echo esc_html($item->time_from ?: '—'); ?></td>
			<td>
				<strong><?php echo esc_html($item->title); ?></strong>
				<?php if ($item->description): ?><br><small style="color:#666;"><?php echo esc_html(wp_trim_words($item->description, 8)); ?></small><?php endif; ?>
				<?php if ($item->allergens): ?><br><small style="color:#856404;">⚠ <?php echo esc_html($item->allergens); ?></small><?php endif; ?>
			</td>
			<td><?php echo esc_html($item->location ?: '—'); ?></td>
			<td>
				<a href="#bm-item-form" onclick="bmFillTplItem(<?php echo esc_js(wp_json_encode($item)); ?>)" class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
				<a href="<?php echo esc_url($del_url); ?>" class="button button-small"
				   onclick="return confirm('<?php esc_attr_e('Usunąć pozycję?', 'basemgmt'); ?>')"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<!-- Add / Edit item form -->
	<h3 id="bm-item-form"><?php esc_html_e('Dodaj / edytuj pozycję', 'basemgmt'); ?></h3>
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:680px;">
		<?php wp_nonce_field('bm_save_meal_template_item'); ?>
		<input type="hidden" name="action"      value="bm_save_meal_template_item">
		<input type="hidden" name="template_id" value="<?php echo esc_attr((string) $template->id); ?>">
		<input type="hidden" name="item_id"     id="bm-tpl-item-id" value="0">
		<table class="form-table">
			<tr>
				<th><?php esc_html_e('Rodzaj posiłku', 'basemgmt'); ?></th>
				<td>
					<select name="meal_type" id="bm-tpl-meal-type">
						<?php foreach ($meal_types as $val => $label): ?>
						<option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Godzina wydawania', 'basemgmt'); ?></th>
				<td><input type="time" name="time_from" id="bm-tpl-time" value=""></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Nazwa *', 'basemgmt'); ?></th>
				<td><input type="text" name="title" id="bm-tpl-title" value="" class="regular-text" required></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Opis', 'basemgmt'); ?></th>
				<td><textarea name="description" id="bm-tpl-desc-item" rows="2" class="large-text"></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Miejsce wydawania', 'basemgmt'); ?></th>
				<td>
					<input type="text" name="location" id="bm-tpl-location" list="bm-tpl-loc-list" class="regular-text" value="">
					<datalist id="bm-tpl-loc-list">
						<?php foreach ($location_names as $loc): ?>
						<option value="<?php echo esc_attr($loc); ?>">
						<?php endforeach; ?>
					</datalist>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Informacja o diecie', 'basemgmt'); ?></th>
				<td>
					<input type="text" name="diet_info" id="bm-tpl-diet" list="bm-tpl-diet-list" class="regular-text" value="">
					<datalist id="bm-tpl-diet-list">
						<?php foreach ($diet_names as $d): ?>
						<option value="<?php echo esc_attr($d); ?>">
						<?php endforeach; ?>
					</datalist>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Alergeny', 'basemgmt'); ?></th>
				<td><input type="text" name="allergens" id="bm-tpl-allergens" value="" class="regular-text"></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
				<td><input type="number" name="sort_order" id="bm-tpl-sort" value="0" style="width:80px;"></td>
			</tr>
		</table>
		<?php submit_button(__('Zapisz pozycję', 'basemgmt'), 'secondary'); ?>
	</form>
	<?php endif; ?>
</div>
<script>
function bmFillTplItem(item) {
	document.getElementById('bm-tpl-item-id').value   = item.id;
	document.getElementById('bm-tpl-meal-type').value = item.meal_type;
	document.getElementById('bm-tpl-time').value      = item.time_from;
	document.getElementById('bm-tpl-title').value     = item.title;
	document.getElementById('bm-tpl-desc-item').value = item.description || '';
	document.getElementById('bm-tpl-location').value  = item.location || '';
	document.getElementById('bm-tpl-diet').value      = item.diet_info || '';
	document.getElementById('bm-tpl-allergens').value = item.allergens || '';
	document.getElementById('bm-tpl-sort').value      = item.sort_order;
}
</script>
