<?php
defined('ABSPATH') || exit;
/**
 * @var array  $locations
 * @var string $tab
 */
?>
<div class="wrap bm-admin-wrap">
	<h1><?php esc_html_e('Opcje jadłospisu – Miejsca wydawania', 'basemgmt'); ?></h1>

	<div class="notice notice-info inline" style="margin:0 0 16px;padding:10px 14px;">
		<p style="margin:0;">
			<?php esc_html_e('Zarządzanie dietami zostało przeniesione do', 'basemgmt'); ?>
			<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-diets')); ?>">
				<?php esc_html_e('Organizacja → Diety', 'basemgmt'); ?>
			</a>.
		</p>
	</div>

	<div style="max-width:640px;">
		<p class="description"><?php esc_html_e('Predefiniowane miejsca wydawania pojawią się jako lista rozwijana w formularzu dodawania/edycji posiłku.', 'basemgmt'); ?></p>

		<?php if (! empty($locations)): ?>
		<table class="wp-list-table widefat fixed striped" style="margin-bottom:20px;">
			<thead><tr>
				<th><?php esc_html_e('Nazwa miejsca', 'basemgmt'); ?></th>
				<th style="width:80px;"><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
				<th style="width:80px;"></th>
			</tr></thead>
			<tbody>
			<?php foreach ($locations as $l): ?>
			<tr>
				<td><?php echo esc_html($l->name); ?></td>
				<td><?php echo esc_html((string) $l->sort_order); ?></td>
				<td>
					<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bm_delete_meal_location&id=' . $l->id), 'bm_delete_location_' . $l->id)); ?>"
					   class="button button-small"
					   onclick="return confirm('<?php esc_attr_e('Usunąć miejsce?', 'basemgmt'); ?>')"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<h3><?php esc_html_e('Dodaj miejsce wydawania', 'basemgmt'); ?></h3>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('bm_save_meal_location'); ?>
			<input type="hidden" name="action"       value="bm_save_meal_location">
			<input type="hidden" name="location_id"  value="0">
			<table class="form-table">
				<tr>
					<th><label><?php esc_html_e('Nazwa', 'basemgmt'); ?> *</label></th>
					<td><input type="text" name="location_name" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e('Kolejność', 'basemgmt'); ?></label></th>
					<td><input type="number" name="sort_order" value="0" style="width:80px;" min="0"></td>
				</tr>
			</table>
			<?php submit_button(__('Dodaj miejsce', 'basemgmt'), 'secondary'); ?>
		</form>
	</div>
</div>
<div class="wrap bm-admin-wrap">
	<h1><?php esc_html_e('Opcje jadłospisu – Diety i miejsca wydawania', 'basemgmt'); ?></h1>

	<nav class="nav-tab-wrapper" style="margin-bottom:20px;">
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-meal-options&tab=diets')); ?>"
		   class="nav-tab <?php echo $tab === 'diets' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e('Diety', 'basemgmt'); ?>
		</a>
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-meal-options&tab=locations')); ?>"
		   class="nav-tab <?php echo $tab === 'locations' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e('Miejsca wydawania', 'basemgmt'); ?>
		</a>
	</nav>

	<?php if ($tab === 'diets'): ?>
	<!-- Diets tab -->
	<div style="max-width:640px;">
		<p class="description"><?php esc_html_e('Predefiniowane diety pojawią się jako lista rozwijana w formularzu dodawania/edycji posiłku.', 'basemgmt'); ?></p>

		<?php if (! empty($diets)): ?>
		<table class="wp-list-table widefat fixed striped" style="margin-bottom:20px;">
			<thead><tr>
				<th><?php esc_html_e('Nazwa diety', 'basemgmt'); ?></th>
				<th style="width:80px;"><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
				<th style="width:80px;"></th>
			</tr></thead>
			<tbody>
			<?php foreach ($diets as $d): ?>
			<tr>
				<td><?php echo esc_html($d->name); ?></td>
				<td><?php echo esc_html((string) $d->sort_order); ?></td>
				<td>
					<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bm_delete_meal_diet&id=' . $d->id), 'bm_delete_diet_' . $d->id)); ?>"
					   class="button button-small"
					   onclick="return confirm('<?php esc_attr_e('Usunąć dietę?', 'basemgmt'); ?>')"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<h3><?php esc_html_e('Dodaj dietę', 'basemgmt'); ?></h3>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('bm_save_meal_diet'); ?>
			<input type="hidden" name="action"  value="bm_save_meal_diet">
			<input type="hidden" name="diet_id" value="0">
			<table class="form-table">
				<tr>
					<th><label><?php esc_html_e('Nazwa', 'basemgmt'); ?> *</label></th>
					<td><input type="text" name="diet_name" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e('Kolejność', 'basemgmt'); ?></label></th>
					<td><input type="number" name="sort_order" value="0" style="width:80px;" min="0"></td>
				</tr>
			</table>
			<?php submit_button(__('Dodaj dietę', 'basemgmt'), 'secondary'); ?>
		</form>
	</div>

	<?php else: ?>
	<!-- Locations tab -->
	<div style="max-width:640px;">
		<p class="description"><?php esc_html_e('Predefiniowane miejsca wydawania pojawią się jako lista rozwijana w formularzu dodawania/edycji posiłku.', 'basemgmt'); ?></p>

		<?php if (! empty($locations)): ?>
		<table class="wp-list-table widefat fixed striped" style="margin-bottom:20px;">
			<thead><tr>
				<th><?php esc_html_e('Nazwa miejsca', 'basemgmt'); ?></th>
				<th style="width:80px;"><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
				<th style="width:80px;"></th>
			</tr></thead>
			<tbody>
			<?php foreach ($locations as $l): ?>
			<tr>
				<td><?php echo esc_html($l->name); ?></td>
				<td><?php echo esc_html((string) $l->sort_order); ?></td>
				<td>
					<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bm_delete_meal_location&id=' . $l->id), 'bm_delete_location_' . $l->id)); ?>"
					   class="button button-small"
					   onclick="return confirm('<?php esc_attr_e('Usunąć miejsce?', 'basemgmt'); ?>')"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<h3><?php esc_html_e('Dodaj miejsce wydawania', 'basemgmt'); ?></h3>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('bm_save_meal_location'); ?>
			<input type="hidden" name="action"       value="bm_save_meal_location">
			<input type="hidden" name="location_id"  value="0">
			<table class="form-table">
				<tr>
					<th><label><?php esc_html_e('Nazwa', 'basemgmt'); ?> *</label></th>
					<td><input type="text" name="location_name" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e('Kolejność', 'basemgmt'); ?></label></th>
					<td><input type="number" name="sort_order" value="0" style="width:80px;" min="0"></td>
				</tr>
			</table>
			<?php submit_button(__('Dodaj miejsce', 'basemgmt'), 'secondary'); ?>
		</form>
	</div>
	<?php endif; ?>
</div>
