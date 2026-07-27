<?php
defined('ABSPATH') || exit;
$is_edit   = ! empty($form);
$title_str = $is_edit ? __('Edytuj formularz', 'basemgmt') : __('Nowy formularz', 'basemgmt');
$back_url  = add_query_arg(['page' => 'basemgmt-forms'], admin_url('admin.php'));
?>
<div class="wrap">
	<h1><?php echo esc_html($title_str); ?></h1>
	<a href="<?php echo esc_url($back_url); ?>" class="button">&larr; <?php esc_html_e('Powrót', 'basemgmt'); ?></a>
	<hr class="wp-header-end">

	<!-- ── Form definition ─────────────────────────────────────────── -->
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field('bm_save_form'); ?>
		<input type="hidden" name="action"  value="bm_save_form">
		<input type="hidden" name="form_id" value="<?php echo esc_attr($form->id ?? 0); ?>">

		<div id="poststuff">
			<div id="post-body">
				<div id="post-body-content">

					<table class="form-table">
						<tr>
							<th><?php esc_html_e('Nazwa', 'basemgmt'); ?> <span class="required">*</span></th>
							<td><input type="text" name="name" class="regular-text" required
								value="<?php echo esc_attr($form->name ?? ''); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e('Opis', 'basemgmt'); ?></th>
							<td><textarea name="description" class="large-text" rows="3"><?php echo esc_textarea($form->description ?? ''); ?></textarea></td>
						</tr>
						<tr>
							<th><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
							<td>
								<select name="category">
									<?php foreach ( $categories as $k => $v ) : ?>
										<option value="<?php echo esc_attr($k); ?>"
											<?php selected($form->category ?? 'inne', $k); ?>><?php echo esc_html($v); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
							<td>
								<select name="status">
									<option value="active"   <?php selected($form->status ?? 'active', 'active'); ?>><?php esc_html_e('Aktywny', 'basemgmt'); ?></option>
									<option value="inactive" <?php selected($form->status ?? '', 'inactive'); ?>><?php esc_html_e('Nieaktywny', 'basemgmt'); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e('Widoczność', 'basemgmt'); ?></th>
							<td>
								<label>
									<input type="checkbox" name="is_global" value="1"
										<?php checked(! empty($form) ? (int)$form->is_global : 1, 1); ?>>
									<?php esc_html_e('Widoczny dla wszystkich obozów', 'basemgmt'); ?>
								</label>
								<p class="description"><?php esc_html_e('Odznacz, aby wybrać konkretne obozy.', 'basemgmt'); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e('Przypisane obozy', 'basemgmt'); ?></th>
							<td>
								<div style="max-height:150px;overflow-y:auto;border:1px solid #ccd0d4;padding:6px 10px;background:#fff">
									<?php foreach ( $camps as $camp ) : ?>
										<label style="display:block">
											<input type="checkbox" name="assigned_camps[]" value="<?php echo esc_attr($camp->id); ?>"
												<?php checked(in_array((int)$camp->id, $assigned_camps, true)); ?>>
											<?php echo esc_html($camp->name); ?>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="description"><?php esc_html_e('Aktywne tylko gdy „Widoczny dla wszystkich obozów" jest odznaczone.', 'basemgmt'); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
							<td><input type="number" name="sort_order" class="small-text" min="0"
								value="<?php echo esc_attr($form->sort_order ?? 0); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e('Wyróżniony', 'basemgmt'); ?></th>
							<td>
								<label><input type="checkbox" name="is_pinned" value="1"
									<?php checked(! empty($form->is_pinned)); ?>>
									<?php esc_html_e('Przypnij formularz na górze listy', 'basemgmt'); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e('Tekst wprowadzający', 'basemgmt'); ?></th>
							<td><textarea name="info_before" class="large-text" rows="3"><?php echo esc_textarea($form->info_before ?? ''); ?></textarea>
								<p class="description"><?php esc_html_e('Wyświetlane nad formularzem.', 'basemgmt'); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e('Tekst po wysłaniu', 'basemgmt'); ?></th>
							<td><textarea name="info_after" class="large-text" rows="3"><?php echo esc_textarea($form->info_after ?? ''); ?></textarea>
								<p class="description"><?php esc_html_e('Wyświetlane po wysłaniu zgłoszenia.', 'basemgmt'); ?></p>
							</td>
						</tr>
					</table>

					<?php submit_button($is_edit ? __('Zapisz zmiany', 'basemgmt') : __('Utwórz formularz', 'basemgmt')); ?>
				</div>
			</div>
		</div>
	</form>

	<?php if ( $is_edit ) : ?>
	<!-- ── Field builder ───────────────────────────────────────────── -->
	<hr>
	<h2><?php esc_html_e('Pola formularza', 'basemgmt'); ?></h2>

	<?php if ( ! empty($fields) ) : ?>
	<table class="wp-list-table widefat fixed striped" style="margin-bottom:20px">
		<thead>
			<tr>
				<th><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Klucz', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Etykieta', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Typ', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Wymagane', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $fields as $fld ) : ?>
			<tr>
				<td><?php echo esc_html($fld->sort_order); ?></td>
				<td><code><?php echo esc_html($fld->field_key); ?></code></td>
				<td><?php echo esc_html($fld->label); ?></td>
				<td><?php echo esc_html($field_types[$fld->type] ?? $fld->type); ?></td>
				<td><?php echo $fld->is_required ? esc_html__('Tak', 'basemgmt') : '—'; ?></td>
				<td>
					<button type="button" class="button button-small bm-edit-field"
						data-id="<?php echo esc_attr($fld->id); ?>"
						data-form_id="<?php echo esc_attr($fld->form_id); ?>"
						data-label="<?php echo esc_attr($fld->label); ?>"
						data-field_key="<?php echo esc_attr($fld->field_key); ?>"
						data-type="<?php echo esc_attr($fld->type); ?>"
						data-is_required="<?php echo esc_attr($fld->is_required); ?>"
						data-placeholder="<?php echo esc_attr($fld->placeholder); ?>"
						data-help_text="<?php echo esc_attr($fld->help_text); ?>"
						data-options="<?php echo esc_attr($fld->options_json); ?>"
						data-default_value="<?php echo esc_attr($fld->default_value); ?>"
						data-validation="<?php echo esc_attr($fld->validation); ?>"
						data-sort_order="<?php echo esc_attr($fld->sort_order); ?>">
						<?php esc_html_e('Edytuj', 'basemgmt'); ?>
					</button>
					&nbsp;
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
						<?php wp_nonce_field('bm_delete_form_field'); ?>
						<input type="hidden" name="action"   value="bm_delete_form_field">
						<input type="hidden" name="field_id" value="<?php echo esc_attr($fld->id); ?>">
						<input type="hidden" name="form_id"  value="<?php echo esc_attr($form->id); ?>">
						<button type="submit" class="button button-small"
							onclick="return confirm('<?php esc_attr_e('Usunąć to pole?', 'basemgmt'); ?>')"
							style="color:#d63638"><?php esc_html_e('Usuń', 'basemgmt'); ?></button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
		<p><?php esc_html_e('Brak pól. Dodaj pierwsze pole poniżej.', 'basemgmt'); ?></p>
	<?php endif; ?>

	<!-- Field add / edit form -->
	<div class="postbox" style="max-width:700px">
		<div class="postbox-header"><h2 id="bm-field-form-title" class="hndle"><?php esc_html_e('Dodaj pole', 'basemgmt'); ?></h2></div>
		<div class="inside">
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="bm-field-form">
				<?php wp_nonce_field('bm_save_form_field'); ?>
				<input type="hidden" name="action"   value="bm_save_form_field">
				<input type="hidden" name="form_id"  value="<?php echo esc_attr($form->id); ?>">
				<input type="hidden" name="field_id" id="bm-field-id" value="0">

				<table class="form-table">
					<tr>
						<th><?php esc_html_e('Etykieta', 'basemgmt'); ?> <span class="required">*</span></th>
						<td><input type="text" name="label" id="bm-f-label" class="regular-text" required></td>
					</tr>
					<tr>
						<th><?php esc_html_e('Klucz (field_key)', 'basemgmt'); ?></th>
						<td><input type="text" name="field_key" id="bm-f-key" class="regular-text">
							<p class="description"><?php esc_html_e('Pozostaw puste, aby wygenerować automatycznie.', 'basemgmt'); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('Typ pola', 'basemgmt'); ?></th>
						<td>
							<select name="type" id="bm-f-type">
								<?php foreach ( $field_types as $k => $v ) : ?>
									<option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('Wymagane', 'basemgmt'); ?></th>
						<td><label><input type="checkbox" name="is_required" id="bm-f-required" value="1"> <?php esc_html_e('Tak', 'basemgmt'); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e('Placeholder', 'basemgmt'); ?></th>
						<td><input type="text" name="placeholder" id="bm-f-placeholder" class="regular-text"></td>
					</tr>
					<tr>
						<th><?php esc_html_e('Opis pomocniczy', 'basemgmt'); ?></th>
						<td><input type="text" name="help_text" id="bm-f-help" class="regular-text"></td>
					</tr>
					<tr id="bm-f-row-options">
						<th><?php esc_html_e('Opcje (select/radio/checkbox)', 'basemgmt'); ?></th>
						<td>
							<textarea name="options" id="bm-f-options" class="large-text" rows="4"
								placeholder="<?php esc_attr_e('Jedna opcja w linii', 'basemgmt'); ?>"></textarea>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('Wartość domyślna', 'basemgmt'); ?></th>
						<td><input type="text" name="default_value" id="bm-f-default" class="regular-text"></td>
					</tr>
					<tr>
						<th><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
						<td><input type="number" name="sort_order" id="bm-f-order" class="small-text" min="0" value="0"></td>
					</tr>
				</table>

				<?php submit_button(__('Zapisz pole', 'basemgmt'), 'primary', 'submit', false); ?>
				&nbsp;
				<button type="button" id="bm-field-reset" class="button"><?php esc_html_e('Wyczyść', 'basemgmt'); ?></button>
			</form>
		</div>
	</div>
	<?php endif; ?>
</div>

<script>
(function($){
	// Populate field form when Edit button clicked
	$('.bm-edit-field').on('click', function(){
		var d = $(this).data();
		$('#bm-field-id').val(d.id);
		$('#bm-f-label').val(d.label);
		$('#bm-f-key').val(d.field_key);
		$('#bm-f-type').val(d.type);
		$('#bm-f-required').prop('checked', d.is_required == 1);
		$('#bm-f-placeholder').val(d.placeholder);
		$('#bm-f-help').val(d.help_text);
		// options_json stored as JSON array; display one per line
		var opts = [];
		try { opts = JSON.parse(d.options); } catch(e){}
		$('#bm-f-options').val(opts.join('\n'));
		$('#bm-f-default').val(d.default_value);
		$('#bm-f-order').val(d.sort_order);
		$('#bm-field-form-title').text('<?php esc_html_e('Edytuj pole', 'basemgmt'); ?>');
		$('html,body').animate({scrollTop: $('#bm-field-form').offset().top - 30}, 400);
	});

	$('#bm-field-reset').on('click', function(){
		$('#bm-field-id').val('0');
		$('#bm-field-form')[0].reset();
		$('#bm-field-form-title').text('<?php esc_html_e('Dodaj pole', 'basemgmt'); ?>');
	});
})(jQuery);
</script>
