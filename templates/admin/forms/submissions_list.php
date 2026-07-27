<?php defined('ABSPATH') || exit; ?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e('Zgłoszenia', 'basemgmt'); ?></h1>
	<a href="<?php echo esc_url(add_query_arg(['page' => 'basemgmt-forms'], admin_url('admin.php'))); ?>"
	   class="page-title-action">&larr; <?php esc_html_e('Formularze', 'basemgmt'); ?></a>
	<hr class="wp-header-end">

	<!-- ── Filters ─────────────────────────────────────────────────── -->
	<form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin-bottom:15px">
		<input type="hidden" name="page" value="basemgmt-forms">
		<input type="hidden" name="view" value="submissions">

		<select name="filter_form">
			<option value=""><?php esc_html_e('Wszystkie formularze', 'basemgmt'); ?></option>
			<?php foreach ( $forms as $f ) : ?>
				<option value="<?php echo esc_attr($f->id); ?>"
					<?php selected($f->id, $_GET['filter_form'] ?? ''); ?>><?php echo esc_html($f->name); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="filter_camp">
			<option value=""><?php esc_html_e('Wszystkie obozy', 'basemgmt'); ?></option>
			<?php foreach ( $camps as $c ) : ?>
				<option value="<?php echo esc_attr($c->id); ?>"
					<?php selected($c->id, $_GET['filter_camp'] ?? ''); ?>><?php echo esc_html($c->name); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="filter_status">
			<option value=""><?php esc_html_e('Wszystkie statusy', 'basemgmt'); ?></option>
			<?php foreach ( $statuses as $k => $v ) : ?>
				<option value="<?php echo esc_attr($k); ?>" <?php selected($k, $_GET['filter_status'] ?? ''); ?>><?php echo esc_html($v); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="filter_priority">
			<option value=""><?php esc_html_e('Wszystkie priorytety', 'basemgmt'); ?></option>
			<?php foreach ( $priorities as $k => $v ) : ?>
				<option value="<?php echo esc_attr($k); ?>" <?php selected($k, $_GET['filter_priority'] ?? ''); ?>><?php echo esc_html($v); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="filter_category">
			<option value=""><?php esc_html_e('Wszystkie kategorie', 'basemgmt'); ?></option>
			<?php foreach ( $categories as $k => $v ) : ?>
				<option value="<?php echo esc_attr($k); ?>" <?php selected($k, $_GET['filter_category'] ?? ''); ?>><?php echo esc_html($v); ?></option>
			<?php endforeach; ?>
		</select>

		<input type="date" name="filter_date_from" value="<?php echo esc_attr($_GET['filter_date_from'] ?? ''); ?>" title="<?php esc_attr_e('Data od', 'basemgmt'); ?>">
		<input type="date" name="filter_date_to"   value="<?php echo esc_attr($_GET['filter_date_to']   ?? ''); ?>" title="<?php esc_attr_e('Data do', 'basemgmt'); ?>">

		<?php submit_button(__('Filtruj', 'basemgmt'), 'secondary', 'filter', false); ?>
		<a href="<?php echo esc_url(add_query_arg(['page' => 'basemgmt-forms', 'view' => 'submissions'], admin_url('admin.php'))); ?>" class="button"><?php esc_html_e('Wyczyść', 'basemgmt'); ?></a>
	</form>

	<!-- ── Table ───────────────────────────────────────────────────── -->
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>#</th>
				<th><?php esc_html_e('Formularz', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Obóz', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Priorytet', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Przypisane do', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Data', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty($submissions) ) : ?>
			<tr><td colspan="9"><?php esc_html_e('Brak zgłoszeń.', 'basemgmt'); ?></td></tr>
		<?php else : ?>
			<?php
			// Build quick lookup maps.
			$forms_map  = array_column($forms,  null, 'id');
			$camps_map  = array_column($camps,  null, 'id');
			$users_map  = array_column($wp_users, null, 'ID');
			$cats       = BaseMgmt\Modules\Forms\FormRepository::CATEGORIES;
			foreach ( $submissions as $sub ) :
				$view_url = add_query_arg(['page' => 'basemgmt-forms', 'view' => 'view_submission', 'id' => $sub->id], admin_url('admin.php'));
				$form_name = $forms_map[$sub->form_id]->name ?? ('Form #' . $sub->form_id);
				$camp_name = $camps_map[$sub->camp_id]->name ?? ('Obóz #' . $sub->camp_id);
				$assigned  = $sub->assigned_to ? ($users_map[$sub->assigned_to]->display_name ?? '—') : '—';
			?>
			<tr>
				<td><?php echo esc_html($sub->id); ?></td>
				<td><?php echo esc_html($form_name); ?></td>
				<td><?php echo esc_html($camp_name); ?></td>
				<td><?php echo esc_html($cats[$sub->category] ?? $sub->category); ?></td>
				<td><span class="bm-badge bm-badge-<?php echo esc_attr($sub->status); ?>"><?php echo esc_html($statuses[$sub->status] ?? $sub->status); ?></span></td>
				<td><?php echo esc_html($priorities[$sub->priority] ?? $sub->priority); ?></td>
				<td><?php echo esc_html($assigned); ?></td>
				<td><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($sub->created_at))); ?></td>
				<td><a href="<?php echo esc_url($view_url); ?>" class="button button-small"><?php esc_html_e('Otwórz', 'basemgmt'); ?></a></td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
