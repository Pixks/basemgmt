<?php
defined('ABSPATH') || exit;
/**
 * @var object|null $day        – meal day row (null = new)
 * @var array       $items      – meal items for this day
 * @var string      $date       – current date string
 * @var array       $meal_types – MEAL_TYPES constant
 */
$is_new = !$day;
?>
<div class="wrap bm-wrap">
	<h1>
		<?php echo $is_new ? esc_html__('Nowy jadłospis', 'basemgmt') : sprintf(esc_html__('Jadłospis – %s', 'basemgmt'), esc_html(date_i18n('d.m.Y (l)', strtotime($date)))); ?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-menu')); ?>" class="page-title-action"><?php esc_html_e('← Lista', 'basemgmt'); ?></a>
	</h1>

	<!-- Day header form -->
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:600px;margin-bottom:28px;">
		<?php wp_nonce_field('bm_save_menu'); ?>
		<input type="hidden" name="action" value="bm_save_menu">
		<input type="hidden" name="meal_day_id" value="<?php echo esc_attr((string) ($day->id ?? 0)); ?>">
		<table class="form-table">
			<tr>
				<th><?php esc_html_e('Data', 'basemgmt'); ?></th>
				<td><input type="date" name="meal_date" value="<?php echo esc_attr($date); ?>" required class="regular-text"></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
				<td>
					<select name="status">
						<option value="published" <?php selected($day->status ?? 'published', 'published'); ?>><?php esc_html_e('Opublikowany', 'basemgmt'); ?></option>
						<option value="draft"     <?php selected($day->status ?? '', 'draft'); ?>><?php esc_html_e('Robocze', 'basemgmt'); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Uwagi organizacyjne', 'basemgmt'); ?></th>
				<td><textarea name="notes" rows="3" class="large-text"><?php echo esc_textarea($day->notes ?? ''); ?></textarea></td>
			</tr>
		</table>
		<?php submit_button($is_new ? __('Utwórz jadłospis', 'basemgmt') : __('Zapisz', 'basemgmt')); ?>
	</form>

	<?php if (!$is_new): ?>
	<!-- Actions -->
	<div style="margin-bottom:20px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
		<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bm_reset_menu_flags&day_id=' . $day->id), 'bm_reset_menu_flags_' . $day->id)); ?>" class="button" onclick="return confirm('<?php esc_attr_e('Zresetować flagi zmian?', 'basemgmt'); ?>')"><?php esc_html_e('Resetuj flagi zmian', 'basemgmt'); ?></a>

		<?php
		$meal_tpls = \BaseMgmt\Modules\Menu\MealTemplateRepository::get_all();
		if ( ! empty($meal_tpls) ):
		?>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-flex;gap:6px;align-items:center;">
			<?php wp_nonce_field('bm_apply_meal_template'); ?>
			<input type="hidden" name="action"      value="bm_apply_meal_template">
			<input type="hidden" name="meal_day_id" value="<?php echo esc_attr((string) $day->id); ?>">
			<select name="template_id" required>
				<option value=""><?php esc_html_e('– wybierz szablon –', 'basemgmt'); ?></option>
				<?php foreach ($meal_tpls as $tpl): ?>
				<option value="<?php echo esc_attr((string) $tpl->id); ?>"><?php echo esc_html($tpl->name); ?></option>
				<?php endforeach; ?>
			</select>
			<label style="font-size:13px;">
				<input type="checkbox" name="replace_existing" value="1">
				<?php esc_html_e('Zastąp istniejące', 'basemgmt'); ?>
			</label>
			<button type="submit" class="button button-secondary"><?php esc_html_e('Zastosuj szablon', 'basemgmt'); ?></button>
		</form>
		<?php endif; ?>
	</div>

	<!-- Meal items list -->
	<h2><?php esc_html_e('Posiłki', 'basemgmt'); ?></h2>

	<?php if (empty($items)): ?>
	<p style="color:#888;"><?php esc_html_e('Brak posiłków. Dodaj pierwszy poniżej.', 'basemgmt'); ?></p>
	<?php else: ?>
	<table class="wp-list-table widefat fixed striped" style="margin-bottom:24px;">
		<thead>
			<tr>
				<th style="width:120px;"><?php esc_html_e('Rodzaj', 'basemgmt'); ?></th>
				<th style="width:70px;"><?php esc_html_e('Godz.', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
				<th style="width:80px;"><?php esc_html_e('Miejsce', 'basemgmt'); ?></th>
				<th style="width:80px;"><?php esc_html_e('Flagi', 'basemgmt'); ?></th>
				<th style="width:140px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($items as $item):
			$del_url = wp_nonce_url(admin_url('admin-post.php?action=bm_delete_meal_item&item_id=' . $item->id . '&day_id=' . $day->id), 'bm_delete_meal_item_' . $item->id);
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
				<?php if ($item->is_new_today): ?><span style="background:#d1fae5;color:#065f46;font-size:.75rem;padding:1px 5px;border-radius:4px;">🆕</span> <?php endif; ?>
				<?php if ($item->is_updated_today): ?><span style="background:#fef3c7;color:#92400e;font-size:.75rem;padding:1px 5px;border-radius:4px;">✏</span><?php endif; ?>
			</td>
			<td>
				<a href="#bm-item-form" onclick="bmFillItem(<?php echo esc_js(wp_json_encode($item)); ?>)" class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
				<a href="<?php echo esc_url($del_url); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Usunąć posiłek?', 'basemgmt'); ?>')"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<!-- Add / Edit item form -->
	<h3 id="bm-item-form"><?php esc_html_e('Dodaj / edytuj posiłek', 'basemgmt'); ?></h3>
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:680px;">
		<?php wp_nonce_field('bm_save_meal_item'); ?>
		<input type="hidden" name="action" value="bm_save_meal_item">
		<input type="hidden" name="meal_day_id" value="<?php echo esc_attr((string) $day->id); ?>">
		<input type="hidden" name="item_id" id="bm-item-id" value="0">
		<table class="form-table">
			<tr>
				<th><?php esc_html_e('Rodzaj posiłku', 'basemgmt'); ?></th>
				<td>
					<select name="meal_type" id="bm-item-meal-type">
						<?php foreach ($meal_types as $val => $label): ?>
						<option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Godzina wydawania', 'basemgmt'); ?></th>
				<td><input type="time" name="time_from" id="bm-item-time" value=""></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Nazwa *', 'basemgmt'); ?></th>
				<td><input type="text" name="title" id="bm-item-title" value="" class="regular-text" required></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Opis / skład', 'basemgmt'); ?></th>
				<td><textarea name="description" id="bm-item-desc" rows="3" class="large-text"></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Miejsce wydawania', 'basemgmt'); ?></th>
				<td>
					<?php if (! empty($location_names)): ?>
					<select name="location" id="bm-item-location">
						<option value=""><?php esc_html_e('— wybierz lub wpisz ręcznie —', 'basemgmt'); ?></option>
						<?php foreach ($location_names as $lid => $lname): ?>
						<option value="<?php echo esc_attr($lname); ?>"><?php echo esc_html($lname); ?></option>
						<?php endforeach; ?>
						<option value="__custom"><?php esc_html_e('Inne (wpisz poniżej)', 'basemgmt'); ?></option>
					</select>
					<input type="text" name="location_custom" id="bm-item-location-custom" value="" class="regular-text" style="margin-top:4px;display:none;" placeholder="<?php esc_attr_e('Wpisz własne miejsce', 'basemgmt'); ?>">
					<?php else: ?>
					<input type="text" name="location" id="bm-item-location" value="" class="regular-text">
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Diety', 'basemgmt'); ?></th>
				<td>
					<?php if (! empty($diet_names)): ?>
					<select name="diet_info" id="bm-item-diet">
						<option value=""><?php esc_html_e('— wybierz lub wpisz ręcznie —', 'basemgmt'); ?></option>
						<?php foreach ($diet_names as $did => $dname): ?>
						<option value="<?php echo esc_attr($dname); ?>"><?php echo esc_html($dname); ?></option>
						<?php endforeach; ?>
						<option value="__custom"><?php esc_html_e('Inne (wpisz poniżej)', 'basemgmt'); ?></option>
					</select>
					<input type="text" name="diet_info_custom" id="bm-item-diet-custom" value="" class="regular-text" style="margin-top:4px;display:none;" placeholder="<?php esc_attr_e('np. wegetariańska, bezglutenowa', 'basemgmt'); ?>">
					<?php else: ?>
					<input type="text" name="diet_info" id="bm-item-diet" value="" class="regular-text" placeholder="<?php esc_attr_e('np. wegetariańska, bezglutenowa', 'basemgmt'); ?>">
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Alergeny', 'basemgmt'); ?></th>
				<td><input type="text" name="allergens" id="bm-item-allergens" value="" class="regular-text" placeholder="<?php esc_attr_e('np. gluten, laktoza, orzechy', 'basemgmt'); ?>"></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
				<td><input type="number" name="sort_order" id="bm-item-order" value="0" min="0" style="width:80px;"></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Flagi zmian', 'basemgmt'); ?></th>
				<td>
					<label><input type="checkbox" name="is_new_today" id="bm-item-new" value="1"> <?php esc_html_e('Nowe na dziś', 'basemgmt'); ?></label><br>
					<label><input type="checkbox" name="is_updated_today" id="bm-item-upd" value="1"> <?php esc_html_e('Zmienione dziś', 'basemgmt'); ?></label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Plan dnia', 'basemgmt'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="add_to_plan" id="bm-item-add-plan" value="1">
						<?php esc_html_e('Dodaj automatycznie do planu dnia', 'basemgmt'); ?>
					</label>
					<p class="description"><?php esc_html_e('Posiłek zostanie dodany jako pozycja w planie dnia dla tej daty.', 'basemgmt'); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button(__('Zapisz posiłek', 'basemgmt')); ?>
	</form>

	<script>
	(function(){
		function bmInitCustomSelect(selectId, customId, hiddenName) {
			var sel = document.getElementById(selectId);
			var cust = document.getElementById(customId);
			if (!sel || !cust) return;

			function bmToggle() {
				cust.style.display = (sel.value === '__custom') ? 'block' : 'none';
			}

			sel.addEventListener('change', bmToggle);
			bmToggle();

			// On form submit, copy custom value to select if __custom
			var form = sel.closest('form');
			if (form) {
				form.addEventListener('submit', function() {
					if (sel.value === '__custom') {
						sel.name = '';
						cust.name = hiddenName;
					}
				});
			}
		}

		document.addEventListener('DOMContentLoaded', function() {
			bmInitCustomSelect('bm-item-location', 'bm-item-location-custom', 'location');
			bmInitCustomSelect('bm-item-diet',     'bm-item-diet-custom',     'diet_info');
		});
	})();

	function bmFillItem(item) {
		document.getElementById('bm-item-id').value         = item.id;
		document.getElementById('bm-item-meal-type').value  = item.meal_type;
		document.getElementById('bm-item-time').value       = item.time_from;
		document.getElementById('bm-item-title').value      = item.title;
		document.getElementById('bm-item-desc').value       = item.description;
		document.getElementById('bm-item-location').value   = item.location;
		document.getElementById('bm-item-diet').value       = item.diet_info;
		document.getElementById('bm-item-allergens').value  = item.allergens;
		document.getElementById('bm-item-order').value      = item.sort_order;
		document.getElementById('bm-item-new').checked      = item.is_new_today == 1;
		document.getElementById('bm-item-upd').checked      = item.is_updated_today == 1;
		document.getElementById('bm-item-form').scrollIntoView({behavior:'smooth'});
	}
	</script>
	<?php endif; ?>
</div>
