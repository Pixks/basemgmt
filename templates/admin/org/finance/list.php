<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<h1><?php esc_html_e('Pakiety finansowe', 'basemgmt'); ?></h1>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-finance&action=new')); ?>" class="page-title-action">
			<?php esc_html_e('+ Nowy pakiet', 'basemgmt'); ?>
		</a>
	</div>

	<?php if ( empty($packages) ) : ?>
		<div class="bm-empty-state">
			<span class="dashicons dashicons-money-alt" style="font-size:48px;color:#c3c4c7;"></span>
			<p><?php esc_html_e('Brak pakietów finansowych. Utwórz pierwszy, aby móc wybierać go w obozie.', 'basemgmt'); ?></p>
			<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-finance&action=new')); ?>" class="button button-primary">
				<?php esc_html_e('Utwórz pakiet', 'basemgmt'); ?>
			</a>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped bm-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Nazwa pakietu', 'basemgmt'); ?></th>
					<th style="width:80px;"><?php esc_html_e('Waluta', 'basemgmt'); ?></th>
					<th style="width:100px;"><?php esc_html_e('Domyślny', 'basemgmt'); ?></th>
					<th style="width:120px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $packages as $pkg ) : ?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-finance&action=edit&id={$pkg->id}")); ?>">
									<?php echo esc_html($pkg->name); ?>
								</a>
							</strong>
							<?php if ( ! empty($pkg->description) ) : ?>
								<br><span class="bm-muted"><?php echo esc_html($pkg->description); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html($pkg->currency); ?></td>
						<td>
							<?php if ( $pkg->is_default ) : ?>
								<span class="bm-badge bm-badge--success">★ <?php esc_html_e('Domyślny', 'basemgmt'); ?></span>
							<?php else : ?>
								<span class="bm-muted">—</span>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-finance&action=edit&id={$pkg->id}")); ?>" class="button button-small">
								<?php esc_html_e('Edytuj', 'basemgmt'); ?>
							</a>
							<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_payment_package&id={$pkg->id}"), "bm_delete_payment_package_{$pkg->id}")); ?>"
								class="button button-small bm-danger"
								onclick="return confirm('<?php esc_attr_e('Usunąć pakiet?', 'basemgmt'); ?>')">
								<?php esc_html_e('Usuń', 'basemgmt'); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
