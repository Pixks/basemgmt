<?php
defined('ABSPATH') || exit;
/**
 * @var array  $logs         – log rows
 * @var int    $total        – total matching rows
 * @var int    $pages        – total pages
 * @var int    $page         – current page
 * @var string $filter_action
 * @var string $filter_date_from
 * @var string $filter_date_to
 * @var array  $action_types – unique action types
 * @var bool   $bm_embedded  – true when included inside another page (e.g. settings tab)
 */
$bm_embedded = $bm_embedded ?? false;
$bm_logs_page_args = $bm_embedded
	? ['page' => 'basemgmt-settings', 'tab' => 'logi']
	: ['page' => 'basemgmt-logs'];
$bm_logs_base_url  = add_query_arg($bm_logs_page_args, admin_url('admin.php'));
$action_labels = [
	'login_success'    => '✓ Logowanie OK',
	'login_failed'     => '✗ Nieudane logowanie',
	'login_locked'     => '🔒 Blokada',
	'logout'           => '→ Wylogowanie',
	'unlock_staff'     => '🔓 Odblokowanie konta',
	'camp_created'     => '+ Obóz dodany',
	'camp_updated'     => '✏ Obóz zaktualizowany',
	'camp_deleted'     => '✗ Obóz usunięty',
	'staff_created'    => '+ Kadra dodana',
	'staff_updated'    => '✏ Kadra zaktualizowana',
	'staff_deleted'    => '✗ Kadra usunięta',
	'report_saved'     => '📋 Meldunek zapisany',
	'plan_created'     => '+ Plan dnia utworzony',
	'plan_updated'     => '✏ Plan dnia zaktualizowany',
	'plan_deleted'     => '✗ Plan dnia usunięty',
	'plan_item_saved'  => '✏ Pozycja planu zapisana',
	'plan_item_deleted'=> '✗ Pozycja planu usunięta',
	'meal_created'     => '+ Jadłospis dodany',
	'meal_updated'     => '✏ Jadłospis zaktualizowany',
	'meal_deleted'     => '✗ Jadłospis usunięty',
	'meal_item_saved'  => '✏ Posiłek zapisany',
	'meal_item_deleted'=> '✗ Posiłek usunięty',
	'thread_created'   => '💬 Wątek utworzony',
	'message_sent'     => '💬 Wiadomość wysłana',
	'form_saved'       => '📝 Formularz zapisany',
	'submission_updated'=> '📝 Zgłoszenie zaktualizowane',
	'settings_saved'   => '⚙ Ustawienia zapisane',
	'template_created' => '+ Szablon planu dodany',
	'template_updated' => '✏ Szablon planu zaktualizowany',
	'template_deleted' => '✗ Szablon planu usunięty',
	'template_applied' => '→ Szablon zastosowany',
];
?>
<?php if ( ! $bm_embedded ): ?>
<div class="wrap bm-admin-wrap">
	<h1><?php esc_html_e('Logi operacji', 'basemgmt'); ?></h1>
<?php endif; ?>

	<!-- Filters -->
	<form method="get" style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
		<?php foreach ($bm_logs_page_args as $k => $v): ?>
		<input type="hidden" name="<?php echo esc_attr($k); ?>" value="<?php echo esc_attr($v); ?>">
		<?php endforeach; ?>
		<div>
			<label style="display:block;font-size:12px;margin-bottom:3px;"><?php esc_html_e('Akcja', 'basemgmt'); ?></label>
			<select name="filter_action">
				<option value=""><?php esc_html_e('— Wszystkie —', 'basemgmt'); ?></option>
				<?php foreach ($action_types as $at): ?>
				<option value="<?php echo esc_attr($at); ?>" <?php selected($filter_action, $at); ?>>
					<?php echo esc_html($action_labels[$at] ?? $at); ?>
				</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label style="display:block;font-size:12px;margin-bottom:3px;"><?php esc_html_e('Od daty', 'basemgmt'); ?></label>
			<input type="date" name="filter_date_from" value="<?php echo esc_attr($filter_date_from); ?>">
		</div>
		<div>
			<label style="display:block;font-size:12px;margin-bottom:3px;"><?php esc_html_e('Do daty', 'basemgmt'); ?></label>
			<input type="date" name="filter_date_to" value="<?php echo esc_attr($filter_date_to); ?>">
		</div>
		<button type="submit" class="button"><?php esc_html_e('Filtruj', 'basemgmt'); ?></button>
		<a href="<?php echo esc_url($bm_logs_base_url); ?>" class="button"><?php esc_html_e('Wyczyść', 'basemgmt'); ?></a>
	</form>

	<p>
		<?php printf(esc_html__('Łącznie: %d wpisów.', 'basemgmt'), $total); ?>
		<a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['bm_action' => 'clear', 'days' => 90], $bm_logs_base_url), 'bm_clear_logs_90')); ?>"
		   class="button button-small"
		   data-bm-confirm="<?php esc_attr_e('Usunąć wpisy starsze niż 90 dni?', 'basemgmt'); ?>">
			<?php esc_html_e('Wyczyść logi >90 dni', 'basemgmt'); ?>
		</a>
	</p>

	<?php if (empty($logs)): ?>
	<p><?php esc_html_e('Brak wpisów dla podanych kryteriów.', 'basemgmt'); ?></p>
	<?php else: ?>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:160px;"><?php esc_html_e('Data', 'basemgmt'); ?></th>
				<th style="width:220px;"><?php esc_html_e('Akcja', 'basemgmt'); ?></th>
				<th style="width:100px;"><?php esc_html_e('Obiekt', 'basemgmt'); ?></th>
				<th style="width:80px;"><?php esc_html_e('ID', 'basemgmt'); ?></th>
				<th><?php esc_html_e('Szczegóły', 'basemgmt'); ?></th>
				<th style="width:120px;"><?php esc_html_e('Użytkownik', 'basemgmt'); ?></th>
				<th style="width:130px;"><?php esc_html_e('IP', 'basemgmt'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($logs as $log): ?>
		<tr>
			<td><?php echo esc_html(date_i18n('d.m.Y H:i:s', strtotime($log->created_at))); ?></td>
			<td><?php echo esc_html($action_labels[$log->action] ?? $log->action); ?></td>
			<td><?php echo esc_html($log->object_type ?: '—'); ?></td>
			<td><?php echo esc_html($log->object_id ? '#' . $log->object_id : '—'); ?></td>
			<td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($log->details); ?>">
				<?php echo esc_html(mb_substr($log->details, 0, 100)); ?>
			</td>
			<td><?php
				$u = $log->user_id ? get_userdata((int) $log->user_id) : null;
				echo $u ? esc_html($u->display_name) : esc_html('#' . $log->user_id);
			?></td>
			<td><?php echo esc_html($log->ip_address ?: '—'); ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ($pages > 1): ?>
	<div class="tablenav bottom"><div class="tablenav-pages">
		<?php
		$base_url = add_query_arg(array_merge($bm_logs_page_args, [
			'filter_action'    => $filter_action,
			'filter_date_from' => $filter_date_from,
			'filter_date_to'   => $filter_date_to,
		]), admin_url('admin.php'));
		for ( $i = 1; $i <= $pages; $i++ ) :
			$url = add_query_arg('paged', $i, $base_url);
			printf(
				'<a class="button button-small%s" href="%s">%d</a> ',
				$i === $page ? ' button-primary' : '',
				esc_url($url),
				$i
			);
		endfor;
		?>
	</div></div>
	<?php endif; ?>
	<?php endif; ?>
<?php if ( ! $bm_embedded ): ?>
</div>
<?php endif; ?>
