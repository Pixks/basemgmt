<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<h1><?php esc_html_e('Szablony deklaracji', 'basemgmt'); ?></h1>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-declarations&action=new')); ?>" class="page-title-action">
			<?php esc_html_e('+ Nowy szablon', 'basemgmt'); ?>
		</a>
	</div>

	<p class="description" style="margin-bottom:16px;">
		<?php esc_html_e('Szablony deklaracji są widoczne w teczce obozu (zakładka Dokumenty → sekcja Deklaracje). Szablony z włączonym "Auto-dodaj" są automatycznie dodawane do każdego nowego obozu.', 'basemgmt'); ?>
	</p>

	<?php if ( empty($templates) ) : ?>
		<div class="bm-empty-state">
			<span class="dashicons dashicons-media-document" style="font-size:48px;color:#c3c4c7;"></span>
			<p><?php esc_html_e('Brak szablonów deklaracji. Utwórz pierwszy.', 'basemgmt'); ?></p>
			<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-declarations&action=new')); ?>" class="button button-primary">
				<?php esc_html_e('Utwórz szablon', 'basemgmt'); ?>
			</a>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped bm-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Tytuł', 'basemgmt'); ?></th>
					<th style="width:200px;"><?php esc_html_e('Opis', 'basemgmt'); ?></th>
					<th style="width:100px;"><?php esc_html_e('Auto-dodaj', 'basemgmt'); ?></th>
					<th style="width:80px;"><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
					<th style="width:140px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $templates as $tpl ) : ?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-declarations&action=edit&id={$tpl->id}")); ?>">
									<?php echo esc_html($tpl->title); ?>
								</a>
							</strong>
						</td>
						<td class="bm-muted"><?php echo esc_html(mb_strimwidth($tpl->description ?? '', 0, 60, '…')); ?></td>
						<td>
							<?php if ( $tpl->auto_add ) : ?>
								<span class="bm-badge bm-badge--success">✓ <?php esc_html_e('Tak', 'basemgmt'); ?></span>
							<?php else : ?>
								<span class="bm-muted">—</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html($tpl->sort_order); ?></td>
						<td>
							<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-declarations&action=edit&id={$tpl->id}")); ?>" class="button button-small">
								<?php esc_html_e('Edytuj', 'basemgmt'); ?>
							</a>
							<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_decl_template&id={$tpl->id}"), "bm_delete_decl_template_{$tpl->id}")); ?>"
								class="button button-small bm-danger"
								data-bm-confirm="<?php esc_attr_e('Usunąć szablon deklaracji?', 'basemgmt'); ?>">
								<?php esc_html_e('Usuń', 'basemgmt'); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
