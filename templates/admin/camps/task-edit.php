<?php defined('ABSPATH') || exit;
$is_new    = is_null($task);
$task_id   = $is_new ? 0 : (int) $task->id;
$back_url  = admin_url('admin.php?page=basemgmt-camps&action=edit&id=' . (int) $camp->id . '#bm-section-checklist');
$status    = $is_new ? ($default_status ?? \BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_PENDING) : (string) $task->status;
$priority  = $is_new ? \BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_PRIORITY_NORMAL : (string) ($task->priority ?? \BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_PRIORITY_NORMAL);

$status_labels = [
	\BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_PENDING     => __('Do zrobienia', 'basemgmt'),
	\BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_IN_PROGRESS => __('W trakcie', 'basemgmt'),
	\BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_BLOCKED     => __('Zablokowane', 'basemgmt'),
	\BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_DONE        => __('Gotowe', 'basemgmt'),
];
?>
<div class="wrap bm-admin-wrap">

	<div class="bm-task-header">
		<div>
			<a href="<?php echo esc_url($back_url); ?>" class="bm-back-link">
				← <?php echo esc_html($camp->name); ?>
			</a>
			<h1 style="margin:4px 0 0;">
				<?php echo $is_new ? esc_html__('Nowe zadanie', 'basemgmt') : esc_html($task->label); ?>
			</h1>
		</div>
		<?php if ( ! $is_new ) : ?>
			<div class="bm-task-header__meta">
				<span class="bm-badge bm-badge--<?php echo esc_attr($priority); ?>"><?php echo esc_html($checklist_priorities[$priority] ?? $priority); ?></span>
				<span class="bm-badge bm-task-status-badge bm-task-status--<?php echo esc_attr($status); ?>"><?php echo esc_html($status_labels[$status] ?? $status); ?></span>
			</div>
		<?php endif; ?>
	</div>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="bm-task-form" class="bm-task-body">
			<?php wp_nonce_field('bm_save_camp_task'); ?>
			<input type="hidden" name="action" value="bm_save_camp_task">
			<input type="hidden" name="camp_id" value="<?php echo esc_attr($camp->id); ?>">
			<input type="hidden" name="task_id" value="<?php echo esc_attr($task_id); ?>">

			<div class="bm-task-main">
				<div class="bm-task-section">
					<label for="bm_task_label"><strong><?php esc_html_e('Nazwa zadania', 'basemgmt'); ?></strong></label>
					<input type="text" id="bm_task_label" name="label" class="widefat bm-task-title-input" required
						value="<?php echo esc_attr($task->label ?? ''); ?>"
						placeholder="<?php esc_attr_e('Wpisz nazwę zadania…', 'basemgmt'); ?>">
				</div>

				<div class="bm-task-section">
					<label for="bm_task_description"><strong><?php esc_html_e('Opis', 'basemgmt'); ?></strong></label>
					<textarea id="bm_task_description" name="description" class="widefat" rows="8"
						placeholder="<?php esc_attr_e('Dodaj szczegółowy opis, kroki do wykonania, uwagi…', 'basemgmt'); ?>"><?php echo esc_textarea($task->description ?? ''); ?></textarea>
				</div>

				<div class="bm-task-section">
					<label for="bm_task_comment"><strong><?php esc_html_e('Komentarz / notatka robocza', 'basemgmt'); ?></strong></label>
					<textarea id="bm_task_comment" name="comment" class="widefat" rows="4"><?php echo esc_textarea($task->comment ?? ''); ?></textarea>
				</div>
			</div>

			<div class="bm-task-sidebar">
				<div class="bm-task-meta-box">
					<h3><?php esc_html_e('Status i priorytet', 'basemgmt'); ?></h3>

					<p>
						<label for="bm_task_status"><strong><?php esc_html_e('Status', 'basemgmt'); ?></strong></label><br>
						<select id="bm_task_status" name="status" class="widefat">
							<?php foreach ( $checklist_statuses as $value => $label ) : ?>
								<option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<label for="bm_task_priority"><strong><?php esc_html_e('Priorytet', 'basemgmt'); ?></strong></label><br>
						<select id="bm_task_priority" name="priority" class="widefat">
							<?php foreach ( $checklist_priorities as $value => $label ) : ?>
								<option value="<?php echo esc_attr($value); ?>" <?php selected($priority, $value); ?>><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				</div>

				<div class="bm-task-meta-box">
					<h3><?php esc_html_e('Przypisanie', 'basemgmt'); ?></h3>

					<p>
						<label for="bm_task_assigned"><strong><?php esc_html_e('Przypisano do', 'basemgmt'); ?></strong></label><br>
						<select id="bm_task_assigned" name="assigned_to" class="widefat">
							<option value="" <?php selected($task->assigned_to ?? '', ''); ?>><?php esc_html_e('— nie przypisano —', 'basemgmt'); ?></option>
							<?php foreach ( $users as $user ) : ?>
								<option value="<?php echo esc_attr($user->display_name); ?>" <?php selected($task->assigned_to ?? '', $user->display_name); ?>><?php echo esc_html($user->display_name); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<label for="bm_task_party"><strong><?php esc_html_e('Strona odpowiedzialna', 'basemgmt'); ?></strong></label><br>
						<select id="bm_task_party" name="party" class="widefat">
							<?php foreach ( $checklist_parties as $value => $label ) : ?>
								<option value="<?php echo esc_attr($value); ?>" <?php selected($task->party ?? \BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_PARTY_SHARED, $value); ?>><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				</div>

				<div class="bm-task-meta-box">
					<h3><?php esc_html_e('Termin', 'basemgmt'); ?></h3>

					<p>
						<label for="bm_task_due_date"><strong><?php esc_html_e('Termin wykonania', 'basemgmt'); ?></strong></label><br>
						<input type="date" id="bm_task_due_date" name="due_date" class="widefat"
							value="<?php echo esc_attr($task->due_date ?? ''); ?>">
					</p>

					<?php if ( ! $is_new && ! empty($task->completed_at) ) : ?>
						<p class="bm-muted">
							<?php echo esc_html(sprintf(__('Ukończono: %s', 'basemgmt'), $task->completed_at)); ?>
						</p>
					<?php endif; ?>
				</div>

				<div class="bm-task-actions">
					<button type="submit" class="button button-primary button-large" style="width:100%;">
						<?php echo $is_new ? esc_html__('Utwórz zadanie', 'basemgmt') : esc_html__('Zapisz zmiany', 'basemgmt'); ?>
					</button>
					<button type="submit" name="_continue_editing" value="1" class="button button-large" style="width:100%;margin-top:6px;">
						<?php esc_html_e('Zapisz i zostań', 'basemgmt'); ?>
					</button>
					<a href="<?php echo esc_url($back_url); ?>" class="button button-large" style="width:100%;margin-top:6px;text-align:center;">
						<?php esc_html_e('Anuluj', 'basemgmt'); ?>
					</a>
					<?php if ( ! $is_new ) : ?>
						<hr style="margin:12px 0;">
						<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_camp_task&id={$camp->id}&task_id={$task_id}"), "bm_delete_task_{$task_id}")); ?>"
							class="button bm-danger" style="width:100%;text-align:center;"
							data-bm-confirm="<?php esc_attr_e('Usunąć to zadanie?', 'basemgmt'); ?>">
							<?php esc_html_e('Usuń zadanie', 'basemgmt'); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</form>
</div>
