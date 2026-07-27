<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e('Obozy', 'basemgmt'); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps&action=new')); ?>">
		<?php esc_html_e('Dodaj nowy', 'basemgmt'); ?>
	</a>

	<?php /* Status filter tabs */ ?>
	<?php
	$statuses   = ['all' => __('Wszystkie', 'basemgmt'), 'active' => __('Aktywne', 'basemgmt'), 'ended' => __('Zakończone', 'basemgmt'), 'archived' => __('Archiwalne', 'basemgmt')];
	$cur_status = sanitize_key($_GET['filter_status'] ?? '');
	?>
	<ul class="subsubsub">
		<?php foreach ($statuses as $slug => $label) : ?>
			<li>
				<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps' . ($slug !== 'all' ? '&filter_status=' . $slug : ''))); ?>"
				   class="<?php echo ($cur_status === $slug || ($slug === 'all' && !$cur_status)) ? 'current' : ''; ?>">
					<?php echo esc_html($label); ?>
				</a>
				<?php echo $slug !== array_key_last($statuses) ? ' |' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if (empty($camps)) : ?>
		<p><?php esc_html_e('Brak obozów.', 'basemgmt'); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped bm-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Start', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Koniec', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($camps as $camp) : ?>
					<tr>
						<td><strong><?php echo esc_html($camp->name); ?></strong></td>
						<td><?php echo esc_html($camp->start_date); ?></td>
						<td><?php echo esc_html($camp->end_date); ?></td>
						<td><span class="bm-badge bm-badge--<?php echo esc_attr($camp->status); ?>"><?php echo esc_html($camp->status); ?></span></td>
						<td class="bm-actions">
							<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-camps&action=edit&id={$camp->id}")); ?>">
								<?php esc_html_e('Edytuj', 'basemgmt'); ?>
							</a>
							&nbsp;|&nbsp;
							<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-staff&filter_camp={$camp->id}")); ?>">
								<?php esc_html_e('Kadra', 'basemgmt'); ?>
							</a>
							&nbsp;|&nbsp;
							<a class="bm-danger"
							   href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_camp&id={$camp->id}"), "bm_delete_camp_{$camp->id}")); ?>"
							   onclick="return confirm('<?php esc_attr_e('Czy na pewno usunąć ten obóz?', 'basemgmt'); ?>')">
								<?php esc_html_e('Usuń', 'basemgmt'); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ($pages > 1) : ?>
			<div class="tablenav bottom">
				<?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $page, 'total' => $pages]); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
