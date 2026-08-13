<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<h1><?php esc_html_e('Biblioteka dokumentów', 'basemgmt'); ?></h1>
	</div>

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
						<?php foreach ( $documents as $doc ) : 
							$doc_atts = $attachments_by_doc[(int) $doc->id] ?? [];
						?>
							<tr>
								<td>
									<strong><?php echo esc_html($doc->title); ?></strong>
									<?php if ( ! empty($doc_atts) ) : ?>
										<br><span class="bm-muted" style="font-size:11px;">
											<?php
											$att_links = array_map(
												fn($a) => '<a href="' . esc_url($a->file_url) . '" target="_blank">' . esc_html($a->file_name ?: basename($a->file_url)) . '</a>',
												$doc_atts
											);
											echo implode(', ', $att_links); // phpcs:ignore WordPress.Security.EscapeOutput
											?>
										</span>
									<?php endif; ?>
								</td>
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
									<button type="button" class="button button-small bm-modal-open"
										data-modal="bm-modal-add-doc-att"
										data-doc-id="<?php echo esc_attr($doc->id); ?>"
										data-doc-title="<?php echo esc_attr($doc->title); ?>">
										<?php esc_html_e('+ Załącznik', 'basemgmt'); ?>
									</button>
									<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_doc_library&id={$doc->id}"), "bm_delete_doc_library_{$doc->id}")); ?>"
										class="button button-small bm-danger"
										data-bm-confirm="<?php esc_attr_e('Usunąć dokument z biblioteki?', 'basemgmt'); ?>">
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

	<!-- ── Templates reference table ──────────────────────────────────────── -->
	<div class="postbox" style="margin-top:18px;">
		<div class="postbox-header" style="display:flex;align-items:center;justify-content:space-between;">
			<h2 class="hndle" style="margin:0;">
				<span class="dashicons dashicons-media-document" style="vertical-align:middle;margin-right:6px;"></span>
				<?php esc_html_e('Dostępne szablony dokumentów', 'basemgmt'); ?>
			</h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=basemgmt-org-doc-templates' ) ); ?>"
				class="button button-small" style="margin-right:12px;">
				<?php esc_html_e('Zarządzaj szablonami →', 'basemgmt'); ?>
			</a>
		</div>
		<div class="inside" style="padding:0;">
			<?php if ( ! empty($templates) ) : ?>
			<table class="wp-list-table widefat fixed striped bm-table" style="border:none;box-shadow:none;">
				<thead>
					<tr>
						<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
						<th style="width:150px;"><?php esc_html_e('Typ', 'basemgmt'); ?></th>
						<th style="width:110px;"><?php esc_html_e('Auto-dodaj', 'basemgmt'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $templates as $tpl ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( admin_url( "admin.php?page=basemgmt-org-doc-templates&action=edit&id={$tpl->id}" ) ); ?>">
								<?php echo esc_html($tpl->title); ?>
							</a>
						</td>
						<td>
							<span class="bm-badge bm-badge--doctype-<?php echo esc_attr($tpl->doc_type); ?>">
								<?php echo esc_html($doc_types[$tpl->doc_type] ?? $tpl->doc_type); ?>
							</span>
						</td>
						<td>
							<?php if ( $tpl->auto_add ) : ?>
								<span class="bm-badge bm-badge--success">✓</span>
							<?php else : ?>
								<span class="bm-muted">—</span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?>
			<p style="padding:12px 16px;margin:0;color:#6b7280;">
				<?php esc_html_e('Brak zdefiniowanych szablonów dokumentów.', 'basemgmt'); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=basemgmt-org-doc-templates&action=new' ) ); ?>">
					<?php esc_html_e('Utwórz pierwszy szablon →', 'basemgmt'); ?>
				</a>
			</p>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Modal: Dodaj załącznik do dokumentu -->
<div id="bm-modal-add-doc-att" style="display:none;" class="bm-modal-overlay">
	<div class="bm-modal">
		<div class="bm-modal-header">
			<h3><?php esc_html_e('Dodaj załącznik', 'basemgmt'); ?></h3>
			<button type="button" class="bm-modal-close">✕</button>
		</div>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('bm_add_doc_library_attachment'); ?>
			<input type="hidden" name="action"    value="bm_add_doc_library_attachment">
			<input type="hidden" name="doc_id"    id="bm-doc-att-doc-id"   value="">
			<input type="hidden" name="file_id"   id="bm-doc-att-file-id"   value="">
			<input type="hidden" name="file_url"  id="bm-doc-att-file-url"  value="">
			<input type="hidden" name="file_name" id="bm-doc-att-file-name" value="">
			<div class="bm-modal-body">
				<p id="bm-doc-att-title" style="font-weight:600;"></p>
				<p>
					<span id="bm-doc-att-display" class="bm-muted"><?php esc_html_e('Brak wybranego pliku', 'basemgmt'); ?></span><br>
					<button type="button" class="button" id="bm-doc-att-select" style="margin-top:6px;"><?php esc_html_e('Wybierz plik', 'basemgmt'); ?></button>
				</p>
			</div>
			<div class="bm-modal-footer">
				<button type="submit" class="button button-primary" id="bm-doc-att-submit" disabled><?php esc_html_e('Dodaj załącznik', 'basemgmt'); ?></button>
				<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
			</div>
		</form>
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

	// Attachment modal
	$(document).on('click', '.bm-modal-open[data-modal="bm-modal-add-doc-att"]', function() {
		var docId    = $(this).data('doc-id');
		var docTitle = $(this).data('doc-title');
		$('#bm-doc-att-doc-id').val(docId);
		$('#bm-doc-att-title').text(docTitle);
		$('#bm-doc-att-file-id, #bm-doc-att-file-url, #bm-doc-att-file-name').val('');
		$('#bm-doc-att-display').text('<?php esc_html_e('Brak wybranego pliku', 'basemgmt'); ?>').addClass('bm-muted');
		$('#bm-doc-att-submit').prop('disabled', true);
	});

	$('#bm-doc-att-select').on('click', function() {
		var frame = wp.media({ title: '<?php esc_js(esc_html_e('Wybierz załącznik', 'basemgmt')); ?>', multiple: false });
		frame.on('select', function() {
			var att = frame.state().get('selection').first().toJSON();
			$('#bm-doc-att-file-id').val(att.id);
			$('#bm-doc-att-file-url').val(att.url);
			$('#bm-doc-att-file-name').val(att.filename);
			$('#bm-doc-att-display').text(att.filename).removeClass('bm-muted');
			$('#bm-doc-att-submit').prop('disabled', false);
		});
		frame.open();
	});
})(jQuery);
</script>
