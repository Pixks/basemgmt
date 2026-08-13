<?php defined('ABSPATH') || exit;
$is_new   = is_null($declaration);
$decl_id  = $is_new ? 0 : (int) $declaration->id;
?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<h1>
			<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-declarations')); ?>" class="bm-back-link">← <?php esc_html_e('Deklaracje', 'basemgmt'); ?></a>
		</h1>
		<h1 style="margin-top:8px;">
			<?php echo $is_new ? esc_html__('Nowa deklaracja', 'basemgmt') : esc_html($declaration->title); ?>
		</h1>
	</div>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field('bm_save_decl_template'); ?>
		<input type="hidden" name="action"       value="bm_save_decl_template">
		<input type="hidden" name="decl_tpl_id"  value="<?php echo esc_attr($decl_id); ?>">
		<input type="hidden" name="file_id"      id="bm_decl_file_id"   value="<?php echo esc_attr($declaration->file_id ?? ''); ?>">
		<input type="hidden" name="file_url"     id="bm_decl_file_url"  value="<?php echo esc_attr($declaration->file_url ?? ''); ?>">
		<input type="hidden" name="file_name"    id="bm_decl_file_name" value="<?php echo esc_attr($declaration->file_name ?? ''); ?>">

		<div class="bm-task-body">
			<div class="bm-task-main">
				<!-- ── Dane podstawowe ──────────────────────────────────────── -->
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Dane deklaracji', 'basemgmt'); ?></h2></div>
					<div class="inside">
						<p>
							<label for="bm_decl_title"><strong><?php esc_html_e('Tytuł', 'basemgmt'); ?></strong></label><br>
							<input type="text" id="bm_decl_title" name="title" class="widefat" required
								value="<?php echo esc_attr($declaration->title ?? ''); ?>"
								placeholder="<?php esc_attr_e('np. Deklaracja uczestnictwa w obozie', 'basemgmt'); ?>">
						</p>
						<p>
							<label for="bm_decl_desc"><strong><?php esc_html_e('Krótki opis', 'basemgmt'); ?></strong></label><br>
							<textarea id="bm_decl_desc" name="description" class="widefat" rows="2"><?php echo esc_textarea($declaration->description ?? ''); ?></textarea>
						</p>

						<!-- ── Plik główny ──────────────────────────────────── -->
						<p><strong><?php esc_html_e('Plik dokumentu (opcjonalny)', 'basemgmt'); ?></strong></p>
						<p>
							<span id="bm_decl_file_display" class="bm-muted">
								<?php echo ! empty($declaration->file_name) ? esc_html($declaration->file_name) : esc_html__('Brak wybranego pliku', 'basemgmt'); ?>
							</span>
							<?php if ( ! empty($declaration->file_url) ) : ?>
								<a href="<?php echo esc_url($declaration->file_url); ?>" target="_blank" style="margin-left:8px;" class="bm-link-small"><?php esc_html_e('Otwórz', 'basemgmt'); ?></a>
							<?php endif; ?>
							<br>
							<button type="button" class="button" id="bm_decl_select_file" style="margin-top:6px;">
								<?php esc_html_e('Wybierz plik z mediów', 'basemgmt'); ?>
							</button>
						</p>

						<!-- ── Treść HTML (opcjonalna) ──────────────────────── -->
						<p><strong><?php esc_html_e('Treść HTML (opcjonalna)', 'basemgmt'); ?></strong></p>
						<p class="description"><?php esc_html_e('Możesz wpisać treść deklaracji bezpośrednio w formacie HTML. Jeśli podasz plik powyżej, będzie on traktowany jako dokument główny.', 'basemgmt'); ?></p>
						<?php
						wp_editor(
							$declaration->html_content ?? '',
							'html_content',
							[
								'textarea_name' => 'html_content',
								'textarea_rows' => 20,
								'teeny'         => false,
								'media_buttons' => false,
							]
						);
						?>
					</div>
				</div>
			</div>

			<div class="bm-task-sidebar">
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Właściwości', 'basemgmt'); ?></h2></div>
					<div class="inside">
						<p>
							<label>
								<input type="checkbox" name="auto_add" value="1" <?php checked(!empty($declaration->auto_add)); ?>>
								<strong><?php esc_html_e('Automatycznie dodaj do nowego obozu', 'basemgmt'); ?></strong>
							</label>
							<p class="description"><?php esc_html_e('Gdy włączone, deklaracja zostanie automatycznie dołączona przy tworzeniu każdego nowego obozu.', 'basemgmt'); ?></p>
						</p>
						<p>
							<label for="bm_decl_order"><strong><?php esc_html_e('Kolejność', 'basemgmt'); ?></strong></label><br>
							<input type="number" id="bm_decl_order" name="sort_order" class="small-text"
								value="<?php echo esc_attr($declaration->sort_order ?? 0); ?>">
						</p>
					</div>
				</div>

				<div class="bm-task-actions">
					<button type="submit" class="button button-primary button-large" style="width:100%;">
						<?php echo $is_new ? esc_html__('Utwórz deklarację', 'basemgmt') : esc_html__('Zapisz zmiany', 'basemgmt'); ?>
					</button>
					<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-declarations')); ?>" class="button button-large" style="width:100%;margin-top:6px;text-align:center;">
						<?php esc_html_e('Anuluj', 'basemgmt'); ?>
					</a>
					<?php if ( ! $is_new ) : ?>
						<hr style="margin:12px 0;">
						<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_decl_template&id={$decl_id}"), "bm_delete_decl_template_{$decl_id}")); ?>"
							class="button bm-danger" style="width:100%;text-align:center;"
							data-bm-confirm="<?php esc_attr_e('Usunąć deklarację?', 'basemgmt'); ?>">
							<?php esc_html_e('Usuń deklarację', 'basemgmt'); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</form>

	<?php if ( ! $is_new ) : ?>
	<!-- ── Załączniki ──────────────────────────────────────────────────────── -->
	<div class="postbox" style="margin-top:24px;">
		<div class="postbox-header" style="display:flex;align-items:center;justify-content:space-between;">
			<h2 class="hndle"><?php esc_html_e('Załączniki', 'basemgmt'); ?></h2>
		</div>
		<div class="inside">
			<?php if ( empty($attachments) ) : ?>
				<p class="bm-muted"><?php esc_html_e('Brak załączników.', 'basemgmt'); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped bm-table" style="margin-bottom:16px;">
					<thead><tr>
						<th><?php esc_html_e('Plik', 'basemgmt'); ?></th>
						<th style="width:80px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $attachments as $att ) : ?>
						<tr>
							<td><a href="<?php echo esc_url($att->file_url); ?>" target="_blank"><?php echo esc_html($att->file_name ?: basename($att->file_url)); ?></a></td>
							<td>
								<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_decl_attachment&att_id={$att->id}&decl_id={$decl_id}"), "bm_delete_decl_attachment_{$att->id}")); ?>"
									class="button button-small bm-danger"
									data-bm-confirm="<?php esc_attr_e('Usunąć załącznik?', 'basemgmt'); ?>">
									<?php esc_html_e('Usuń', 'basemgmt'); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<!-- Add attachment form -->
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<?php wp_nonce_field('bm_add_decl_attachment'); ?>
				<input type="hidden" name="action"  value="bm_add_decl_attachment">
				<input type="hidden" name="decl_id" value="<?php echo esc_attr($decl_id); ?>">
				<input type="hidden" name="file_id"   id="bm_decl_att_file_id"   value="">
				<input type="hidden" name="file_url"  id="bm_decl_att_file_url"  value="">
				<input type="hidden" name="file_name" id="bm_decl_att_file_name" value="">
				<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
					<span id="bm_decl_att_display" class="bm-muted"><?php esc_html_e('Brak wybranego pliku', 'basemgmt'); ?></span>
					<button type="button" class="button" id="bm_decl_add_att_btn"><?php esc_html_e('Wybierz plik', 'basemgmt'); ?></button>
					<button type="submit" class="button button-primary" id="bm_decl_att_submit" disabled><?php esc_html_e('Dodaj załącznik', 'basemgmt'); ?></button>
				</div>
			</form>
		</div>
	</div>
	<?php endif; ?>
</div>
<script>
(function($) {
	// Main file selector
	$('#bm_decl_select_file').on('click', function() {
		var frame = wp.media({ title: '<?php esc_js(esc_html_e('Wybierz plik', 'basemgmt')); ?>', multiple: false });
		frame.on('select', function() {
			var att = frame.state().get('selection').first().toJSON();
			$('#bm_decl_file_id').val(att.id);
			$('#bm_decl_file_url').val(att.url);
			$('#bm_decl_file_name').val(att.filename);
			$('#bm_decl_file_display').text(att.filename).removeClass('bm-muted');
		});
		frame.open();
	});

	// Attachment file selector
	$('#bm_decl_add_att_btn').on('click', function() {
		var frame = wp.media({ title: '<?php esc_js(esc_html_e('Wybierz załącznik', 'basemgmt')); ?>', multiple: false });
		frame.on('select', function() {
			var att = frame.state().get('selection').first().toJSON();
			$('#bm_decl_att_file_id').val(att.id);
			$('#bm_decl_att_file_url').val(att.url);
			$('#bm_decl_att_file_name').val(att.filename);
			$('#bm_decl_att_display').text(att.filename).removeClass('bm-muted');
			$('#bm_decl_att_submit').prop('disabled', false);
		});
		frame.open();
	});
})(jQuery);
</script>
