<?php defined('ABSPATH') || exit;
$is_edit = ! is_null($camp);
$id      = $is_edit ? (int) $camp->id : 0;
?>
<div class="wrap bm-admin-wrap">
	<h1>
		<?php echo $is_edit ? esc_html__('Edytuj obóz', 'basemgmt') : esc_html__('Nowy obóz', 'basemgmt'); ?>
	</h1>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field('bm_save_camp'); ?>
		<input type="hidden" name="action"  value="bm_save_camp">
		<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="bm_name"><?php esc_html_e('Nazwa obozu', 'basemgmt'); ?> <span class="required">*</span></label></th>
				<td><input type="text" id="bm_name" name="name" class="regular-text" required
					   value="<?php echo esc_attr($camp->name ?? ''); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="bm_start"><?php esc_html_e('Data rozpoczęcia', 'basemgmt'); ?> <span class="required">*</span></label></th>
				<td><input type="date" id="bm_start" name="start_date" required
					   value="<?php echo esc_attr($camp->start_date ?? ''); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="bm_end"><?php esc_html_e('Data zakończenia', 'basemgmt'); ?> <span class="required">*</span></label></th>
				<td><input type="date" id="bm_end" name="end_date" required
					   value="<?php echo esc_attr($camp->end_date ?? ''); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="bm_status"><?php esc_html_e('Status', 'basemgmt'); ?></label></th>
				<td>
					<select id="bm_status" name="status">
						<?php
						$statuses = ['active' => __('Aktywny', 'basemgmt'), 'ended' => __('Zakończony', 'basemgmt'), 'archived' => __('Archiwalny', 'basemgmt')];
						foreach ($statuses as $val => $label) :
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr($val),
								selected($camp->status ?? 'active', $val, false),
								esc_html($label)
							);
						endforeach;
						?>
					</select>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php echo $is_edit ? esc_html__('Zapisz zmiany', 'basemgmt') : esc_html__('Dodaj obóz', 'basemgmt'); ?>
			</button>
			<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps')); ?>">
				<?php esc_html_e('Anuluj', 'basemgmt'); ?>
			</a>
		</p>
	</form>
</div>
