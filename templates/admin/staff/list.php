<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e('Kadra', 'basemgmt'); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-staff&action=new')); ?>">
		<?php esc_html_e('Dodaj osobę', 'basemgmt'); ?>
	</a>

	<?php /* Filter by camp */ ?>
	<?php if (!empty($camps)) : ?>
		<form method="get" style="display:inline-block; margin-left:10px;">
			<input type="hidden" name="page" value="basemgmt-staff">
			<select name="filter_camp" onchange="this.form.submit()">
				<option value=""><?php esc_html_e('— Wszystkie obozy —', 'basemgmt'); ?></option>
				<?php foreach ($camps as $c) : ?>
					<option value="<?php echo esc_attr($c->id); ?>"
						<?php selected((int)($_GET['filter_camp'] ?? 0), (int)$c->id); ?>>
						<?php echo esc_html($c->name); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</form>
	<?php endif; ?>

	<?php if (empty($staff_list)) : ?>
		<p><?php esc_html_e('Brak osób kadry.', 'basemgmt'); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped bm-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Nazwisko i imię', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Obóz', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Rola', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Email / tel.', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Blokada', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Ostatnie logowanie', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$camp_map = array_column($camps, 'name', 'id');
				foreach ($staff_list as $m) :
				?>
					<tr>
						<td><strong><?php echo esc_html($m->last_name . ' ' . $m->first_name); ?></strong></td>
						<td><?php echo esc_html($camp_map[$m->camp_id] ?? '—'); ?></td>
						<td><?php echo esc_html($m->role_in_camp ?: '—'); ?></td>
						<td>
							<?php if ($m->email) echo esc_html($m->email); ?>
							<?php if ($m->phone) echo '<br>' . esc_html($m->phone); ?>
						</td>
						<td>
							<span class="bm-badge bm-badge--<?php echo $m->is_active ? 'active' : 'inactive'; ?>">
								<?php echo $m->is_active ? esc_html__('Aktywny', 'basemgmt') : esc_html__('Nieaktywny', 'basemgmt'); ?>
							</span>
						</td>
						<td>
							<?php
							$is_perm   = ! empty($m->permanent_lock) && (int) $m->permanent_lock === 1;
							$is_temp   = ! $is_perm && ! empty($m->locked_until) && strtotime($m->locked_until) > time();
							if ( $is_perm ) {
								echo '<span style="color:#c0392b;font-weight:bold;">🔒 ' . esc_html__('Trwała', 'basemgmt') . '</span>';
							} elseif ( $is_temp ) {
								$mins = ceil((strtotime($m->locked_until) - time()) / 60);
								printf('<span style="color:#856404;">⏳ ' . esc_html__('Temp. (%d min)', 'basemgmt') . '</span>', $mins);
							} else {
								echo '—';
							}
							?>
						</td>
						<td><?php echo $m->last_login ? esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($m->last_login))) : '—'; ?></td>
						<td class="bm-actions">
							<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-staff&action=edit&id={$m->id}")); ?>">
								<?php esc_html_e('Edytuj', 'basemgmt'); ?>
							</a>
							&nbsp;|&nbsp;
							<?php if ($is_perm || $is_temp): ?>
							<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_unlock_staff&id={$m->id}"), "bm_unlock_staff_{$m->id}")); ?>"
							   style="color:#c0392b;font-weight:bold;"
							   onclick="return confirm('<?php esc_attr_e('Odblokować konto? Wymagany reset kodu bezpieczeństwa.', 'basemgmt'); ?>')">
								<?php esc_html_e('Odblokuj', 'basemgmt'); ?>
							</a>
							&nbsp;|&nbsp;
							<?php endif; ?>
							<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_toggle_staff_active&id={$m->id}"), "bm_toggle_staff_{$m->id}")); ?>">
								<?php echo $m->is_active ? esc_html__('Dezaktywuj', 'basemgmt') : esc_html__('Aktywuj', 'basemgmt'); ?>
							</a>
							&nbsp;|&nbsp;
							<a class="bm-danger"
							   href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_staff&id={$m->id}"), "bm_delete_staff_{$m->id}")); ?>"
							   onclick="return confirm('<?php esc_attr_e('Czy na pewno usunąć tę osobę?', 'basemgmt'); ?>')">
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
