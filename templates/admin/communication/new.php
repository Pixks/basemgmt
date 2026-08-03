<?php
defined('ABSPATH') || exit;
/**
 * @var array $all_camps  – active camps
 * @var array $priorities – ConversationRepository::PRIORITIES
 */
?>
<div class="wrap bm-wrap">
	<h1>
		<?php esc_html_e('Nowa wiadomość do obozu', 'basemgmt'); ?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-communication')); ?>" class="page-title-action">← <?php esc_html_e('Lista wątków', 'basemgmt'); ?></a>
	</h1>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:700px;">
		<?php wp_nonce_field('bm_create_thread'); ?>
		<input type="hidden" name="action" value="bm_create_thread">

		<table class="form-table">
			<tr>
				<th><?php esc_html_e('Obóz *', 'basemgmt'); ?></th>
				<td>
					<select name="camp_id" required>
						<option value=""><?php esc_html_e('— Wybierz obóz —', 'basemgmt'); ?></option>
						<?php foreach ($all_camps as $camp) : ?>
						<option value="<?php echo esc_attr($camp->id); ?>"><?php echo esc_html($camp->name); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Temat *', 'basemgmt'); ?></th>
				<td><input type="text" name="subject" class="large-text" required placeholder="<?php esc_attr_e('Temat wiadomości', 'basemgmt'); ?>"></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Priorytet', 'basemgmt'); ?></th>
				<td>
					<select name="priority">
						<?php foreach ($priorities as $val => $label) : ?>
						<option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Pilne', 'basemgmt'); ?></th>
				<td>
					<label><input type="checkbox" name="is_urgent" value="1"> <?php esc_html_e('Oznacz jako pilne (🚨)', 'basemgmt'); ?></label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Treść *', 'basemgmt'); ?></th>
				<td>
					<textarea name="content" rows="10" class="large-text" required placeholder="<?php esc_attr_e('Treść wiadomości...', 'basemgmt'); ?>"></textarea>
				</td>
			</tr>
		</table>

		<?php submit_button(__('Wyślij wiadomość', 'basemgmt')); ?>
	</form>
</div>
