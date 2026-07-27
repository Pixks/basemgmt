<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e('Ogłoszenia', 'basemgmt'); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-announcements&action=new')); ?>">
		<?php esc_html_e('Dodaj ogłoszenie', 'basemgmt'); ?>
	</a>

	<?php
	$statuses  = ['' => __('Wszystkie', 'basemgmt'), 'active' => __('Aktywne', 'basemgmt'), 'pending' => __('Oczekujące', 'basemgmt'), 'expired' => __('Wygasłe', 'basemgmt'), 'archived' => __('Archiwalne', 'basemgmt'), 'draft' => __('Szkice', 'basemgmt')];
	$cur       = sanitize_key($_GET['filter_status'] ?? '');
	?>
	<ul class="subsubsub">
		<?php foreach ($statuses as $slug => $label) : ?>
			<li>
				<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-announcements' . ($slug ? '&filter_status=' . $slug : ''))); ?>"
				   class="<?php echo $cur === $slug ? 'current' : ''; ?>">
					<?php echo esc_html($label); ?>
				</a>
				<?php echo $slug !== array_key_last($statuses) ? ' |' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if (empty($announcements)) : ?>
		<p><?php esc_html_e('Brak ogłoszeń.', 'basemgmt'); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped bm-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Tytuł', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Pilne', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Od', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Do', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Źródło', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($announcements as $ann) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html($ann->title); ?></strong>
						</td>
						<td><span class="bm-badge bm-badge--<?php echo esc_attr($ann->status); ?>"><?php echo esc_html($ann->status); ?></span></td>
						<td><?php echo $ann->is_urgent ? '🔴 ' . esc_html__('TAK', 'basemgmt') : '—'; ?></td>
						<td><?php echo esc_html(wp_date(get_option('date_format'), strtotime($ann->valid_from))); ?></td>
						<td><?php echo esc_html(wp_date(get_option('date_format'), strtotime($ann->valid_until))); ?></td>
						<td><?php echo $ann->submitted_camp_id ? esc_html__('Obóz', 'basemgmt') : esc_html__('Admin', 'basemgmt'); ?></td>
						<td class="bm-actions">
							<?php if ($ann->status === 'pending') : ?>
								<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_approve_announcement&id={$ann->id}"), "bm_approve_ann_{$ann->id}")); ?>"
								   style="color: green;">
									<?php esc_html_e('Zatwierdź', 'basemgmt'); ?>
								</a> &nbsp;|&nbsp;
							<?php endif; ?>
							<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-announcements&action=edit&id={$ann->id}")); ?>">
								<?php esc_html_e('Edytuj', 'basemgmt'); ?>
							</a>
							&nbsp;|&nbsp;
							<a class="bm-danger"
							   href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_announcement&id={$ann->id}"), "bm_delete_ann_{$ann->id}")); ?>"
							   onclick="return confirm('<?php esc_attr_e('Czy na pewno usunąć to ogłoszenie?', 'basemgmt'); ?>')">
								<?php esc_html_e('Usuń', 'basemgmt'); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ($pages > 1) : ?>
			<div class="tablenav bottom">
				<?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $page, 'total' => $pages]); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
