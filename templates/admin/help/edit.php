<?php
defined('ABSPATH') || exit;
/**
 * @var object|null $article  – existing article or null for new
 * @var array       $types    – TYPES constant
 * @var array       $statuses – STATUSES constant
 */
$is_new = ! $article;
?>
<div class="wrap bm-wrap">
	<h1>
		<?php echo $is_new ? esc_html__('Nowy wpis pomocy', 'basemgmt') : esc_html($article->title); ?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-help')); ?>" class="page-title-action">← <?php esc_html_e('Lista', 'basemgmt'); ?></a>
	</h1>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:800px;">
		<?php wp_nonce_field('bm_save_help'); ?>
		<input type="hidden" name="action" value="bm_save_help">
		<input type="hidden" name="article_id" value="<?php echo esc_attr((string) ($article->id ?? 0)); ?>">

		<table class="form-table">
			<tr>
				<th><?php esc_html_e('Tytuł *', 'basemgmt'); ?></th>
				<td><input type="text" name="title" value="<?php echo esc_attr($article->title ?? ''); ?>" class="large-text" required></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Typ treści', 'basemgmt'); ?></th>
				<td>
					<select name="type">
						<?php foreach ($types as $val => $label): ?>
						<option value="<?php echo esc_attr($val); ?>" <?php selected($article->type ?? 'article', $val); ?>><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
				<td>
					<input type="text" name="category" value="<?php echo esc_attr($article->category ?? ''); ?>" class="regular-text" placeholder="<?php esc_attr_e('np. Panel, Bezpieczeństwo, Jedzenie', 'basemgmt'); ?>">
					<p class="description"><?php esc_html_e('Używana do grupowania na frontendzie.', 'basemgmt'); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Streszczenie', 'basemgmt'); ?></th>
				<td><textarea name="excerpt" rows="2" class="large-text"><?php echo esc_textarea($article->excerpt ?? ''); ?></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Treść', 'basemgmt'); ?></th>
				<td>
					<?php
					wp_editor(
						wp_kses_post($article->content ?? ''),
						'bm_help_content',
						[
							'textarea_name' => 'content',
							'media_buttons' => false,
							'teeny'         => true,
							'textarea_rows' => 14,
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
				<td>
					<select name="status">
						<?php foreach ($statuses as $val => $label): ?>
						<option value="<?php echo esc_attr($val); ?>" <?php selected($article->status ?? 'published', $val); ?>><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
				<td><input type="number" name="sort_order" value="<?php echo esc_attr((string) ($article->sort_order ?? 0)); ?>" min="0" style="width:80px;"></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Wyróżnienie', 'basemgmt'); ?></th>
				<td>
					<label><input type="checkbox" name="is_pinned" value="1" <?php checked((int)($article->is_pinned ?? 0)); ?>> <?php esc_html_e('📌 Przypnij jako ważny', 'basemgmt'); ?></label><br>
					<label><input type="checkbox" name="is_alarm" value="1" <?php checked((int)($article->is_alarm ?? 0)); ?>> <?php esc_html_e('🚨 Oznacz jako alarmowy', 'basemgmt'); ?></label>
				</td>
			</tr>
		</table>

		<?php submit_button($is_new ? __('Utwórz wpis', 'basemgmt') : __('Zapisz zmiany', 'basemgmt')); ?>
	</form>
</div>
