<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<h1><?php esc_html_e('Deklaracje', 'basemgmt'); ?></h1>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-declarations&action=new')); ?>" class="page-title-action">
			<?php esc_html_e('+ Nowa deklaracja', 'basemgmt'); ?>
		</a>
	</div>

	<p class="description" style="margin-bottom:16px;">
		<?php esc_html_e('Deklaracje to gotowe dokumenty (pliki lub treść HTML) widoczne w teczce obozu. Deklaracje z włączonym "Auto-dodaj" są automatycznie dodawane do każdego nowego obozu.', 'basemgmt'); ?>
	</p>

	<?php if ( empty($declarations) ) : ?>
		<div class="bm-empty-state">
			<span class="dashicons dashicons-media-document" style="font-size:48px;color:#c3c4c7;"></span>
			<p><?php esc_html_e('Brak deklaracji. Utwórz pierwszą.', 'basemgmt'); ?></p>
			<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-declarations&action=new')); ?>" class="button button-primary">
				<?php esc_html_e('Utwórz deklarację', 'basemgmt'); ?>
			</a>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped bm-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Tytuł', 'basemgmt'); ?></th>
					<th style="width:200px;"><?php esc_html_e('Opis', 'basemgmt'); ?></th>
					<th style="width:80px;"><?php esc_html_e('Plik', 'basemgmt'); ?></th>
					<th style="width:100px;"><?php esc_html_e('Auto-dodaj', 'basemgmt'); ?></th>
					<th style="width:80px;"><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
					<th style="width:140px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $declarations as $decl ) : ?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-declarations&action=edit&id={$decl->id}")); ?>">
									<?php echo esc_html($decl->title); ?>
								</a>
							</strong>
						</td>
						<td class="bm-muted"><?php echo esc_html(mb_strimwidth($decl->description ?? '', 0, 60, '…')); ?></td>
						<td>
							<?php if ( ! empty($decl->file_url) ) : ?>
								<a href="<?php echo esc_url($decl->file_url); ?>" target="_blank" class="bm-link-small"><?php esc_html_e('Pobierz', 'basemgmt'); ?></a>
							<?php elseif ( ! empty($decl->html_content) ) : ?>
								<span class="bm-badge bm-badge--normal"><?php esc_html_e('HTML', 'basemgmt'); ?></span>
							<?php else : ?>
								<span class="bm-muted">—</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $decl->auto_add ) : ?>
								<span class="bm-badge bm-badge--success">✓ <?php esc_html_e('Tak', 'basemgmt'); ?></span>
							<?php else : ?>
								<span class="bm-muted">—</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html($decl->sort_order); ?></td>
						<td>
							<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-declarations&action=edit&id={$decl->id}")); ?>" class="button button-small">
								<?php esc_html_e('Edytuj', 'basemgmt'); ?>
							</a>
							<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_decl_template&id={$decl->id}"), "bm_delete_decl_template_{$decl->id}")); ?>"
								class="button button-small bm-danger"
								data-bm-confirm="<?php esc_attr_e('Usunąć deklarację?', 'basemgmt'); ?>">
								<?php esc_html_e('Usuń', 'basemgmt'); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
