<?php
defined('ABSPATH') || exit;
use BaseMgmt\Modules\Schedule\PlanTemplateRepository;
/**
 * @var array $templates
 */
$recurrences = PlanTemplateRepository::RECURRENCES;
$day_names   = PlanTemplateRepository::DAY_NAMES;
?>
<div class="wrap bm-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e('Szablony planów dnia', 'basemgmt'); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-plan-templates&bm_action=new')); ?>">
		<?php esc_html_e('+ Nowy szablon', 'basemgmt'); ?>
	</a>
	<hr class="wp-header-end">

	<p class="description"><?php esc_html_e('Szablony planów dnia pozwalają definiować typowe układy dnia, które można stosować wielokrotnie. Szablony cykliczne (codzienne, tygodniowe) można ręcznie zastosować do wybranego planu dnia.', 'basemgmt'); ?></p>

	<?php if (empty($templates)): ?>
	<p><?php esc_html_e('Brak szablonów. Utwórz pierwszy szablon.', 'basemgmt'); ?></p>
	<?php else: ?>
	<table class="wp-list-table widefat fixed striped">
		<thead><tr>
			<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
			<th style="width:160px;"><?php esc_html_e('Powtarzalność', 'basemgmt'); ?></th>
			<th style="width:200px;"><?php esc_html_e('Dni tygodnia', 'basemgmt'); ?></th>
			<th style="width:100px;"><?php esc_html_e('Pozycji', 'basemgmt'); ?></th>
			<th style="width:160px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
		</tr></thead>
		<tbody>
		<?php foreach ($templates as $tpl):
			$items     = \BaseMgmt\Modules\Schedule\PlanTemplateRepository::get_items((int) $tpl->id);
			$del_url   = wp_nonce_url(admin_url('admin-post.php?action=bm_delete_plan_template&id=' . $tpl->id), 'bm_delete_plan_template_' . $tpl->id);
			$days_list = '';
			if ( $tpl->recurrence === PlanTemplateRepository::RECURRENCE_WEEKLY && $tpl->days_of_week ) {
				$days_arr  = explode(',', $tpl->days_of_week);
				$days_list = implode(', ', array_map(fn($d) => $day_names[(int)$d] ?? $d, $days_arr));
			}
		?>
		<tr>
			<td>
				<strong><a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-plan-templates&bm_action=edit&id=' . $tpl->id)); ?>">
					<?php echo esc_html($tpl->name); ?>
				</a></strong>
				<?php if ($tpl->description): ?><br><small style="color:#666;"><?php echo esc_html(wp_trim_words($tpl->description, 10)); ?></small><?php endif; ?>
			</td>
			<td><?php echo esc_html($recurrences[$tpl->recurrence] ?? $tpl->recurrence); ?></td>
			<td><?php echo esc_html($days_list ?: '—'); ?></td>
			<td><?php echo esc_html((string) count($items)); ?></td>
			<td>
				<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-plan-templates&bm_action=edit&id=' . $tpl->id)); ?>" class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
				<a href="<?php echo esc_url($del_url); ?>" class="button button-small"
				   onclick="return confirm('<?php esc_attr_e('Usunąć szablon?', 'basemgmt'); ?>')"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
