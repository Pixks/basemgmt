<?php
defined('ABSPATH') || exit;
/**
 * @var object   $thread     – thread row
 * @var array    $messages   – message rows
 * @var array    $all_camps  – camp rows
 * @var array    $statuses   – STATUS constants
 * @var array    $priorities – PRIORITY constants
 * @var WP_User[] $wp_users  – admins/editors for assignment
 */
$camp_map = [];
foreach ($all_camps as $c) { $camp_map[(int)$c->id] = $c->name; }
?>
<div class="wrap bm-wrap">
	<h1>
		<?php if ($thread->is_urgent): ?><span style="color:#e74c3c;">🔴 </span><?php endif; ?>
		<?php echo esc_html($thread->subject); ?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-communication')); ?>" class="page-title-action">← <?php esc_html_e('Lista', 'basemgmt'); ?></a>
	</h1>

	<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">

		<!-- Left: messages -->
		<div>
			<div style="margin-bottom:20px;padding:12px;background:#f8fafc;border-radius:8px;border:1px solid #e0e7ef;">
				<strong><?php esc_html_e('Obóz:', 'basemgmt'); ?></strong> <?php echo esc_html($camp_map[(int)$thread->camp_id] ?? '—'); ?> &nbsp;|&nbsp;
				<strong><?php esc_html_e('Status:', 'basemgmt'); ?></strong> <?php echo esc_html($statuses[$thread->status] ?? $thread->status); ?> &nbsp;|&nbsp;
				<strong><?php esc_html_e('Priorytet:', 'basemgmt'); ?></strong> <?php echo esc_html($priorities[$thread->priority] ?? $thread->priority); ?>
			</div>

			<!-- Message thread -->
			<div id="bm-thread-messages" style="max-height:60vh;overflow-y:auto;border:1px solid #ddd;border-radius:8px;padding:16px;background:#fff;margin-bottom:20px;">
				<?php if (empty($messages)): ?>
				<p style="color:#aaa;text-align:center;"><?php esc_html_e('Brak wiadomości.', 'basemgmt'); ?></p>
				<?php else: ?>
				<?php foreach ($messages as $msg):
					$is_admin = $msg->author_type === 'admin';
					$bg       = $is_admin ? '#e8f0fe' : '#f0fdf4';
					$label    = $is_admin ? __('Obsługa ośrodka', 'basemgmt') : __('Kadra obozu', 'basemgmt');
					if ($is_admin) {
						$wp_user = get_user_by('id', $msg->author_id);
						$label   = $wp_user ? $wp_user->display_name . ' (admin)' : $label;
					}
				?>
				<div style="background:<?php echo esc_attr($bg); ?>;border-radius:8px;padding:12px;margin-bottom:12px;">
					<div style="font-size:.78rem;color:#888;margin-bottom:4px;">
						<strong><?php echo esc_html($label); ?></strong> &bull;
						<?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($msg->created_at))); ?>
						<?php if ($msg->is_system): ?> &bull; <em><?php esc_html_e('[systemowa]', 'basemgmt'); ?></em><?php endif; ?>
					</div>
					<div style="white-space:pre-wrap;"><?php echo wp_kses_post($msg->content); ?></div>
					<?php if ($msg->attachment_url): ?>
					<div style="margin-top:6px;"><a href="<?php echo esc_url($msg->attachment_url); ?>" target="_blank">📎 <?php esc_html_e('Załącznik', 'basemgmt'); ?></a></div>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<script>document.getElementById('bm-thread-messages').scrollTop = 9999;</script>

			<!-- Reply form -->
			<?php if (!in_array($thread->status, ['closed','archived'], true)): ?>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<?php wp_nonce_field('bm_admin_reply'); ?>
				<input type="hidden" name="action" value="bm_admin_reply">
				<input type="hidden" name="thread_id" value="<?php echo esc_attr((string) $thread->id); ?>">
				<label style="font-weight:600;"><?php esc_html_e('Twoja odpowiedź:', 'basemgmt'); ?></label>
				<textarea name="content" rows="5" class="large-text" style="margin-top:6px;" required></textarea>
				<?php submit_button(__('Wyślij odpowiedź', 'basemgmt'), 'primary', 'submit', false); ?>
			</form>
			<?php else: ?>
			<p style="color:#888;font-style:italic;"><?php esc_html_e('Wątek jest zamknięty / zarchiwizowany – nie można odpowiedzieć.', 'basemgmt'); ?></p>
			<?php endif; ?>
		</div>

		<!-- Right: thread management -->
		<div>
			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Zarządzanie wątkiem', 'basemgmt'); ?></h2></div>
				<div class="inside">
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_update_thread'); ?>
						<input type="hidden" name="action" value="bm_update_thread">
						<input type="hidden" name="thread_id" value="<?php echo esc_attr((string) $thread->id); ?>">
						<p>
							<label><strong><?php esc_html_e('Status', 'basemgmt'); ?></strong></label><br>
							<select name="status" style="width:100%;">
								<?php foreach ($statuses as $val => $label): ?>
								<option value="<?php echo esc_attr($val); ?>" <?php selected($thread->status, $val); ?>><?php echo esc_html($label); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p>
							<label><strong><?php esc_html_e('Priorytet', 'basemgmt'); ?></strong></label><br>
							<select name="priority" style="width:100%;">
								<?php foreach ($priorities as $val => $label): ?>
								<option value="<?php echo esc_attr($val); ?>" <?php selected($thread->priority, $val); ?>><?php echo esc_html($label); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p>
							<label><input type="checkbox" name="is_urgent" value="1" <?php checked($thread->is_urgent); ?>>
							<strong><?php esc_html_e('Oznacz jako pilny', 'basemgmt'); ?></strong></label>
						</p>
						<p>
							<label><strong><?php esc_html_e('Przypisz do', 'basemgmt'); ?></strong></label><br>
							<select name="assigned_to" style="width:100%;">
								<option value=""><?php esc_html_e('— Nieprzypisany —', 'basemgmt'); ?></option>
								<?php foreach ($wp_users as $u): ?>
								<option value="<?php echo esc_attr((string) $u->ID); ?>" <?php selected((int)$thread->assigned_to, $u->ID); ?>><?php echo esc_html($u->display_name); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<?php submit_button(__('Aktualizuj wątek', 'basemgmt'), 'secondary', 'submit', false); ?>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
