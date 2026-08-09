<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e('Obozy', 'basemgmt'); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps&action=new')); ?>">
		<?php esc_html_e('Dodaj nowy', 'basemgmt'); ?>
	</a>

	<ul class="subsubsub">
		<?php foreach ( $statuses as $slug => $label ) : ?>
			<li>
				<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps' . ($slug !== 'all' ? '&filter_status=' . $slug : ''))); ?>"
				   class="<?php echo ($status === $slug || ($slug === 'all' && $status === '')) ? 'current' : ''; ?>">
					<?php echo esc_html($label); ?>
				</a>
				<?php echo $slug !== array_key_last($statuses) ? ' |' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<form method="get" class="bm-filter-grid" style="margin:16px 0 20px;">
		<input type="hidden" name="page" value="basemgmt-camps">
		<input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Szukaj obozu lub organizatora…', 'basemgmt'); ?>">

		<select name="filter_stage">
			<option value=""><?php esc_html_e('Dowolny etap procesu', 'basemgmt'); ?></option>
			<?php foreach ( $stage_options as $stage_value => $stage_label ) : ?>
				<option value="<?php echo esc_attr($stage_value); ?>" <?php selected($process_stage, $stage_value); ?>>
					<?php echo esc_html($stage_label); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<select name="filter_readiness">
			<?php foreach ( $readiness_map as $readiness_value => $readiness_label ) : ?>
				<option value="<?php echo esc_attr($readiness_value); ?>" <?php selected($readiness, $readiness_value); ?>>
					<?php echo esc_html($readiness_label); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label class="bm-inline-check">
			<input type="checkbox" name="filter_attention" value="1" <?php checked($needs_attention, 1); ?>>
			<?php esc_html_e('Wymaga reakcji', 'basemgmt'); ?>
		</label>

		<button type="submit" class="button"><?php esc_html_e('Filtruj', 'basemgmt'); ?></button>
	</form>

	<?php if ( empty($camps) ) : ?>
		<p><?php esc_html_e('Brak obozów spełniających filtry.', 'basemgmt'); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped bm-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Obóz', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Organizator', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Termin', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Status pobytu', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Etap procesu', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Gotowość', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Ryzyko', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $camps as $camp ) : ?>
					<?php
					$readiness_percent = (int) (
						((int) ($camp->readiness_total ?? 0)) > 0
							? round((((int) ($camp->readiness_done ?? 0)) / ((int) $camp->readiness_total)) * 100)
							: 0
					);
					$stage_label       = $stage_options[$camp->process_stage] ?? $camp->process_stage;
					$risk_label        = $risk_levels[$camp->risk_level] ?? $camp->risk_level;
					?>
					<tr>
						<td>
							<strong><?php echo esc_html($camp->name); ?></strong>
							<?php if ( ! empty($camp->needs_attention) ) : ?>
								<br><span class="bm-badge bm-badge--critical"><?php esc_html_e('Wymaga reakcji', 'basemgmt'); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php echo esc_html($camp->organization_name ?: '—'); ?>
							<?php if ( ! empty($camp->contact_person) ) : ?>
								<br><span class="description"><?php echo esc_html($camp->contact_person); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php echo esc_html($camp->start_date); ?><br>
							<span class="description"><?php echo esc_html($camp->end_date); ?></span>
						</td>
						<td><span class="bm-badge bm-badge--<?php echo esc_attr($camp->status); ?>"><?php echo esc_html($camp->status); ?></span></td>
						<td><span class="bm-badge bm-badge--<?php echo esc_attr($camp->process_stage); ?>"><?php echo esc_html($stage_label); ?></span></td>
						<td>
							<strong><?php echo esc_html($readiness_percent); ?>%</strong>
							<?php if ( (int) ($camp->readiness_total ?? 0) > 0 ) : ?>
								<br><span class="description">
									<?php
									printf(
										esc_html__('%1$d / %2$d zadań', 'basemgmt'),
										(int) ($camp->readiness_done ?? 0),
										(int) ($camp->readiness_total ?? 0)
									);
									?>
								</span>
							<?php endif; ?>
							<?php if ( (int) ($camp->readiness_overdue ?? 0) > 0 ) : ?>
								<br><span class="bm-badge bm-badge--pending">
									<?php
									printf(
										esc_html__('%d po terminie', 'basemgmt'),
										(int) $camp->readiness_overdue
									);
									?>
								</span>
							<?php endif; ?>
						</td>
						<td><span class="bm-badge bm-badge--<?php echo esc_attr($camp->risk_level); ?>"><?php echo esc_html($risk_label); ?></span></td>
						<td class="bm-actions">
							<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-camps&action=edit&id={$camp->id}")); ?>">
								<?php esc_html_e('Teczka', 'basemgmt'); ?>
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

		<?php if ( $pages > 1 ) : ?>
			<div class="tablenav bottom">
				<?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $page, 'total' => $pages]); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
