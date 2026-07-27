<?php
defined('ABSPATH') || exit;
/**
 * @var array  $threads    – conversation thread rows
 * @var array  $all_camps  – camp rows
 * @var array  $statuses   – STATUS constants
 * @var array  $priorities – PRIORITY constants
 * @var int    $filter_camp
 * @var string $filter_status
 * @var bool   $filter_unread
 */
$camp_map = [];
foreach ($all_camps as $c) { $camp_map[(int)$c->id] = $c->name; }
?>
<div class="wrap bm-wrap">
	<h1><?php esc_html_e('Komunikacja – wątki', 'basemgmt'); ?></h1>

	<!-- Filters -->
	<form method="get" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
		<input type="hidden" name="page" value="basemgmt-communication">
		<label>
			<?php esc_html_e('Obóz:', 'basemgmt'); ?><br>
			<select name="filter_camp">
				<option value=""><?php esc_html_e('Wszystkie', 'basemgmt'); ?></option>
				<?php foreach ($all_camps as $c): ?>
				<option value="<?php echo esc_attr((string) $c->id); ?>" <?php selected($filter_camp, $c->id); ?>><?php echo esc_html($c->name); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<?php esc_html_e('Status:', 'basemgmt'); ?><br>
			<select name="filter_status">
				<option value="all" <?php selected($filter_status, 'all'); ?>><?php esc_html_e('Wszystkie', 'basemgmt'); ?></option>
				<?php foreach ($statuses as $val => $label): ?>
				<option value="<?php echo esc_attr($val); ?>" <?php selected($filter_status, $val); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label style="align-self:flex-end;">
			<input type="checkbox" name="filter_unread" value="1" <?php checked($filter_unread); ?>>
			<?php esc_html_e('Tylko nieprzeczytane', 'basemgmt'); ?>
		</label>
		<button type="submit" class="button"><?php esc_html_e('Filtruj', 'basemgmt'); ?></button>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-communication')); ?>" class="button"><?php esc_html_e('Wyczyść', 'basemgmt'); ?></a>
	</form>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:40px;"></th>
				<th><?php esc_html_e('Temat', 'basemgmt'); ?></th>
				<th style="width:130px;"><?php esc_html_e('Obóz', 'basemgmt'); ?></th>
				<th style="width:90px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
				<th style="width:80px;"><?php esc_html_e('Priorytet', 'basemgmt'); ?></th>
				<th style="width:130px;"><?php esc_html_e('Ostatnia aktywność', 'basemgmt'); ?></th>
				<th style="width:80px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if (empty($threads)): ?>
		<tr><td colspan="7" style="text-align:center;color:#888;"><?php esc_html_e('Brak wątków.', 'basemgmt'); ?></td></tr>
		<?php else: ?>
		<?php foreach ($threads as $thread):
			$view_url = admin_url('admin.php?page=basemgmt-communication&bm_action=view&id=' . $thread->id);
			$priority_colors = ['normal' => '#555', 'high' => '#856404', 'urgent' => '#c0392b'];
			$status_colors   = ['open' => '#1a73e8', 'in_progress' => '#856404', 'closed' => '#555', 'archived' => '#aaa'];
		?>
		<tr style="<?php echo $thread->unread_admin > 0 ? 'font-weight:700;background:#fffde7;' : ''; ?>">
			<td style="text-align:center;">
				<?php if ($thread->is_urgent): ?><span title="Pilne" style="color:#e74c3c;">🔴</span><?php endif; ?>
				<?php if ($thread->unread_admin > 0): ?><span title="Nieprzeczytane" style="background:#e74c3c;color:#fff;border-radius:50%;padding:1px 5px;font-size:.75rem;"><?php echo esc_html((string) $thread->unread_admin); ?></span><?php endif; ?>
			</td>
			<td>
				<a href="<?php echo esc_url($view_url); ?>"><strong><?php echo esc_html($thread->subject); ?></strong></a>
			</td>
			<td><?php echo esc_html($camp_map[(int)$thread->camp_id] ?? '—'); ?></td>
			<td style="color:<?php echo esc_attr($status_colors[$thread->status] ?? '#555'); ?>;">
				<?php echo esc_html($statuses[$thread->status] ?? $thread->status); ?>
			</td>
			<td style="color:<?php echo esc_attr($priority_colors[$thread->priority] ?? '#555'); ?>;">
				<?php echo esc_html($priorities[$thread->priority] ?? $thread->priority); ?>
			</td>
			<td><?php echo esc_html($thread->last_message_at ? date_i18n('d.m.Y H:i', strtotime($thread->last_message_at)) : '—'); ?></td>
			<td><a href="<?php echo esc_url($view_url); ?>" class="button button-small"><?php esc_html_e('Otwórz', 'basemgmt'); ?></a></td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
