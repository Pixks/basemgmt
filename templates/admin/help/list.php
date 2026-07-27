<?php
defined('ABSPATH') || exit;
/**
 * @var array  $articles      – help article rows
 * @var array  $types         – TYPES constant
 * @var array  $statuses      – STATUSES constant
 * @var string $filter_type
 * @var string $filter_status
 */
?>
<div class="wrap bm-wrap">
	<h1 style="display:flex;align-items:center;justify-content:space-between;">
		<?php esc_html_e('Baza pomocy', 'basemgmt'); ?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-help&bm_action=new')); ?>" class="button button-primary">+ <?php esc_html_e('Nowy wpis', 'basemgmt'); ?></a>
	</h1>

	<!-- Filters -->
	<form method="get" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
		<input type="hidden" name="page" value="basemgmt-help">
		<label>
			<?php esc_html_e('Typ:', 'basemgmt'); ?><br>
			<select name="filter_type">
				<option value=""><?php esc_html_e('Wszystkie', 'basemgmt'); ?></option>
				<?php foreach ($types as $val => $label): ?>
				<option value="<?php echo esc_attr($val); ?>" <?php selected($filter_type, $val); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<?php esc_html_e('Status:', 'basemgmt'); ?><br>
			<select name="filter_status">
				<option value=""><?php esc_html_e('Wszystkie', 'basemgmt'); ?></option>
				<?php foreach ($statuses as $val => $label): ?>
				<option value="<?php echo esc_attr($val); ?>" <?php selected($filter_status, $val); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<button type="submit" class="button"><?php esc_html_e('Filtruj', 'basemgmt'); ?></button>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-help')); ?>" class="button"><?php esc_html_e('Wyczyść', 'basemgmt'); ?></a>
	</form>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:30px;"></th>
				<th><?php esc_html_e('Tytuł', 'basemgmt'); ?></th>
				<th style="width:100px;"><?php esc_html_e('Typ', 'basemgmt'); ?></th>
				<th style="width:100px;"><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
				<th style="width:90px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
				<th style="width:60px;"><?php esc_html_e('Kol.', 'basemgmt'); ?></th>
				<th style="width:120px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if (empty($articles)): ?>
		<tr><td colspan="7" style="text-align:center;color:#888;"><?php esc_html_e('Brak wpisów.', 'basemgmt'); ?></td></tr>
		<?php else: ?>
		<?php foreach ($articles as $art):
			$del_url = wp_nonce_url(admin_url('admin-post.php?action=bm_delete_help&id=' . $art->id), 'bm_delete_help_' . $art->id);
		?>
		<tr>
			<td>
				<?php if ($art->is_alarm): ?><span title="<?php esc_attr_e('Alarmowy', 'basemgmt'); ?>" style="color:#e74c3c;">🚨</span>
				<?php elseif ($art->is_pinned): ?><span title="<?php esc_attr_e('Przypięty', 'basemgmt'); ?>" style="color:#f59e0b;">📌</span>
				<?php endif; ?>
			</td>
			<td>
				<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-help&bm_action=edit&id=' . $art->id)); ?>"><strong><?php echo esc_html($art->title); ?></strong></a>
				<?php if ($art->excerpt): ?><br><small style="color:#666;"><?php echo esc_html(wp_trim_words($art->excerpt, 10)); ?></small><?php endif; ?>
			</td>
			<td><?php echo esc_html($types[$art->type] ?? $art->type); ?></td>
			<td><?php echo esc_html($art->category ?: '—'); ?></td>
			<td style="color:<?php echo $art->status === 'published' ? '#155724' : '#856404'; ?>;">
				<?php echo esc_html($statuses[$art->status] ?? $art->status); ?>
			</td>
			<td><?php echo esc_html((string) $art->sort_order); ?></td>
			<td>
				<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-help&bm_action=edit&id=' . $art->id)); ?>" class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
				<a href="<?php echo esc_url($del_url); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Usunąć wpis?', 'basemgmt'); ?>')"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
