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
					<th style="width:200px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
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
							<?php if ( ! empty($camps) ) : ?>
								<button type="button" class="button button-small button-primary bm-push-decl-btn"
									data-decl-id="<?php echo esc_attr($decl->id); ?>"
									data-decl-title="<?php echo esc_attr($decl->title); ?>">
									<?php esc_html_e('Prześlij do obozu', 'basemgmt'); ?>
								</button>
							<?php endif; ?>
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

<?php if ( ! empty($camps) ) : ?>
<!-- Modal: Prześlij deklarację do obozu -->
<div id="bm-modal-push-decl" style="display:none;" class="bm-modal-overlay">
	<div class="bm-modal">
		<div class="bm-modal-header">
			<h3><?php esc_html_e('Prześlij deklarację do obozu', 'basemgmt'); ?></h3>
			<button type="button" class="bm-modal-close">✕</button>
		</div>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('bm_push_decl_to_camp'); ?>
			<input type="hidden" name="action"  value="bm_push_decl_to_camp">
			<input type="hidden" name="decl_id" id="bm-push-decl-id" value="">
			<div class="bm-modal-body">
				<p id="bm-push-decl-name" style="font-weight:600;margin-bottom:12px;"></p>
				<p>
					<label for="bm-push-camp-select"><strong><?php esc_html_e('Wybierz obóz:', 'basemgmt'); ?></strong></label><br>
					<select name="camp_id" id="bm-push-camp-select" class="widefat" style="margin-top:6px;">
						<option value=""><?php esc_html_e('— wybierz obóz —', 'basemgmt'); ?></option>
						<?php foreach ( $camps as $camp ) : ?>
							<option value="<?php echo esc_attr($camp->id); ?>">
								<?php echo esc_html($camp->name); ?>
								<?php if ( ! empty($camp->start_date) ) : ?>
									(<?php echo esc_html(substr($camp->start_date, 0, 10)); ?>)
								<?php endif; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
			</div>
			<div class="bm-modal-footer">
				<button type="submit" class="button button-primary" id="bm-push-decl-submit" disabled>
					<?php esc_html_e('Prześlij', 'basemgmt'); ?>
				</button>
				<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
			</div>
		</form>
	</div>
</div>
<script>
(function($) {
	$(document).on('click', '.bm-push-decl-btn', function() {
		var $btn = $(this);
		$('#bm-push-decl-id').val($btn.data('decl-id'));
		$('#bm-push-decl-name').text($btn.data('decl-title'));
		$('#bm-push-camp-select').val('');
		$('#bm-push-decl-submit').prop('disabled', true);
		$('#bm-modal-push-decl').show();
	});

	$(document).on('change', '#bm-push-camp-select', function() {
		$('#bm-push-decl-submit').prop('disabled', $(this).val() === '');
	});

	$(document).on('click', '#bm-modal-push-decl .bm-modal-close', function() {
		$('#bm-modal-push-decl').hide();
	});

	$(document).on('click', '#bm-modal-push-decl.bm-modal-overlay', function(e) {
		if ($(e.target).is('.bm-modal-overlay')) {
			$(this).hide();
		}
	});
})(jQuery);
</script>
<?php endif; ?>
