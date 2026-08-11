<?php defined('ABSPATH') || exit;
$is_new  = is_null($template);
$tpl_id  = $is_new ? 0 : (int) $template->id;
?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<h1>
			<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org')); ?>" class="bm-back-link">← <?php esc_html_e('Szablony', 'basemgmt'); ?></a>
		</h1>
		<h1 style="margin-top:8px;">
			<?php echo $is_new ? esc_html__('Nowy szablon', 'basemgmt') : esc_html($template->title); ?>
		</h1>
	</div>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field('bm_save_doc_template'); ?>
		<input type="hidden" name="action" value="bm_save_doc_template">
		<input type="hidden" name="template_id" value="<?php echo esc_attr($tpl_id); ?>">

		<div class="bm-task-body">
			<div class="bm-task-main">
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Zawartość szablonu', 'basemgmt'); ?></h2></div>
					<div class="inside">
						<p>
							<label for="bm_tpl_title"><strong><?php esc_html_e('Tytuł szablonu', 'basemgmt'); ?></strong></label><br>
							<input type="text" id="bm_tpl_title" name="title" class="widefat" required
								value="<?php echo esc_attr($template->title ?? ''); ?>"
								placeholder="<?php esc_attr_e('np. Umowa o świadczenie usług wypoczynkowych', 'basemgmt'); ?>">
						</p>

						<p><strong><?php esc_html_e('Treść HTML szablonu', 'basemgmt'); ?></strong></p>
						<p class="description"><?php esc_html_e('Dostępne zmienne: {{camp_name}}, {{organizer_name}}, {{organizer_email}}, {{start_date}}, {{end_date}}, {{participants}}', 'basemgmt'); ?></p>
						<?php
						wp_editor(
							$template->html_content ?? '',
							'html_content',
							[
								'textarea_name' => 'html_content',
								'textarea_rows' => 30,
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
							<label for="bm_tpl_type"><strong><?php esc_html_e('Typ dokumentu', 'basemgmt'); ?></strong></label><br>
							<select id="bm_tpl_type" name="doc_type" class="widefat">
								<?php foreach ( $doc_types as $val => $label ) : ?>
									<option value="<?php echo esc_attr($val); ?>" <?php selected($template->doc_type ?? 'contract', $val); ?>>
										<?php echo esc_html($label); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</p>

						<p>
							<label>
								<input type="checkbox" name="auto_add" value="1" <?php checked(!empty($template->auto_add)); ?>>
								<?php esc_html_e('Automatycznie dodaj do nowego obozu', 'basemgmt'); ?>
							</label>
						</p>

						<p>
							<label for="bm_tpl_order"><strong><?php esc_html_e('Kolejność', 'basemgmt'); ?></strong></label><br>
							<input type="number" id="bm_tpl_order" name="sort_order" class="small-text"
								value="<?php echo esc_attr($template->sort_order ?? 0); ?>">
						</p>
					</div>
				</div>

				<div class="bm-task-actions">
					<button type="submit" class="button button-primary button-large" style="width:100%;">
						<?php echo $is_new ? esc_html__('Utwórz szablon', 'basemgmt') : esc_html__('Zapisz zmiany', 'basemgmt'); ?>
					</button>
					<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org')); ?>" class="button button-large" style="width:100%;margin-top:6px;text-align:center;">
						<?php esc_html_e('Anuluj', 'basemgmt'); ?>
					</a>
					<?php if ( ! $is_new ) : ?>
						<hr style="margin:12px 0;">
						<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_doc_template&id={$tpl_id}"), "bm_delete_doc_template_{$tpl_id}")); ?>"
							class="button bm-danger" style="width:100%;text-align:center;"
							onclick="return confirm('<?php esc_attr_e('Usunąć szablon?', 'basemgmt'); ?>')">
							<?php esc_html_e('Usuń szablon', 'basemgmt'); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</form>
</div>
