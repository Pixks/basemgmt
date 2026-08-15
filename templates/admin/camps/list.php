<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<h1><?php esc_html_e('Obozy', 'basemgmt'); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps&action=new')); ?>">
			+ <?php esc_html_e('Dodaj nowy', 'basemgmt'); ?>
		</a>
	</div>

	<!-- Filtry statusu -->
	<ul class="subsubsub" style="margin-bottom:0;">
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

	<!-- Filtry wyszukiwania -->
	<form method="get" style="margin:12px 0 16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
		<input type="hidden" name="page" value="basemgmt-camps">
		<input type="search" name="s" value="<?php echo esc_attr($search); ?>"
			placeholder="<?php esc_attr_e('Szukaj obozu…', 'basemgmt'); ?>"
			style="min-width:220px;">
		<select name="filter_stage">
			<option value=""><?php esc_html_e('Wszystkie etapy', 'basemgmt'); ?></option>
			<?php foreach ( $stage_options as $v => $l ) : ?>
				<option value="<?php echo esc_attr($v); ?>" <?php selected($process_stage, $v); ?>><?php echo esc_html($l); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="filter_readiness">
			<?php foreach ( $readiness_map as $v => $l ) : ?>
				<option value="<?php echo esc_attr($v); ?>" <?php selected($readiness, $v); ?>><?php echo esc_html($l); ?></option>
			<?php endforeach; ?>
		</select>
		<label style="display:flex;align-items:center;gap:5px;font-weight:normal;">
			<input type="checkbox" name="filter_attention" value="1" <?php checked($needs_attention, 1); ?>>
			<?php esc_html_e('Wymaga reakcji', 'basemgmt'); ?>
		</label>
		<button type="submit" class="button"><?php esc_html_e('Filtruj', 'basemgmt'); ?></button>
	</form>

	<?php if ( empty($camps) ) : ?>
		<div class="bm-empty-state" style="padding:40px 0;">
			<span class="dashicons dashicons-calendar-alt" style="font-size:48px;color:#c3c4c7;display:block;text-align:center;"></span>
			<p style="text-align:center;color:#8c8f94;"><?php esc_html_e('Brak obozów spełniających filtry.', 'basemgmt'); ?></p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped bm-table bm-camps-list">
			<thead>
				<tr>
					<th style="width:28%;"><?php esc_html_e('Obóz', 'basemgmt'); ?></th>
					<th style="width:20%;"><?php esc_html_e('Organizator', 'basemgmt'); ?></th>
					<th style="width:15%;"><?php esc_html_e('Etap / Stan', 'basemgmt'); ?></th>
					<th style="width:15%;"><?php esc_html_e('Termin', 'basemgmt'); ?></th>
					<th style="width:12%;"><?php esc_html_e('Gotowość', 'basemgmt'); ?></th>
					<th style="width:10%;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $camps as $camp ) :
					$readiness_done    = (int) ($camp->readiness_done  ?? 0);
					$readiness_total   = (int) ($camp->readiness_total ?? 0);
					$readiness_pct     = $readiness_total > 0 ? round($readiness_done / $readiness_total * 100) : 0;
					$readiness_overdue = (int) ($camp->readiness_overdue ?? 0);
					$stage_label       = $stage_options[$camp->process_stage] ?? $camp->process_stage;
					$risk_label        = $risk_levels[$camp->risk_level] ?? '';

					$has_attention = ! empty($camp->needs_attention);
					$row_class     = $has_attention ? 'bm-row--attention' : '';
				?>
				<tr class="<?php echo esc_attr($row_class); ?>">

					<!-- Obóz -->
					<td>
						<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-camps&action=edit&id={$camp->id}")); ?>"
						   style="font-weight:600;font-size:14px;color:#1d2327;text-decoration:none;">
							<?php echo esc_html($camp->name); ?>
						</a>
						<div style="margin-top:3px;display:flex;gap:4px;flex-wrap:wrap;">
							<?php if ( $has_attention ) : ?>
								<span class="bm-badge bm-badge--critical" style="font-size:10px;"><?php esc_html_e('Wymaga reakcji', 'basemgmt'); ?></span>
							<?php endif; ?>
							<?php if ( $readiness_overdue > 0 ) : ?>
								<span class="bm-badge bm-badge--pending" style="font-size:10px;">
									<?php printf(esc_html__('%d po terminie', 'basemgmt'), $readiness_overdue); ?>
								</span>
							<?php endif; ?>
							<?php if ( ! empty($camp->risk_level) && $camp->risk_level !== 'low' ) : ?>
								<span class="bm-badge bm-badge--<?php echo esc_attr($camp->risk_level); ?>" style="font-size:10px;">
									<?php echo esc_html($risk_label); ?>
								</span>
							<?php endif; ?>
						</div>
					</td>

					<!-- Organizator -->
					<td>
						<?php if ( ! empty($camp->organization_name) ) : ?>
							<span style="font-weight:500;"><?php echo esc_html($camp->organization_name); ?></span>
							<?php if ( ! empty($camp->contact_person) ) : ?>
								<br><span class="bm-muted" style="font-size:12px;"><?php echo esc_html($camp->contact_person); ?></span>
							<?php endif; ?>
						<?php else : ?>
							<span class="bm-muted">—</span>
						<?php endif; ?>
					</td>

					<!-- Etap / Stan -->
					<td>
						<span class="bm-badge bm-badge--<?php echo esc_attr($camp->process_stage ?? 'default'); ?>">
							<?php echo esc_html($stage_label); ?>
						</span>
						<?php
						$status_label = $statuses[$camp->status ?? ''] ?? ($camp->status ?? '');
						if ( $status_label ) : ?>
						<br><span class="bm-muted" style="font-size:11px;margin-top:2px;display:inline-block;">
							<?php echo esc_html($status_label); ?>
						</span>
						<?php endif; ?>
					</td>

					<!-- Termin -->
					<td style="font-size:12px;">
						<?php if ( ! empty($camp->start_date) ) : ?>
							<span style="font-weight:500;"><?php echo esc_html(substr($camp->start_date, 0, 10)); ?></span>
							<?php if ( ! empty($camp->end_date) ) : ?>
								<span class="bm-muted"> → <?php echo esc_html(substr($camp->end_date, 0, 10)); ?></span>
							<?php endif; ?>
						<?php else : ?>
							<span class="bm-muted">—</span>
						<?php endif; ?>
						<?php if ( ! empty($camp->next_action_due_date) ) : ?>
							<br><span class="bm-muted" style="font-size:11px;">
								<?php esc_html_e('Działanie:', 'basemgmt'); ?> <?php echo esc_html(substr($camp->next_action_due_date, 0, 10)); ?>
							</span>
						<?php endif; ?>
					</td>

					<!-- Gotowość -->
					<td>
						<?php if ( $readiness_total > 0 ) : ?>
							<div style="display:flex;align-items:center;gap:6px;">
								<div style="flex:1;background:#e0e0e0;border-radius:4px;height:6px;min-width:50px;">
									<div style="width:<?php echo esc_attr($readiness_pct); ?>%;background:<?php echo $readiness_pct >= 100 ? '#46b450' : '#2271b1'; ?>;border-radius:4px;height:6px;"></div>
								</div>
								<span style="font-size:11px;font-weight:600;white-space:nowrap;"><?php echo esc_html($readiness_pct); ?>%</span>
							</div>
							<span class="bm-muted" style="font-size:11px;"><?php echo esc_html($readiness_done); ?>/<?php echo esc_html($readiness_total); ?></span>
						<?php else : ?>
							<span class="bm-muted" style="font-size:12px;">—</span>
						<?php endif; ?>
					</td>

					<!-- Akcje -->
					<td class="bm-actions" style="white-space:nowrap;">
						<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-camps&action=edit&id={$camp->id}")); ?>" class="button button-small">
							<?php esc_html_e('Teczka', 'basemgmt'); ?>
						</a>
						<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-staff&filter_camp={$camp->id}")); ?>" class="button button-small">
							<?php esc_html_e('Kadra', 'basemgmt'); ?>
						</a>
						<a class="button button-small bm-danger"
						   href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_camp&id={$camp->id}"), "bm_delete_camp_{$camp->id}")); ?>"
						   data-bm-confirm="<?php esc_attr_e('Czy na pewno usunąć ten obóz?', 'basemgmt'); ?>">
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
<style>
.bm-camps-list td { vertical-align: middle; padding: 10px 8px; }
.bm-row--attention > td:first-child { border-left: 3px solid #d63638; }
</style>
