<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<h1><?php esc_html_e('Diety', 'basemgmt'); ?></h1>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-diets&action=new')); ?>" class="page-title-action">
			+ <?php esc_html_e('Dodaj dietę', 'basemgmt'); ?>
		</a>
	</div>

	<?php if ( empty($diets) ) : ?>
		<div class="bm-empty-state">
			<span class="dashicons dashicons-food" style="font-size:48px;color:#c3c4c7;"></span>
			<p><?php esc_html_e('Brak zdefiniowanych diet.', 'basemgmt'); ?></p>
			<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-diets&action=new')); ?>" class="button button-primary">
				<?php esc_html_e('Dodaj pierwszą dietę', 'basemgmt'); ?>
			</a>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped bm-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
					<th style="width:220px;"><?php esc_html_e('Opis', 'basemgmt'); ?></th>
					<th style="width:90px;"><?php esc_html_e('Śniadanie', 'basemgmt'); ?></th>
					<th style="width:90px;"><?php esc_html_e('Obiad', 'basemgmt'); ?></th>
					<th style="width:90px;"><?php esc_html_e('Kolacja', 'basemgmt'); ?></th>
					<th style="width:90px;"><?php esc_html_e('Suma/dzień', 'basemgmt'); ?></th>
					<th style="width:100px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $diets as $diet ) :
					$costs = \BaseMgmt\Admin\Pages\OrgDietsPage::get_costs((int) $diet->id);
					$total = array_reduce((array) $costs, static fn($c, $r) => $c + (float) $r->cost_netto, 0.0);
				?>
				<tr>
					<td><strong><?php echo esc_html($diet->name); ?></strong></td>
					<td class="bm-muted"><?php echo esc_html($diet->diet_info ?? ''); ?></td>
					<td><?php echo isset($costs['sniadanie']) ? number_format((float)$costs['sniadanie']->cost_netto, 2, ',', ' ') : '—'; ?></td>
					<td><?php echo isset($costs['obiad'])     ? number_format((float)$costs['obiad']->cost_netto,     2, ',', ' ') : '—'; ?></td>
					<td><?php echo isset($costs['kolacja'])   ? number_format((float)$costs['kolacja']->cost_netto,   2, ',', ' ') : '—'; ?></td>
					<td><strong><?php echo number_format($total, 2, ',', ' '); ?></strong></td>
					<td>
						<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-diets&action=edit&id={$diet->id}")); ?>" class="button button-small">
							<?php esc_html_e('Edytuj', 'basemgmt'); ?>
						</a>
						<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_org_diet&id={$diet->id}"), "bm_delete_diet_{$diet->id}")); ?>"
							class="button button-small bm-danger"
							onclick="return confirm('<?php esc_attr_e('Usunąć dietę?', 'basemgmt'); ?>')">
							<?php esc_html_e('Usuń', 'basemgmt'); ?>
						</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
