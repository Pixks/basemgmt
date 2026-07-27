<?php defined('ABSPATH') || exit;
$is_edit = ! is_null($announcement);
$id      = $is_edit ? (int) $announcement->id : 0;
?>
<div class="wrap bm-admin-wrap">
	<h1><?php echo $is_edit ? esc_html__('Edytuj ogłoszenie', 'basemgmt') : esc_html__('Nowe ogłoszenie', 'basemgmt'); ?></h1>

	<?php if ($is_edit && $announcement->status === 'pending') : ?>
		<div class="notice notice-warning"><p>
			<?php esc_html_e('To ogłoszenie zostało dodane przez obóz i oczekuje na zatwierdzenie.', 'basemgmt'); ?>
			<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_approve_announcement&id={$id}"), "bm_approve_ann_{$id}")); ?>"
			   class="button button-small button-primary" style="margin-left:8px;">
				<?php esc_html_e('Zatwierdź teraz', 'basemgmt'); ?>
			</a>
		</p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field('bm_save_announcement'); ?>
		<input type="hidden" name="action"          value="bm_save_announcement">
		<input type="hidden" name="announcement_id" value="<?php echo esc_attr($id); ?>">

		<table class="form-table" role="presentation">
			<tr>
				<th><label for="bm_title"><?php esc_html_e('Tytuł', 'basemgmt'); ?> <span class="required">*</span></label></th>
				<td><input type="text" id="bm_title" name="title" class="large-text" required
					   value="<?php echo esc_attr($announcement->title ?? ''); ?>"></td>
			</tr>
			<tr>
				<th><label for="bm_content"><?php esc_html_e('Treść', 'basemgmt'); ?></label></th>
				<td><textarea id="bm_content" name="content" class="large-text" rows="8"><?php echo wp_kses_post($announcement->content ?? ''); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="bm_status"><?php esc_html_e('Status', 'basemgmt'); ?></label></th>
				<td>
					<select id="bm_status" name="status">
						<?php
						$opts = ['active' => __('Aktywne', 'basemgmt'), 'draft' => __('Szkic', 'basemgmt'), 'archived' => __('Archiwalne', 'basemgmt'), 'expired' => __('Wygasłe', 'basemgmt')];
						foreach ($opts as $v => $l) :
							printf('<option value="%s"%s>%s</option>', esc_attr($v), selected($announcement->status ?? 'active', $v, false), esc_html($l));
						endforeach;
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Pilne', 'basemgmt'); ?></th>
				<td>
					<input type="checkbox" name="is_urgent" value="1" id="bm_urgent"
						   <?php checked((bool) ($announcement->is_urgent ?? false)); ?>>
					<label for="bm_urgent"><?php esc_html_e('Oznacz jako pilne (wyróżnienie w panelu)', 'basemgmt'); ?></label>
				</td>
			</tr>
			<tr>
				<th><label for="bm_priority"><?php esc_html_e('Priorytet', 'basemgmt'); ?></label></th>
				<td>
					<input type="number" id="bm_priority" name="priority" min="0" max="10" style="width:80px"
						   value="<?php echo esc_attr($announcement->priority ?? 0); ?>">
					<p class="description"><?php esc_html_e('0–10. Wyższy priorytet = wyżej na liście.', 'basemgmt'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="bm_from"><?php esc_html_e('Obowiązuje od', 'basemgmt'); ?> <span class="required">*</span></label></th>
				<td><input type="datetime-local" id="bm_from" name="valid_from" required
					   value="<?php echo esc_attr(str_replace(' ', 'T', substr($announcement->valid_from ?? date('Y-m-d H:i:s'), 0, 16))); ?>"></td>
			</tr>
			<tr>
				<th><label for="bm_until"><?php esc_html_e('Obowiązuje do', 'basemgmt'); ?> <span class="required">*</span></label></th>
				<td><input type="datetime-local" id="bm_until" name="valid_until" required
					   value="<?php echo esc_attr(str_replace(' ', 'T', substr($announcement->valid_until ?? date('Y-m-d H:i:s', strtotime('+7 days')), 0, 16))); ?>"></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Zasięg', 'basemgmt'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="is_global" id="bm_global" value="1"
							   <?php checked((bool) ($announcement->is_global ?? true)); ?>
							   onchange="document.getElementById('bm_camp_targets').style.display = this.checked ? 'none' : 'block'">
						<?php esc_html_e('Globalne (widoczne dla wszystkich obozów)', 'basemgmt'); ?>
					</label>
					<div id="bm_camp_targets" style="margin-top:8px; <?php echo (isset($announcement->is_global) && !$announcement->is_global) ? '' : 'display:none'; ?>">
						<p class="description"><?php esc_html_e('Wybierz obozy docelowe (Ctrl+klik dla wielu):', 'basemgmt'); ?></p>
						<select name="camp_ids[]" multiple size="6" style="min-width:250px;">
							<?php foreach ($camps as $c) : ?>
								<option value="<?php echo esc_attr($c->id); ?>"
									<?php echo in_array((string)$c->id, $camp_target, true) ? 'selected' : ''; ?>>
									<?php echo esc_html($c->name); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="bm_attach"><?php esc_html_e('URL załącznika', 'basemgmt'); ?></label></th>
				<td><input type="url" id="bm_attach" name="attachment_url" class="large-text"
					   value="<?php echo esc_url($announcement->attachment_url ?? ''); ?>"></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php echo $is_edit ? esc_html__('Zapisz zmiany', 'basemgmt') : esc_html__('Dodaj ogłoszenie', 'basemgmt'); ?>
			</button>
			<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-announcements')); ?>">
				<?php esc_html_e('Anuluj', 'basemgmt'); ?>
			</a>
		</p>
	</form>
</div>
