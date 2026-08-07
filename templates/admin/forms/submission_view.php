<?php
defined('ABSPATH') || exit;
$back_url     = add_query_arg(['page' => 'basemgmt-forms', 'view' => 'submissions'], admin_url('admin.php'));
$statuses     = \BaseMgmt\Modules\Forms\SubmissionRepository::STATUSES;
$priorities   = \BaseMgmt\Modules\Forms\SubmissionRepository::PRIORITIES;
$cats         = \BaseMgmt\Modules\Forms\FormRepository::CATEGORIES;
$users_map    = array_column($wp_users, null, 'ID');
$snapshot_frm = $form_snapshot['form']   ?? [];
$snapshot_flds= $form_snapshot['fields'] ?? [];
?>
<div class="wrap">
	<h1><?php printf(esc_html__('Zgłoszenie #%d', 'basemgmt'), (int) $submission->id); ?></h1>
	<a href="<?php echo esc_url($back_url); ?>" class="button">&larr; <?php esc_html_e('Powrót', 'basemgmt'); ?></a>
	<hr class="wp-header-end">

	<div style="display:grid;grid-template-columns:1fr 400px;gap:24px;align-items:start">

		<!-- ── Left: submission data ─────────────────────────────── -->
		<div>
			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Dane zgłoszenia', 'basemgmt'); ?></h2></div>
				<div class="inside">
					<p><strong><?php esc_html_e('Formularz:', 'basemgmt'); ?></strong> <?php echo esc_html($snapshot_frm['name'] ?? ('Form #' . $submission->form_id)); ?></p>
					<p><strong><?php esc_html_e('Kategoria:', 'basemgmt'); ?></strong> <?php echo esc_html($cats[$submission->category] ?? $submission->category); ?></p>
					<p><strong><?php esc_html_e('Data:', 'basemgmt'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($submission->created_at))); ?></p>

					<?php if ( ! empty($snapshot_flds) ) : ?>
						<hr>
						<table class="widefat striped">
							<tbody>
							<?php foreach ( $snapshot_flds as $fld ) :
								$key = $fld['field_key'];
								$val = $submission_data[$key] ?? '';
								if ( is_array($val) ) $val = implode(', ', $val);
							?>
								<tr>
									<th style="width:35%"><?php echo esc_html($fld['label']); ?>
										<?php if ( ! empty($fld['is_required']) ) echo ' <span style="color:red">*</span>'; ?>
									</th>
									<td><?php echo esc_html((string) $val); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p><em><?php esc_html_e('Brak struktury formularza w snapshocie.', 'basemgmt'); ?></em></p>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty($attachments) ) : ?>
			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Załączniki', 'basemgmt'); ?></h2></div>
				<div class="inside">
					<ul>
					<?php foreach ( $attachments as $att ) :
						$dl_url = add_query_arg([
							'action'  => 'bm_download_attachment',
							'att_id'  => $att->id,
							'_wpnonce'=> wp_create_nonce('bm_download_attachment'),
						], admin_url('admin-post.php'));
					?>
						<li>
							<a href="<?php echo esc_url($dl_url); ?>">
								<?php echo esc_html($att->original_name); ?>
							</a>
							<small>(<?php echo esc_html(size_format((int) $att->file_size)); ?>)</small>
						</li>
					<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( ! empty($history) ) : ?>
			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Historia zmian', 'basemgmt'); ?></h2></div>
				<div class="inside">
					<table class="widefat striped">
						<thead><tr>
							<th><?php esc_html_e('Data', 'basemgmt'); ?></th>
							<th><?php esc_html_e('Z&nbsp;→&nbsp;Na', 'basemgmt'); ?></th>
							<th><?php esc_html_e('Przez', 'basemgmt'); ?></th>
							<th><?php esc_html_e('Notatka', 'basemgmt'); ?></th>
						</tr></thead>
						<tbody>
						<?php foreach ( $history as $h ) :
							$who = $users_map[$h->changed_by]->display_name ?? ('User #' . $h->changed_by);
						?>
							<tr>
								<td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($h->created_at))); ?></td>
								<td>
									<?php echo esc_html($statuses[$h->from_status] ?? $h->from_status); ?>
									&nbsp;→&nbsp;
									<?php echo esc_html($statuses[$h->to_status] ?? $h->to_status); ?>
								</td>
								<td><?php echo esc_html($who); ?></td>
								<td><?php echo esc_html($h->note); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<!-- ── Right: admin actions ──────────────────────────────── -->
		<div>
			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Zarządzanie', 'basemgmt'); ?></h2></div>
				<div class="inside">
					<!-- Create conversation thread -->
					<div style="margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #eee;">
						<p style="margin:0 0 8px;"><strong><?php esc_html_e('Komunikacja', 'basemgmt'); ?></strong></p>
						<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
							<?php wp_nonce_field('bm_create_thread_from_submission'); ?>
							<input type="hidden" name="action"        value="bm_create_thread_from_submission">
							<input type="hidden" name="submission_id" value="<?php echo esc_attr($submission->id); ?>">
							<button type="submit" class="button button-secondary">
								💬 <?php esc_html_e('Utwórz wątek konwersacji', 'basemgmt'); ?>
							</button>
							<p class="description" style="margin-top:4px;">
								<?php esc_html_e('Tworzy nowy wątek z treścią zgłoszenia jako pierwszą wiadomością.', 'basemgmt'); ?>
							</p>
						</form>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_update_submission'); ?>
						<input type="hidden" name="action"        value="bm_update_submission">
						<input type="hidden" name="submission_id" value="<?php echo esc_attr($submission->id); ?>">

						<p>
							<label><strong><?php esc_html_e('Status', 'basemgmt'); ?></strong></label><br>
							<select name="status">
								<?php foreach ( $statuses as $k => $v ) : ?>
									<option value="<?php echo esc_attr($k); ?>" <?php selected($k, $submission->status); ?>><?php echo esc_html($v); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p>
							<label><strong><?php esc_html_e('Notatka do zmiany statusu', 'basemgmt'); ?></strong></label><br>
							<textarea name="status_note" class="large-text" rows="2"></textarea>
						</p>
						<p>
							<label><strong><?php esc_html_e('Priorytet', 'basemgmt'); ?></strong></label><br>
							<select name="priority">
								<?php foreach ( $priorities as $k => $v ) : ?>
									<option value="<?php echo esc_attr($k); ?>" <?php selected($k, $submission->priority); ?>><?php echo esc_html($v); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p>
							<label><strong><?php esc_html_e('Przypisz do', 'basemgmt'); ?></strong></label><br>
							<select name="assigned_to">
								<option value=""><?php esc_html_e('— Nieprzypisane —', 'basemgmt'); ?></option>
								<?php foreach ( $wp_users as $u ) : ?>
									<option value="<?php echo esc_attr($u->ID); ?>" <?php selected($u->ID, $submission->assigned_to ?? 0); ?>><?php echo esc_html($u->display_name); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p>
							<label><strong><?php esc_html_e('Komentarz dla obozu', 'basemgmt'); ?></strong></label><br>
							<textarea name="admin_comment" class="large-text" rows="4"><?php echo esc_textarea($submission->admin_comment ?? ''); ?></textarea>
						</p>

						<?php submit_button(__('Zapisz zmiany', 'basemgmt'), 'primary', 'submit', false); ?>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
