<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<h1><?php esc_html_e('Biblioteka dokumentów', 'basemgmt'); ?></h1>
	</div>

	<?php if ( ! empty($templates) ) : ?>
	<div class="notice notice-info inline" style="margin:0 0 16px;padding:10px 14px;">
		<p style="margin:0;">
			<span class="dashicons dashicons-media-document" style="vertical-align:middle;margin-right:4px;"></span>
			<?php esc_html_e('Dostępne szablony dokumentów (zakładka Szablony):', 'basemgmt'); ?>
			<strong>
			<?php
			$labels = array_map( static function ( $tpl ) use ( $doc_types ): string {
				$type = $doc_types[ $tpl->doc_type ] ?? $tpl->doc_type;
				$auto = $tpl->auto_add ? ' ★' : '';
				return esc_html( $tpl->title . ' (' . $type . ')' . $auto );
			}, $templates );
			echo implode( ', ', $labels );
			?>
			</strong>
			&nbsp;—&nbsp;
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=basemgmt-org-doc-templates' ) ); ?>">
				<?php esc_html_e('zarządzaj szablonami', 'basemgmt'); ?> →
			</a>
			<br><small class="bm-muted"><?php esc_html_e('★ = automatycznie dodawany do nowego obozu', 'basemgmt'); ?></small>
		</p>
	</div>
	<?php else : ?>
	<div class="notice notice-warning inline" style="margin:0 0 16px;padding:10px 14px;">
		<p style="margin:0;">
			<?php esc_html_e('Brak zdefiniowanych szablonów dokumentów.', 'basemgmt'); ?>
			&nbsp;<a href="<?php echo esc_url( admin_url( 'admin.php?page=basemgmt-org-doc-templates&action=new' ) ); ?>">
				<?php esc_html_e('Utwórz pierwszy szablon →', 'basemgmt'); ?>
			</a>
		</p>
	</div>
	<?php endif; ?>

	<div class="bm-two-col-layout">
		<!-- ── Add/Edit form ──────────────────────────────────────────────────── -->
		<div class="bm-col-sidebar">
			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Dodaj dokument', 'basemgmt'); ?></h2></div>
				<div class="inside">
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field('bm_save_doc_library'); ?>
						<input type="hidden" name="action" value="bm_save_doc_library">
						<input type="hidden" name="doc_id" value="0">
						<input type="hidden" name="file_id" id="bm_file_id" value="">
						<input type="hidden" name="file_url" id="bm_file_url" value="">
						<input type="hidden" name="file_name" id="bm_file_name" value="">

						<p>
							<label for="bm_doc_title"><strong><?php esc_html_e('Nazwa dokumentu', 'basemgmt'); ?></strong></label><br>
							<input type="text" id="bm_doc_title" name="title" class="widefat" required
								placeholder="<?php esc_attr_e('np. Regulamin pobytu 2025', 'basemgmt'); ?>">
						</p>

						<p>
							<label for="bm_doc_type"><strong><?php esc_html_e('Typ', 'basemgmt'); ?></strong></label><br>
							<select id="bm_doc_type" name="doc_type" class="widefat">
								<?php foreach ( $doc_types as $val => $label ) : ?>
									<option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($label); ?></option>
								<?php endforeach; ?>
							</select>
						</p>

						<p>
							<strong><?php esc_html_e('Plik', 'basemgmt'); ?></strong><br>
							<span id="bm_file_display" class="bm-muted"><?php esc_html_e('Brak wybranego pliku', 'basemgmt'); ?></span><br>
							<button type="button" class="button" id="bm_select_file" style="margin-top:6px;">
								<?php esc_html_e('Wybierz plik z mediów', 'basemgmt'); ?>
							</button>
						</p>

						<p>
							<label>
								<input type="checkbox" name="auto_add" value="1">
								<?php esc_html_e('Automatycznie dodaj do nowego obozu', 'basemgmt'); ?>
							</label>
						</p>

						<p class="submit">
							<button type="submit" class="button button-primary"><?php esc_html_e('Dodaj dokument', 'basemgmt'); ?></button>
						</p>
					</form>
				</div>
			</div>
		</div>

		<!-- ── Document list ──────────────────────────────────────────────────── -->
		<div class="bm-col-main">
			<?php if ( empty($documents) ) : ?>
				<div class="bm-empty-state">
					<span class="dashicons dashicons-media-document" style="font-size:48px;color:#c3c4c7;"></span>
					<p><?php esc_html_e('Brak dokumentów w bibliotece.', 'basemgmt'); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped bm-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
							<th style="width:130px;"><?php esc_html_e('Typ', 'basemgmt'); ?></th>
							<th style="width:90px;"><?php esc_html_e('Auto-dodaj', 'basemgmt'); ?></th>
							<th style="width:100px;"><?php esc_html_e('Plik', 'basemgmt'); ?></th>
							<th style="width:80px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $documents as $doc ) : ?>
							<tr>
								<td><strong><?php echo esc_html($doc->title); ?></strong></td>
								<td>
									<span class="bm-badge bm-badge--doctype-<?php echo esc_attr($doc->doc_type); ?>">
										<?php echo esc_html($doc_types[$doc->doc_type] ?? $doc->doc_type); ?>
									</span>
								</td>
								<td>
									<?php if ( $doc->auto_add ) : ?>
										<span class="bm-badge bm-badge--success">✓</span>
									<?php else : ?>
										<span class="bm-muted">—</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( ! empty($doc->file_url) ) : ?>
										<a href="<?php echo esc_url($doc->file_url); ?>" target="_blank" class="bm-link-small">
											<?php esc_html_e('Pobierz', 'basemgmt'); ?>
										</a>
									<?php else : ?>
										<span class="bm-muted">—</span>
									<?php endif; ?>
								</td>
								<td>
									<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_doc_library&id={$doc->id}"), "bm_delete_doc_library_{$doc->id}")); ?>"
										class="button button-small bm-danger"
										onclick="return confirm('<?php esc_attr_e('Usunąć dokument z biblioteki?', 'basemgmt'); ?>')">
										<?php esc_html_e('Usuń', 'basemgmt'); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>
<script>
(function($) {
	$('#bm_select_file').on('click', function() {
		var frame = wp.media({title: '<?php esc_js(esc_html_e('Wybierz dokument', 'basemgmt')); ?>', multiple: false});
		frame.on('select', function() {
			var att = frame.state().get('selection').first().toJSON();
			$('#bm_file_id').val(att.id);
			$('#bm_file_url').val(att.url);
			$('#bm_file_name').val(att.filename);
			$('#bm_file_display').text(att.filename).removeClass('bm-muted');
		});
		frame.open();
	});
})(jQuery);
</script>
