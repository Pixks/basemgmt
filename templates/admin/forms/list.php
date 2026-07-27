<?php defined('ABSPATH') || exit; ?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e('Formularze', 'basemgmt'); ?></h1>
	<a href="<?php echo esc_url(add_query_arg(['page' => 'basemgmt-forms', 'view' => 'edit_form'], admin_url('admin.php'))); ?>"
	   class="page-title-action"><?php esc_html_e('Dodaj formularz', 'basemgmt'); ?></a>
	<a href="<?php echo esc_url(add_query_arg(['page' => 'basemgmt-forms', 'view' => 'submissions'], admin_url('admin.php'))); ?>"
	   class="page-title-action"><?php esc_html_e('Zgłoszenia', 'basemgmt'); ?></a>
	<hr class="wp-header-end">

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Widoczność', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Pola', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty($forms) ) : ?>
			<tr><td colspan="7"><?php esc_html_e('Brak formularzy.', 'basemgmt'); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $forms as $form ) :
				$fields_count = count(BaseMgmt\Modules\Forms\FormRepository::get_fields((int) $form->id));
				$edit_url     = add_query_arg(['page' => 'basemgmt-forms', 'view' => 'edit_form', 'id' => $form->id], admin_url('admin.php'));
				$cats         = BaseMgmt\Modules\Forms\FormRepository::CATEGORIES;
				$cat_label    = $cats[$form->category] ?? $form->category;
			?>
			<tr>
				<td>
					<strong><?php echo esc_html($form->name); ?></strong>
					<?php if ( $form->is_pinned ) echo ' &#10036;'; ?>
					<div class="row-actions">
						<span><a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a></span>
						&nbsp;|&nbsp;
						<span class="trash">
							<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
								<?php wp_nonce_field('bm_delete_form'); ?>
								<input type="hidden" name="action" value="bm_delete_form">
								<input type="hidden" name="form_id" value="<?php echo esc_attr($form->id); ?>">
								<button type="submit" class="button-link"
									onclick="return confirm('<?php esc_attr_e('Usunąć formularz?', 'basemgmt'); ?>')"
									style="color:#d63638"><?php esc_html_e('Usuń', 'basemgmt'); ?></button>
							</form>
						</span>
					</div>
				</td>
				<td><?php echo esc_html($cat_label); ?></td>
				<td><?php echo $form->is_global ? esc_html__('Globalna', 'basemgmt') : esc_html__('Wybrane obozy', 'basemgmt'); ?></td>
				<td><?php echo esc_html($fields_count); ?></td>
				<td>
					<span class="bm-badge bm-badge-<?php echo esc_attr($form->status); ?>">
						<?php echo $form->status === 'active' ? esc_html__('Aktywny', 'basemgmt') : esc_html__('Nieaktywny', 'basemgmt'); ?>
					</span>
				</td>
				<td><?php echo esc_html($form->sort_order); ?></td>
				<td><a href="<?php echo esc_url($edit_url); ?>" class="button button-small"><?php esc_html_e('Edytuj / Pola', 'basemgmt'); ?></a></td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
