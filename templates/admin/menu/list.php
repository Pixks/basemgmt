<?php
defined('ABSPATH') || exit;
/**
 * @var array  $days        – list of meal day rows
 * @var string $filter_date – currently filtered date
 */
global $wpdb;
$table_exists = (bool) $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}bm_meal_days'");
?>
<div class="wrap bm-wrap">
	<h1 style="display:flex;align-items:center;justify-content:space-between;">
		<?php esc_html_e('Jadłospis', 'basemgmt'); ?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-menu&bm_action=new')); ?>" class="button button-primary">
			+ <?php esc_html_e('Nowy dzień', 'basemgmt'); ?>
		</a>
	</h1>

	<?php if ( ! $table_exists ) : ?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e('Tabele jadłospisu nie istnieją.', 'basemgmt'); ?></strong>
			<?php esc_html_e('Kliknij przycisk, aby je utworzyć.', 'basemgmt'); ?>
		</p>
		<p>
			<a href="<?php echo esc_url(wp_nonce_url(
				add_query_arg(['page' => 'basemgmt-menu', 'bm_create_tables' => '1'], admin_url('admin.php')),
				'bm_create_tables'
			)); ?>" class="button button-primary"><?php esc_html_e('Utwórz tabele', 'basemgmt'); ?></a>
		</p>
	</div>
	<?php endif; ?>

	<!-- Filter bar -->
	<form method="get" style="margin-bottom:16px;display:flex;gap:8px;align-items:flex-end;">
		<input type="hidden" name="page" value="basemgmt-menu">
		<label>
			<?php esc_html_e('Data:', 'basemgmt'); ?><br>
			<input type="date" name="filter_date" value="<?php echo esc_attr($filter_date); ?>">
		</label>
		<button type="submit" class="button"><?php esc_html_e('Filtruj', 'basemgmt'); ?></button>
		<?php if ($filter_date): ?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-menu')); ?>" class="button"><?php esc_html_e('Wyczyść', 'basemgmt'); ?></a>
		<?php endif; ?>
	</form>

	<!-- Copy form -->
	<details style="margin-bottom:20px;">
		<summary style="cursor:pointer;font-weight:600;color:#2271b1;">📋 <?php esc_html_e('Kopiuj jadłospis z innego dnia', 'basemgmt'); ?></summary>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;display:flex;gap:12px;align-items:flex-end;">
			<?php wp_nonce_field('bm_copy_menu'); ?>
			<input type="hidden" name="action" value="bm_copy_menu">
			<label><?php esc_html_e('Kopiuj z dnia:', 'basemgmt'); ?><br><input type="date" name="copy_from" required></label>
			<label><?php esc_html_e('Na dzień:', 'basemgmt'); ?><br><input type="date" name="copy_to" required></label>
			<button type="submit" class="button button-secondary"><?php esc_html_e('Kopiuj', 'basemgmt'); ?></button>
		</form>
	</details>

	<!-- List -->
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:130px;"><?php esc_html_e('Data', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Uwagi', 'basemgmt'); ?></th>
				<th style="width:90px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
				<th style="width:70px;"><?php esc_html_e('Posiłki', 'basemgmt'); ?></th>
				<th style="width:150px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($days)): ?>
			<tr><td colspan="5" style="text-align:center;color:#888;"><?php esc_html_e('Brak jadłospisów.', 'basemgmt'); ?></td></tr>
			<?php else: ?>
			<?php foreach ($days as $day):
				$items_count = count(\BaseMgmt\Modules\Menu\MealRepository::get_items((int)$day->id));
				$del_url     = wp_nonce_url(admin_url('admin-post.php?action=bm_delete_menu&id=' . $day->id), 'bm_delete_menu_' . $day->id);
				$status_color= $day->status === 'published' ? '#155724' : '#856404';
			?>
			<tr>
				<td><strong><?php echo esc_html(date_i18n('d.m.Y (D)', strtotime($day->meal_date))); ?></strong></td>
				<td><?php echo esc_html($day->notes ? wp_trim_words($day->notes, 10) : '—'); ?></td>
				<td style="color:<?php echo esc_attr($status_color); ?>;">
					<?php echo esc_html($day->status === 'published' ? __('Opublikowany', 'basemgmt') : __('Robocze', 'basemgmt')); ?>
				</td>
				<td><?php echo esc_html((string) $items_count); ?></td>
				<td>
					<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-menu&bm_action=edit&id=' . $day->id)); ?>" class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
					<a href="<?php echo esc_url($del_url); ?>" class="button button-small" data-bm-confirm="<?php esc_attr_e('Usunąć jadłospis?', 'basemgmt'); ?>"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
				</td>
			</tr>
			<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
