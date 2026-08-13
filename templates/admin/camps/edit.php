<?php defined('ABSPATH') || exit;
$is_edit           = ! is_null($camp);
$id                = $is_edit ? (int) $camp->id : 0;
$process_stage     = $case->process_stage ?? \BaseMgmt\Modules\Camps\CampCaseRepository::STAGE_INQUIRY;
$risk_level        = $case->risk_level ?? \BaseMgmt\Modules\Camps\CampCaseRepository::RISK_LOW;
$owner_user_id     = (int) ($case->owner_user_id ?? 0);
$needs_attention   = ! empty($case->manual_attention) || (! isset($case->manual_attention) && ! empty($case->needs_attention));
$readiness_percent = (int) ($readiness['percent'] ?? 0);

$super_status_map = [
	'inquiry'           => ['label' => __('Zapytanie', 'basemgmt'),     'slug' => 'zapytanie'],
	'offer'             => ['label' => __('Zapytanie', 'basemgmt'),     'slug' => 'zapytanie'],
	'negotiation'       => ['label' => __('Zapytanie', 'basemgmt'),     'slug' => 'zapytanie'],
	'tentative_booking' => ['label' => __('Zapytanie', 'basemgmt'),     'slug' => 'zapytanie'],
	'contract_draft'    => ['label' => __('Przygotowanie', 'basemgmt'), 'slug' => 'przygotowanie'],
	'contract_signed'   => ['label' => __('Przygotowanie', 'basemgmt'), 'slug' => 'przygotowanie'],
	'awaiting_payment'  => ['label' => __('Przygotowanie', 'basemgmt'), 'slug' => 'przygotowanie'],
	'ready_for_arrival' => ['label' => __('Przygotowanie', 'basemgmt'), 'slug' => 'przygotowanie'],
	'on_site'           => ['label' => __('Pobyt', 'basemgmt'),         'slug' => 'pobyt'],
	'settlement'        => ['label' => __('Rozliczenie', 'basemgmt'),   'slug' => 'rozliczenie'],
	'closed'            => ['label' => __('Zakończono', 'basemgmt'),    'slug' => 'zakonczone'],
	'cancelled'         => ['label' => __('Zakończono', 'basemgmt'),    'slug' => 'zakonczone'],
];
$super_status = $super_status_map[$process_stage] ?? ['label' => __('Zapytanie', 'basemgmt'), 'slug' => 'zapytanie'];
?>
<div class="wrap bm-admin-wrap">

	<?php if ( $is_edit ) : ?>

		<div class="bm-camp-header">
			<div class="bm-camp-header__title">
				<h1><?php echo esc_html($camp->name); ?></h1>
				<a class="bm-muted" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps')); ?>" style="font-size:13px;">← <?php esc_html_e('Wróć do listy', 'basemgmt'); ?></a>
			</div>
			<div class="bm-camp-header__meta">
				<span class="bm-status-badge bm-status-badge--<?php echo esc_attr($super_status['slug']); ?>">
					<?php echo esc_html($super_status['label']); ?>
				</span>
				<span class="bm-muted"><?php echo esc_html($workflow['current_stage_label'] ?? ''); ?></span>
				<?php if ( $needs_attention ) : ?>
					<span class="bm-badge bm-badge--critical">⚠ <?php esc_html_e('Wymaga uwagi', 'basemgmt'); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<nav class="nav-tab-wrapper bm-camp-tabs" id="bm-camp-tab-nav" style="margin-bottom:0;display:flex;align-items:center;">
			<a href="#" class="nav-tab" data-tab="panel"><?php esc_html_e('Panel', 'basemgmt'); ?></a>
			<a href="#" class="nav-tab" data-tab="workcenter"><?php esc_html_e('Centrum Pracy', 'basemgmt'); ?></a>
			<a href="#" class="nav-tab" data-tab="organizer"><?php esc_html_e('Organizator', 'basemgmt'); ?></a>
			<a href="#" class="nav-tab" data-tab="documents"><?php esc_html_e('Dokumenty', 'basemgmt'); ?></a>
			<a href="#" class="nav-tab" data-tab="finance"><?php esc_html_e('Finanse', 'basemgmt'); ?></a>
			<span style="flex:1;"></span>
			<button type="button" class="button button-primary" style="margin:4px 0 4px 8px;" data-bm-alert="<?php esc_attr_e('Funkcja rozliczenia zostanie wkrótce uruchomiona.', 'basemgmt'); ?>">
				<?php esc_html_e('Rozlicz', 'basemgmt'); ?>
			</button>
		</nav>

		<!-- ── PANEL ─────────────────────────────────────────────────────────── -->
		<div class="bm-tab-panel" data-tab="panel" id="bm-section-overview">

                        <!-- Quick stats row -->
                        <div class="bm-case-grid bm-case-grid--metrics" style="margin-bottom:20px;">
                                <div class="bm-case-card">
                                        <span class="bm-stat-label"><?php esc_html_e('Aktualny etap', 'basemgmt'); ?></span>
                                        <strong><?php echo esc_html($workflow['current_stage_label']); ?></strong>
                                </div>
                                <div class="bm-case-card">
                                        <span class="bm-stat-label"><?php esc_html_e('Gotowość', 'basemgmt'); ?></span>
                                        <strong><?php echo esc_html($readiness_percent); ?>%</strong>
                                </div>
                                <div class="bm-case-card">
                                        <span class="bm-stat-label"><?php esc_html_e('Następny termin', 'basemgmt'); ?></span>
                                        <strong><?php echo esc_html($case->next_action_due_date ?? '—'); ?></strong>
                                </div>
                                <div class="bm-case-card">
                                        <span class="bm-stat-label"><?php esc_html_e('Otwarte blokery', 'basemgmt'); ?></span>
                                        <strong><?php echo esc_html((string) count($workflow['blockers'])); ?></strong>
                                </div>
                                <div class="bm-case-card">
                                        <span class="bm-stat-label"><?php esc_html_e('Zadania', 'basemgmt'); ?></span>
                                        <strong><?php
                                                $tasks_total = $wpdb->get_var($wpdb->prepare(
                                                        "SELECT COUNT(*) FROM " . \BaseMgmt\Database\Schema::table('camp_checklist_items') . " WHERE camp_id = %d",
                                                        $id
                                                ));
                                                $tasks_done = $wpdb->get_var($wpdb->prepare(
                                                        "SELECT COUNT(*) FROM " . \BaseMgmt\Database\Schema::table('camp_checklist_items') . " WHERE camp_id = %d AND status = 'done'",
                                                        $id
                                                ));
                                                echo esc_html(($tasks_done ?? 0) . ' / ' . ($tasks_total ?? 0));
                                        ?></strong>
                                </div>
                                <div class="bm-case-card">
                                        <span class="bm-stat-label"><?php esc_html_e('Dokumenty', 'basemgmt'); ?></span>
                                        <strong><?php
                                                echo esc_html((string) $wpdb->get_var($wpdb->prepare(
                                                        "SELECT COUNT(*) FROM " . \BaseMgmt\Database\Schema::table('camp_documents') . " WHERE camp_id = %d",
                                                        $id
                                                )));
                                        ?></strong>
                                </div>
                        </div>

                        <!-- Blockers + Actions -->
                        <div class="bm-case-grid" style="margin-bottom:20px;">
                                <div class="bm-case-card">
                                        <h3 style="margin-top:0;"><?php esc_html_e('Co blokuje przejście dalej', 'basemgmt'); ?></h3>
                                        <?php if ( empty($workflow['blockers']) ) : ?>
                                                <p class="bm-muted"><?php esc_html_e('Brak krytycznych blokerów na tym etapie.', 'basemgmt'); ?></p>
                                        <?php else : ?>
                                                <ul style="margin:0 0 0 18px;">
                                                        <?php foreach ( $workflow['blockers'] as $item ) : ?>
                                                                <li><?php echo esc_html($item); ?></li>
                                                        <?php endforeach; ?>
                                                </ul>
                                        <?php endif; ?>
                                </div>
                                <div class="bm-case-card">
                                        <h3 style="margin-top:0;"><?php esc_html_e('Sugerowane działania', 'basemgmt'); ?></h3>
                                        <?php if ( empty($workflow['next_actions']) ) : ?>
                                                <p class="bm-muted"><?php esc_html_e('Brak sugestii — etap kompletny.', 'basemgmt'); ?></p>
                                        <?php else : ?>
                                                <ol style="margin:0 0 0 18px;">
                                                        <?php foreach ( $workflow['next_actions'] as $item ) : ?>
                                                                <li><?php echo esc_html($item); ?></li>
                                                        <?php endforeach; ?>
                                                </ol>
                                        <?php endif; ?>
                                </div>
                        </div>

                        <!-- Basic data form -->
                        <div class="bm-form-section">
                                <h2 style="margin-top:0;"><?php esc_html_e('Podstawowe dane obozu', 'basemgmt'); ?></h2>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <?php wp_nonce_field('bm_save_camp_overview'); ?>
                                        <input type="hidden" name="action" value="bm_save_camp_overview">
                                        <input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
                                        <div class="bm-form-grid">
                                                <p>
                                                        <label for="bm_name"><strong><?php esc_html_e('Nazwa obozu', 'basemgmt'); ?></strong></label><br>
                                                        <input type="text" id="bm_name" name="name" class="regular-text" required value="<?php echo esc_attr($camp->name ?? ''); ?>">
                                                </p>
                                                <p>
                                                        <label for="bm_status"><strong><?php esc_html_e('Status pobytu', 'basemgmt'); ?></strong></label><br>
                                                        <select id="bm_status" name="status">
                                                                <?php foreach ( ['active' => __('Aktywny', 'basemgmt'), 'ended' => __('Zakończony', 'basemgmt'), 'archived' => __('Archiwalny', 'basemgmt')] as $value => $label ) : ?>
                                                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($camp->status ?? 'active', $value); ?>><?php echo esc_html($label); ?></option>
                                                                <?php endforeach; ?>
                                                        </select>
                                                </p>
                                                <p>
                                                        <label for="bm_start"><strong><?php esc_html_e('Data rozpoczęcia', 'basemgmt'); ?></strong></label><br>
                                                        <input type="date" id="bm_start" name="start_date" required value="<?php echo esc_attr($camp->start_date ?? ''); ?>">
                                                </p>
                                                <p>
                                                        <label for="bm_end"><strong><?php esc_html_e('Data zakończenia', 'basemgmt'); ?></strong></label><br>
                                                        <input type="date" id="bm_end" name="end_date" required value="<?php echo esc_attr($camp->end_date ?? ''); ?>">
                                                </p>
                                        </div>
                                        <p class="submit" style="margin-bottom:0;">
                                                <button type="submit" class="button button-primary"><?php esc_html_e('Zapisz podstawowe dane', 'basemgmt'); ?></button>
                                        </p>
                                </form>
                        </div>

                        <!-- Process stage + owner form (simplified) -->
                        <div class="bm-form-section" id="bm-section-process">
                                <h2 style="margin-top:0;"><?php esc_html_e('Etap i odpowiedzialny', 'basemgmt'); ?></h2>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <?php wp_nonce_field('bm_save_camp_process'); ?>
                                        <input type="hidden" name="action" value="bm_save_camp_process">
                                        <input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
                                        <input type="hidden" name="risk_level" value="<?php echo esc_attr($risk_level); ?>">
                                        <input type="hidden" name="needs_attention" value="0">
                                        <input type="hidden" name="readiness_notes" value="<?php echo esc_attr($case->readiness_notes ?? ''); ?>">
                                        <div class="bm-form-grid">
                                                <p>
                                                        <label for="bm_process_stage"><strong><?php esc_html_e('Etap procesu', 'basemgmt'); ?></strong></label><br>
                                                        <select id="bm_process_stage" name="process_stage">
                                                                <?php foreach ( $process_stages as $value => $label ) : ?>
                                                                        <?php $is_allowed = $value === $process_stage || in_array($value, $allowed_transitions, true); ?>
                                                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($process_stage, $value); ?> <?php disabled(! $is_allowed); ?>>
                                                                                <?php echo esc_html($label . ($is_allowed ? '' : ' — ' . __('niedostępne', 'basemgmt'))); ?>
                                                                        </option>
                                                                <?php endforeach; ?>
                                                        </select>
                                                </p>
                                                <p>
                                                        <label for="bm_owner_user_id"><strong><?php esc_html_e('Odpowiedzialny', 'basemgmt'); ?></strong></label><br>
                                                        <select id="bm_owner_user_id" name="owner_user_id">
                                                                <option value="0"><?php esc_html_e('— nie przypisano —', 'basemgmt'); ?></option>
                                                                <?php foreach ( $users as $user ) : ?>
                                                                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($owner_user_id, (int) $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                                                                <?php endforeach; ?>
                                                        </select>
                                                </p>
                                                <p>
                                                        <label for="bm_next_action"><strong><?php esc_html_e('Termin następnego działania', 'basemgmt'); ?></strong></label><br>
                                                        <input type="date" id="bm_next_action" name="next_action_due_date" value="<?php echo esc_attr($case->next_action_due_date ?? ''); ?>">
                                                </p>
                                        </div>
                                        <p>
                                                <label for="bm_case_notes"><strong><?php esc_html_e('Notatki', 'basemgmt'); ?></strong></label><br>
                                                <textarea id="bm_case_notes" name="case_notes" class="large-text" rows="4"><?php echo esc_textarea($case->notes ?? ''); ?></textarea>
                                        </p>
                                        <input type="hidden" name="stage_change_note" value="">
                                        <p class="submit" style="margin-bottom:0;">
                                                <button type="submit" class="button button-primary"><?php esc_html_e('Zapisz etap', 'basemgmt'); ?></button>
                                        </p>
                                </form>
                        </div>

                </div><!-- /panel --><!-- ── CENTRUM PRACY ─────────────────────────────────────────────────── -->
		<div class="bm-tab-panel" data-tab="workcenter" id="bm-section-workcenter">

			<?php
			$task_new_url = admin_url('admin.php?page=basemgmt-camps&action=task_new&id=' . $id);
			$kanban_cols  = [
				\BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_PENDING     => ['label' => __('Do zrobienia', 'basemgmt'),  'slug' => 'pending'],
				\BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_IN_PROGRESS => ['label' => __('W trakcie', 'basemgmt'),       'slug' => 'in_progress'],
				\BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_BLOCKED     => ['label' => __('Zablokowane', 'basemgmt'),    'slug' => 'blocked'],
				\BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_DONE        => ['label' => __('Gotowe', 'basemgmt'),          'slug' => 'done'],
			];
			$kanban_tasks = [];
			foreach ( $kanban_cols as $col_status => $_ ) {
				$kanban_tasks[$col_status] = [];
			}
			foreach ( $checklist as $item ) {
				$s = (string) ($item['status'] ?? 'pending');
				if ( isset($kanban_tasks[$s]) ) {
					$kanban_tasks[$s][] = $item;
				}
			}
			$total_tasks = count($checklist);
			$done_tasks  = count($kanban_tasks[\BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_DONE]);
			?>

			<div class="bm-workcenter-header">
				<div class="bm-workcenter-stats">
					<span><?php echo esc_html(sprintf(__('%1$d / %2$d zadań ukończonych', 'basemgmt'), $done_tasks, $total_tasks)); ?></span>
					<?php if ( $total_tasks > 0 ) : ?>
						<div class="bm-progress-bar">
							<div class="bm-progress-fill" style="width:<?php echo esc_attr((string) (int) round(($done_tasks / $total_tasks) * 100)); ?>%"></div>
						</div>
					<?php endif; ?>
				</div>
				<a href="<?php echo esc_url($task_new_url); ?>" class="button button-primary">
					+ <?php esc_html_e('Nowe zadanie', 'basemgmt'); ?>
				</a>
			</div>

			<?php
			$task_templates_for_modal = \BaseMgmt\Admin\Pages\OrgTasksPage::get_all();
			?>
			<?php if (!empty($task_templates_for_modal)): ?>
			<div style="margin-bottom:12px;">
				<button type="button" class="button" id="bm-add-task-from-template">
					+ <?php esc_html_e('Dodaj z szablonu', 'basemgmt'); ?>
				</button>
			</div>
			<?php endif; ?>

			<div class="bm-kanban" id="bm-section-checklist">
				<?php foreach ( $kanban_cols as $col_status => $col ) : ?>
					<div class="bm-kanban-col bm-kanban-col--<?php echo esc_attr($col['slug']); ?>">
						<div class="bm-kanban-col__header">
							<span class="bm-kanban-col__title"><?php echo esc_html($col['label']); ?></span>
							<span class="bm-kanban-col__count"><?php echo esc_html((string) count($kanban_tasks[$col_status])); ?></span>
						</div>
						<div class="bm-kanban-col__body">
							<?php if ( empty($kanban_tasks[$col_status]) ) : ?>
								<div class="bm-kanban-empty"><?php esc_html_e('Brak zadań', 'basemgmt'); ?></div>
							<?php else : ?>
								<?php foreach ( $kanban_tasks[$col_status] as $item ) : ?>
									<?php
									$item_id   = (int) ($item['id'] ?? 0);
									$edit_url  = $item_id > 0
										? admin_url("admin.php?page=basemgmt-camps&action=task_edit&id={$id}&task_id={$item_id}")
										: '#';
									$priority  = $item['priority'] ?? \BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_PRIORITY_NORMAL;
									$overdue   = ! empty($item['due_date']) && $item['due_date'] < gmdate('Y-m-d') && $col_status !== \BaseMgmt\Modules\Camps\CampCaseRepository::CHECKLIST_STATUS_DONE;
									?>
									<a href="<?php echo esc_url($edit_url); ?>" class="bm-task-card <?php echo $overdue ? 'bm-task-card--overdue' : ''; ?>">
										<div class="bm-task-card__title"><?php echo esc_html($item['label']); ?></div>
										<?php if ( ! empty($item['description']) ) : ?>
											<div class="bm-task-card__desc"><?php echo esc_html(mb_strimwidth($item['description'], 0, 80, '…')); ?></div>
										<?php endif; ?>
										<div class="bm-task-card__meta">
											<span class="bm-badge bm-badge--<?php echo esc_attr($priority); ?>"><?php echo esc_html($checklist_priorities[$priority] ?? $priority); ?></span>
											<?php if ( ! empty($item['assigned_to']) ) : ?>
												<span class="bm-task-card__assignee">👤 <?php echo esc_html($item['assigned_to']); ?></span>
											<?php endif; ?>
											<?php if ( ! empty($item['due_date']) ) : ?>
												<span class="bm-task-card__due <?php echo $overdue ? 'bm-task-card__due--overdue' : ''; ?>">
													📅 <?php echo esc_html($item['due_date']); ?>
												</span>
											<?php endif; ?>
										</div>
									</a>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<div class="bm-kanban-col__footer">
							<a href="<?php echo esc_url(add_query_arg(['action' => 'task_new', 'id' => $id, 'default_status' => $col_status], admin_url('admin.php?page=basemgmt-camps'))); ?>" class="bm-kanban-add-link">
								+ <?php esc_html_e('Dodaj', 'basemgmt'); ?>
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Modal: Dodaj zadanie z szablonu -->
			<?php if (!empty($task_templates_for_modal)): ?>
			<div id="bm-modal-task-template" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Dodaj zadanie z szablonu', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_add_task_from_template'); ?>
						<input type="hidden" name="action" value="bm_add_task_from_template">
						<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
						<div class="bm-modal-body">
							<p><?php esc_html_e('Wybierz szablony do dodania (możesz zaznaczyć wiele):', 'basemgmt'); ?></p>
							<div style="max-height:320px;overflow-y:auto;border:1px solid #dcdcde;border-radius:4px;padding:8px;">
								<?php
								$priority_map_modal = ['low'=>__('Niski','basemgmt'),'normal'=>__('Normalny','basemgmt'),'high'=>__('Wysoki','basemgmt'),'critical'=>__('Krytyczny','basemgmt')];
								foreach ($task_templates_for_modal as $ttpl): ?>
								<label style="display:flex;align-items:flex-start;gap:8px;padding:8px;border-bottom:1px solid #f0f0f1;cursor:pointer;">
									<input type="checkbox" name="template_ids[]" value="<?php echo esc_attr($ttpl->id); ?>"
										<?php checked($ttpl->auto_add, 1); ?> style="margin-top:2px;flex-shrink:0;">
									<span>
										<strong><?php echo esc_html($ttpl->title); ?></strong>
										<span class="bm-badge bm-badge--<?php echo esc_attr($ttpl->priority); ?>" style="margin-left:6px;"><?php echo esc_html($priority_map_modal[$ttpl->priority] ?? $ttpl->priority); ?></span>
										<?php if (!empty($ttpl->description)): ?>
											<br><small class="bm-muted"><?php echo esc_html(mb_strimwidth($ttpl->description, 0, 80, '…')); ?></small>
										<?php endif; ?>
									</span>
								</label>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="bm-modal-footer">
							<button type="submit" class="button button-primary"><?php esc_html_e('Dodaj zaznaczone', 'basemgmt'); ?></button>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>
			<?php endif; ?>

		</div><!-- /workcenter -->

		<!-- ── ORGANIZATOR ───────────────────────────────────────────────────── -->
		<div class="bm-tab-panel" data-tab="organizer" id="bm-section-organizer">
			<div class="bm-form-section">
				<h2 style="margin-top:0;"><?php esc_html_e('Dane organizatora', 'basemgmt'); ?></h2>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<?php wp_nonce_field('bm_save_camp_organizer'); ?>
					<input type="hidden" name="action" value="bm_save_camp_organizer">
					<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">

					<div class="postbox" style="margin-bottom:16px;">
						<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Dane kontaktowe', 'basemgmt'); ?></h2></div>
						<div class="inside">
							<div class="bm-form-grid">
								<p>
									<label for="bm_organization_name"><strong><?php esc_html_e('Organizator', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_organization_name" name="organization_name" class="regular-text" value="<?php echo esc_attr($organizer->organization_name ?? ''); ?>">
								</p>
								<p>
									<label for="bm_contact_person"><strong><?php esc_html_e('Osoba kontaktowa', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_contact_person" name="contact_person" class="regular-text" value="<?php echo esc_attr($organizer->contact_person ?? ''); ?>">
								</p>
								<p>
									<label for="bm_contact_email"><strong><?php esc_html_e('E-mail kontaktowy', 'basemgmt'); ?></strong></label><br>
									<input type="email" id="bm_contact_email" name="contact_email" class="regular-text" value="<?php echo esc_attr($organizer->contact_email ?? ''); ?>">
								</p>
								<p>
									<label for="bm_contact_phone"><strong><?php esc_html_e('Telefon kontaktowy', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_contact_phone" name="contact_phone" class="regular-text" value="<?php echo esc_attr($organizer->contact_phone ?? ''); ?>">
								</p>
							</div>
						</div>
					</div>

					<div class="postbox" style="margin-bottom:16px;">
						<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Dane rozliczeniowe', 'basemgmt'); ?></h2></div>
						<div class="inside">
							<div class="bm-form-grid">
								<p>
									<label for="bm_billing_name"><strong><?php esc_html_e('Nazwa do faktury', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_billing_name" name="billing_name" class="regular-text" value="<?php echo esc_attr($organizer->billing_name ?? ''); ?>">
								</p>
								<p>
									<label for="bm_billing_tax_id"><strong><?php esc_html_e('NIP / identyfikator', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_billing_tax_id" name="billing_tax_id" class="regular-text" value="<?php echo esc_attr($organizer->billing_tax_id ?? ''); ?>">
								</p>
								<p>
									<label for="bm_billing_regon"><strong><?php esc_html_e('REGON', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_billing_regon" name="billing_regon" class="regular-text" value="<?php echo esc_attr($organizer->billing_regon ?? ''); ?>">
								</p>
								<p>
									<label for="bm_billing_krs"><strong><?php esc_html_e('KRS (opcjonalnie)', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_billing_krs" name="billing_krs" class="regular-text" value="<?php echo esc_attr($organizer->billing_krs ?? ''); ?>">
								</p>
								<p>
									<label for="bm_billing_street"><strong><?php esc_html_e('Ulica i numer', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_billing_street" name="billing_street" class="regular-text" value="<?php echo esc_attr($organizer->billing_street ?? ''); ?>">
								</p>
								<p>
									<label for="bm_billing_city"><strong><?php esc_html_e('Miejscowość', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_billing_city" name="billing_city" class="regular-text" value="<?php echo esc_attr($organizer->billing_city ?? ''); ?>">
								</p>
								<p>
									<label for="bm_billing_zip"><strong><?php esc_html_e('Kod pocztowy', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_billing_zip" name="billing_zip" class="regular-text" value="<?php echo esc_attr($organizer->billing_zip ?? ''); ?>">
								</p>
								<p>
									<label for="bm_bank_name"><strong><?php esc_html_e('Nazwa banku', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_bank_name" name="bank_name" class="regular-text" value="<?php echo esc_attr($organizer->bank_name ?? ''); ?>">
								</p>
								<p>
									<label for="bm_bank_account"><strong><?php esc_html_e('Numer konta', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_bank_account" name="bank_account" class="regular-text" value="<?php echo esc_attr($organizer->bank_account ?? ''); ?>">
								</p>
							</div>
							<p>
								<label for="bm_billing_address"><strong><?php esc_html_e('Uwagi / adres rozliczeniowy', 'basemgmt'); ?></strong></label><br>
								<textarea id="bm_billing_address" name="billing_address" class="large-text" rows="3"><?php echo esc_textarea($organizer->billing_address ?? ''); ?></textarea>
							</p>
						</div>
					</div>

					<div class="postbox" style="margin-bottom:16px;">
						<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Dane do rozliczenia', 'basemgmt'); ?></h2></div>
						<div class="inside">
							<div class="bm-form-grid">
								<p>
									<label for="bm_settlement_contact_name"><strong><?php esc_html_e('Osoba do rozliczenia', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_settlement_contact_name" name="settlement_contact_name" class="regular-text" value="<?php echo esc_attr($organizer->settlement_contact_name ?? ''); ?>">
								</p>
								<p>
									<label for="bm_settlement_contact_email"><strong><?php esc_html_e('E-mail do rozliczenia', 'basemgmt'); ?></strong></label><br>
									<input type="email" id="bm_settlement_contact_email" name="settlement_contact_email" class="regular-text" value="<?php echo esc_attr($organizer->settlement_contact_email ?? ''); ?>">
								</p>
								<p>
									<label for="bm_settlement_contact_phone"><strong><?php esc_html_e('Telefon do rozliczenia', 'basemgmt'); ?></strong></label><br>
									<input type="text" id="bm_settlement_contact_phone" name="settlement_contact_phone" class="regular-text" value="<?php echo esc_attr($organizer->settlement_contact_phone ?? ''); ?>">
								</p>
							</div>
						</div>
					</div>
					<p>
						<label for="bm_organizer_notes"><strong><?php esc_html_e('Uwagi do organizatora', 'basemgmt'); ?></strong></label><br>
						<textarea id="bm_organizer_notes" name="organizer_notes" class="large-text" rows="3"><?php echo esc_textarea($organizer->notes ?? ''); ?></textarea>
					</p>
					<p class="submit" style="margin-bottom:0;">
						<button type="submit" class="button button-primary"><?php esc_html_e('Zapisz dane organizatora', 'basemgmt'); ?></button>
					</p>
				</form>
			</div>
		</div><!-- /organizer -->

		<!-- ── DOKUMENTY ─────────────────────────────────────────────────────── -->
		<div class="bm-tab-panel" data-tab="documents" id="bm-section-documents">
			<?php
			$doc_types_map = [
				'contract'    => __('Umowa', 'basemgmt'),
				'regulation'  => __('Regulamin', 'basemgmt'),
				'declaration' => __('Deklaracja', 'basemgmt'),
				'document'    => __('Dokument', 'basemgmt'),
				'other'       => __('Inny', 'basemgmt'),
			];
			$doc_status_map = [
				'draft'    => ['label' => __('Szkic', 'basemgmt'),       'class' => 'bm-badge--normal'],
				'ready'    => ['label' => __('Gotowy', 'basemgmt'),      'class' => 'bm-badge--success'],
				'sent'     => ['label' => __('Wysłany', 'basemgmt'),     'class' => 'bm-badge--high'],
				'signed'   => ['label' => __('Podpisany', 'basemgmt'),   'class' => 'bm-badge--success'],
				'accepted' => ['label' => __('Zaakceptowany', 'basemgmt'), 'class' => 'bm-badge--success'],
				'rejected' => ['label' => __('Odrzucony', 'basemgmt'),   'class' => 'bm-badge--urgent'],
			];
			?>
			<!-- ── Deklaracja obozu ─────────────────────────────────────────── -->
			<div class="postbox" style="margin-bottom:20px;">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e('Deklaracja obozu', 'basemgmt'); ?></h2>
				</div>
				<div class="inside">
					<?php
					$decl_status = '';
					if (!empty($camp_declaration)) {
						if (!empty($camp_declaration->signed_at)) {
							$decl_status = '<span class="bm-badge bm-badge--success">✓ ' . esc_html__('Podpisana', 'basemgmt') . '</span>';
						} elseif (!empty($camp_declaration->submitted_at)) {
							$decl_status = '<span class="bm-badge bm-badge--high">' . esc_html__('Wysłana', 'basemgmt') . '</span>';
						} elseif (!$camp_declaration->is_active) {
							$decl_status = '<span class="bm-badge bm-badge--normal">' . esc_html__('Nieaktywna', 'basemgmt') . '</span>';
						} else {
							$decl_status = '<span class="bm-badge bm-badge--normal">' . esc_html__('Szkic', 'basemgmt') . '</span>';
						}
					}
					if ($decl_status) { echo '<p>' . esc_html__('Status:', 'basemgmt') . ' ' . $decl_status . '</p>'; }

					// Build the list of days in this camp's stay
					$camp_dates_range = [];
					if (!empty($camp->start_date) && !empty($camp->end_date)) {
						$d_cur = new DateTime($camp->start_date);
						$d_end = new DateTime($camp->end_date);
						while ($d_cur <= $d_end) {
							$camp_dates_range[] = $d_cur->format('Y-m-d');
							$d_cur->modify('+1 day');
						}
					}
					?>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_save_camp_declaration'); ?>
						<input type="hidden" name="action" value="bm_save_camp_declaration">
						<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">

						<?php if (empty($camp_dates_range)): ?>
							<p class="bm-muted"><?php esc_html_e('Uzupełnij daty obozu (Panel → Podstawowe dane), aby wypełnić deklarację.', 'basemgmt'); ?></p>
						<?php else: ?>
							<?php
							$has_diet_types  = !empty($decl_diet_types);
							$has_accom_types = !empty($decl_accommodation_types);
							$first_date = $camp_dates_range[0];
							$last_date  = $camp_dates_range[count($camp_dates_range) - 1];
							?>
							<div style="overflow-x:auto;">
							<table class="widefat bm-table" style="min-width:600px;">
								<thead>
									<tr>
										<th style="width:110px;"><?php esc_html_e('Data', 'basemgmt'); ?></th>
										<th style="width:80px;"><?php esc_html_e('Osoby', 'basemgmt'); ?></th>
										<?php foreach ($decl_diet_types as $dt): ?>
											<th style="width:90px;" title="<?php echo esc_attr($dt->name); ?>">
												<?php echo esc_html(mb_strimwidth($dt->name, 0, 12, '…')); ?><br>
												<small class="bm-muted"><?php esc_html_e('diety', 'basemgmt'); ?></small>
											</th>
										<?php endforeach; ?>
										<?php foreach ($decl_accommodation_types as $at): ?>
											<th style="width:90px;" title="<?php echo esc_attr($at->name); ?>">
												<?php echo esc_html(mb_strimwidth($at->name, 0, 12, '…')); ?><br>
												<small class="bm-muted"><?php esc_html_e('noclegi', 'basemgmt'); ?></small>
											</th>
										<?php endforeach; ?>
										<th style="width:80px;"><?php esc_html_e('Przyjazd', 'basemgmt'); ?></th>
										<th style="width:80px;"><?php esc_html_e('Wyjazd', 'basemgmt'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($camp_dates_range as $date_str):
										$existing_day = $decl_days_by_date[$date_str] ?? null;
										$day_id       = $existing_day ? (int) $existing_day->id : 0;
										$day_persons  = $existing_day ? (int) $existing_day->declared_persons : 0;
										$day_arrival  = $existing_day ? $existing_day->arrival_time : '';
										$day_departure= $existing_day ? $existing_day->departure_time : '';
										$day_diets    = $day_id ? ($decl_diet_lines_by_day_id[$day_id] ?? []) : [];
										$day_accoms   = $day_id ? ($decl_accom_lines_by_day_id[$day_id] ?? []) : [];
										$is_first     = ($date_str === $first_date);
										$is_last      = ($date_str === $last_date);
									?>
									<tr>
										<td><strong><?php echo esc_html((new DateTime($date_str))->format('d.m.Y')); ?></strong><br><small class="bm-muted"><?php echo esc_html((new DateTime($date_str))->format('D')); ?></small></td>
										<td><input type="number" name="days[<?php echo esc_attr($date_str); ?>][persons]" value="<?php echo esc_attr($day_persons); ?>" min="0" style="width:70px;"></td>
										<?php foreach ($decl_diet_types as $dt): ?>
											<td><input type="number" name="days[<?php echo esc_attr($date_str); ?>][diets][<?php echo esc_attr($dt->id); ?>]"
												value="<?php echo esc_attr($day_diets[$dt->id] ?? 0); ?>" min="0" style="width:70px;"></td>
										<?php endforeach; ?>
										<?php foreach ($decl_accommodation_types as $at): ?>
											<td><input type="number" name="days[<?php echo esc_attr($date_str); ?>][accommodations][<?php echo esc_attr($at->id); ?>]"
												value="<?php echo esc_attr($day_accoms[$at->id] ?? 0); ?>" min="0" style="width:70px;"></td>
										<?php endforeach; ?>
										<td>
											<?php if ($is_first): ?>
												<input type="text" name="days[<?php echo esc_attr($date_str); ?>][arrival_time]"
													value="<?php echo esc_attr($day_arrival); ?>" placeholder="14:00" style="width:70px;">
											<?php else: ?>
												<span class="bm-muted">—</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($is_last): ?>
												<input type="text" name="days[<?php echo esc_attr($date_str); ?>][departure_time]"
													value="<?php echo esc_attr($day_departure); ?>" placeholder="10:00" style="width:70px;">
											<?php else: ?>
												<span class="bm-muted">—</span>
											<?php endif; ?>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							</div>
							<?php if (!$has_diet_types): ?>
								<p class="description"><?php esc_html_e('Brak zdefiniowanych typów diety — dodaj je w CampLink → Jadłospis → Opcje.', 'basemgmt'); ?></p>
							<?php endif; ?>
							<?php if (!$has_accom_types): ?>
								<p class="description"><?php esc_html_e('Brak zdefiniowanych typów noclegów — dodaj je w CampLink → Organizacja → Noclegi.', 'basemgmt'); ?></p>
							<?php endif; ?>
						<?php endif; ?>

						<p style="margin-top:16px;">
							<label>
								<input type="checkbox" name="decl_is_active" value="1" <?php checked($camp_declaration->is_active ?? 1, 1); ?>>
								<strong><?php esc_html_e('Deklaracja aktywna', 'basemgmt'); ?></strong>
							</label>
						</p>
						<p>
							<label for="decl_notes"><strong><?php esc_html_e('Uwagi do deklaracji', 'basemgmt'); ?></strong></label><br>
							<textarea id="decl_notes" name="decl_notes" class="large-text" rows="3"><?php echo esc_textarea($camp_declaration->notes ?? ''); ?></textarea>
						</p>
						<p class="submit" style="margin-bottom:0;">
							<button type="submit" class="button button-primary"><?php esc_html_e('Zapisz deklarację', 'basemgmt'); ?></button>
						</p>
					</form>
				</div>
			</div>
			<!-- ── End Deklaracja obozu ──────────────────────────────────────── -->
			<div class="bm-workcenter-header">
				<h2 style="margin:0;"><?php esc_html_e('Dokumenty obozu', 'basemgmt'); ?></h2>
				<div style="display:flex;gap:8px;">
					<button type="button" class="button" id="bm-add-doc-from-library">
						<?php esc_html_e('+ Dodaj z biblioteki', 'basemgmt'); ?>
					</button>
					<button type="button" class="button button-primary" id="bm-add-doc-from-template">
						<?php esc_html_e('+ Utwórz z szablonu', 'basemgmt'); ?>
					</button>
				</div>
			</div>

			<?php if ( empty($camp_documents) ) : ?>
				<div class="bm-empty-state">
					<span class="dashicons dashicons-media-document" style="font-size:40px;color:#c3c4c7;"></span>
					<p><?php esc_html_e('Brak dokumentów. Dodaj pierwszy.', 'basemgmt'); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped bm-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Tytuł', 'basemgmt'); ?></th>
							<th style="width:120px;"><?php esc_html_e('Typ', 'basemgmt'); ?></th>
							<th style="width:100px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
							<th style="width:140px;"><?php esc_html_e('Data wysłania', 'basemgmt'); ?></th>
							<th style="width:160px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $camp_documents as $doc ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html($doc->title); ?></strong>
									<?php if ( ! empty($doc->locked) ) : ?>
										<span title="<?php esc_attr_e('Dokument wysłany — zablokowany', 'basemgmt'); ?>"> 🔒</span>
									<?php endif; ?>
								</td>
								<td>
									<span class="bm-badge bm-badge--doctype-<?php echo esc_attr($doc->document_type); ?>">
										<?php echo esc_html($doc_types_map[$doc->document_type] ?? $doc->document_type); ?>
									</span>
								</td>
								<td>
									<?php $ds = $doc_status_map[$doc->status] ?? ['label' => $doc->status, 'class' => 'bm-badge--normal']; ?>
									<span class="bm-badge <?php echo esc_attr($ds['class']); ?>"><?php echo esc_html($ds['label']); ?></span>
								</td>
								<td class="bm-muted"><?php echo esc_html($doc->sent_at ?: '—'); ?></td>
								<td>
									<?php if ( ! empty($doc->html_content) ) : ?>
										<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-camps&action=doc_view&id={$id}&doc_id={$doc->id}")); ?>" class="button button-small" target="_blank">
											<?php esc_html_e('Podgląd', 'basemgmt'); ?>
										</a>
									<?php elseif ( ! empty($doc->file_url) ) : ?>
										<a href="<?php echo esc_url($doc->file_url); ?>" class="button button-small" target="_blank">
											<?php esc_html_e('Pobierz', 'basemgmt'); ?>
										</a>
									<?php endif; ?>
									<?php if ( empty($doc->locked) ) : ?>
										<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_send_camp_doc&id={$id}&doc_id={$doc->id}"), "bm_send_camp_doc_{$doc->id}")); ?>"
											class="button button-small"
											data-bm-confirm="<?php esc_attr_e('Wygenerować link do wysłania do klienta?', 'basemgmt'); ?>">
											<?php echo esc_html($doc->document_type === 'declaration' ? __('Wyślij do akceptacji', 'basemgmt') : __('Wyślij do podpisu', 'basemgmt')); ?>
										</a>
										<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_camp_doc&id={$id}&doc_id={$doc->id}"), "bm_delete_camp_doc_{$doc->id}")); ?>"
											class="button button-small bm-danger"
											data-bm-confirm="<?php esc_attr_e('Usunąć dokument?', 'basemgmt'); ?>">
											<?php esc_html_e('Usuń', 'basemgmt'); ?>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<!-- Modal: Dodaj z biblioteki -->
			<div id="bm-modal-library" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Dodaj dokument z biblioteki', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_add_camp_doc_library'); ?>
						<input type="hidden" name="action" value="bm_add_camp_doc_library">
						<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
						<div class="bm-modal-body">
							<?php if ( empty($doc_library_items) ) : ?>
								<p><?php esc_html_e('Brak dokumentów w bibliotece. Dodaj najpierw dokumenty w Organizacja → Dokumenty.', 'basemgmt'); ?></p>
							<?php else : ?>
								<label><strong><?php esc_html_e('Wybierz dokument:', 'basemgmt'); ?></strong></label>
								<select name="library_doc_id" class="widefat" required>
									<option value=""><?php esc_html_e('— Wybierz —', 'basemgmt'); ?></option>
									<?php foreach ( $doc_library_items as $item ) : ?>
										<option value="<?php echo esc_attr($item->id); ?>">
											<?php echo esc_html($item->title . ' (' . ($doc_types_map[$item->doc_type] ?? $item->doc_type) . ')'); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>
						</div>
						<div class="bm-modal-footer">
							<?php if ( ! empty($doc_library_items) ) : ?>
								<button type="submit" class="button button-primary"><?php esc_html_e('Dodaj', 'basemgmt'); ?></button>
							<?php endif; ?>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>

			<!-- Modal: Utwórz z szablonu -->
			<div id="bm-modal-template" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Utwórz dokument z szablonu', 'basemgmt'); ?></h3>
						<button type="button" class="button bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_create_camp_doc_from_template'); ?>
						<input type="hidden" name="action" value="bm_create_camp_doc_from_template">
						<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
						<div class="bm-modal-body">
							<?php if ( empty($doc_templates) ) : ?>
								<p><?php esc_html_e('Brak szablonów. Utwórz je w Organizacja → Szablony.', 'basemgmt'); ?></p>
							<?php else : ?>
								<label><strong><?php esc_html_e('Wybierz szablon:', 'basemgmt'); ?></strong></label>
								<select name="template_id" class="widefat" required>
									<option value=""><?php esc_html_e('— Wybierz —', 'basemgmt'); ?></option>
									<?php foreach ( $doc_templates as $tpl ) : ?>
										<option value="<?php echo esc_attr($tpl->id); ?>">
											<?php echo esc_html($tpl->title . ' (' . ($doc_types_map[$tpl->doc_type] ?? $tpl->doc_type) . ')'); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>
						</div>
						<div class="bm-modal-footer">
							<?php if ( ! empty($doc_templates) ) : ?>
								<button type="submit" class="button button-primary"><?php esc_html_e('Utwórz dokument', 'basemgmt'); ?></button>
							<?php endif; ?>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div><!-- /documents -->

		<!-- ── FINANSE ───────────────────────────────────────────────────────── -->
		<div class="bm-tab-panel" data-tab="finance" id="bm-section-finance">
			<div class="bm-workcenter-header">
				<h2 style="margin:0;"><?php esc_html_e('Finanse obozu', 'basemgmt'); ?></h2>
			</div>

			<form id="bm-finance-form"method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<?php wp_nonce_field('bm_save_camp_finance'); ?>
				<input type="hidden" name="action" value="bm_save_camp_finance">
				<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">

				<div class="bm-task-body" style="align-items:flex-start;">
					<div class="bm-task-main">
						<div class="postbox">
							<div class="postbox-header">
								<h2 class="hndle"><?php esc_html_e('Harmonogram płatności', 'basemgmt'); ?></h2>
							</div>
							<div class="inside">
								<?php if ( empty($payment_schedules) ) : ?>
									<p class="bm-muted"><?php esc_html_e('Brak pozycji. Wybierz pakiet lub dodaj ręcznie.', 'basemgmt'); ?></p>
								<?php else : ?>
									<table class="widefat bm-table" id="bm-payment-lines-table">
										<thead>
											<tr>
												<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
												<th style="width:120px;"><?php esc_html_e('Typ', 'basemgmt'); ?></th>
												<th style="width:100px;"><?php esc_html_e('Kwota', 'basemgmt'); ?></th>
												<th style="width:120px;"><?php esc_html_e('Termin', 'basemgmt'); ?></th>
												<th style="width:90px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
												<th style="width:40px;"></th>
											</tr>
										</thead>
										<tbody id="bm-payment-tbody">
											<?php foreach ( $payment_schedules as $sched ) : ?>
												<tr>
													<td><input type="hidden" name="sched_id[]" value="<?php echo esc_attr($sched->id); ?>">
													<input type="text" name="sched_label[]" class="widefat" value="<?php echo esc_attr($sched->label); ?>"></td>
													<td>
														<select name="sched_type[]" class="widefat bm-sched-type">
															<option value="deposit" <?php selected($sched->payment_type, 'deposit'); ?>><?php esc_html_e('Zaliczka', 'basemgmt'); ?></option>
															<option value="accommodation" <?php selected($sched->payment_type, 'accommodation'); ?>><?php esc_html_e('Nocleg', 'basemgmt'); ?></option>
															<option value="food" <?php selected($sched->payment_type, 'food'); ?>><?php esc_html_e('Wyżywienie', 'basemgmt'); ?></option>
															<option value="tax" <?php selected($sched->payment_type, 'tax'); ?>><?php esc_html_e('Podatek', 'basemgmt'); ?></option>
															<option value="extra_fee" <?php selected($sched->payment_type, 'extra_fee'); ?>><?php esc_html_e('Opłata dodatkowa', 'basemgmt'); ?></option>
															<option value="surcharge" <?php selected($sched->payment_type, 'surcharge'); ?>><?php esc_html_e('Dopłata', 'basemgmt'); ?></option>
															<option value="discount" <?php selected($sched->payment_type, 'discount'); ?>><?php esc_html_e('Rabat', 'basemgmt'); ?></option>
															<option value="penalty" <?php selected($sched->payment_type, 'penalty'); ?>><?php esc_html_e('Kara umowna', 'basemgmt'); ?></option>
															<option value="other" <?php selected($sched->payment_type, 'other'); ?>><?php esc_html_e('Inne', 'basemgmt'); ?></option>
														</select>
													</td>
													<td style="white-space:nowrap;"><input type="number" name="sched_amount[]" class="bm-sched-amount" style="width:72px;" step="0.01" value="<?php echo esc_attr($sched->amount); ?>"><select name="sched_amount_type[]" class="bm-amount-type" style="width:54px;"><option value="fixed" <?php selected($sched->amount_type ?? 'fixed', 'fixed'); ?>>PLN</option><option value="percent" <?php selected($sched->amount_type ?? 'fixed', 'percent'); ?>>%</option></select></td>
													<td><input type="date" name="sched_due_date[]" class="widefat" value="<?php echo esc_attr($sched->due_date ?? ''); ?>"></td>
													<td>
														<select name="sched_status[]" class="widefat">
															<option value="expected" <?php selected($sched->status, 'expected'); ?>><?php esc_html_e('Oczekiwana', 'basemgmt'); ?></option>
															<option value="paid" <?php selected($sched->status, 'paid'); ?>><?php esc_html_e('Zapłacona', 'basemgmt'); ?></option>
															<option value="overdue" <?php selected($sched->status, 'overdue'); ?>><?php esc_html_e('Po terminie', 'basemgmt'); ?></option>
															<option value="cancelled" <?php selected($sched->status, 'cancelled'); ?>><?php esc_html_e('Anulowana', 'basemgmt'); ?></option>
														</select>
													</td>
													<td>
														<button type="button" class="button-link bm-remove-sched-row" title="<?php esc_attr_e('Usuń', 'basemgmt'); ?>">✕</button>
													</td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								<?php endif; ?>
								<!-- Live finance summary -->
								<div id="bm-finance-summary" style="margin-top:14px;padding:12px 16px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;">
									<table style="width:100%;max-width:380px;margin-left:auto;border-collapse:collapse;font-size:13px;">
										<tr><td style="padding:3px 8px;"><?php esc_html_e('Suma (brutto):', 'basemgmt'); ?></td><td style="text-align:right;padding:3px 8px;"><span id="bm-finance-subtotal">0,00 zł</span></td></tr>
										<tr><td style="padding:3px 8px;color:#d63638;"><?php esc_html_e('Rabaty:', 'basemgmt'); ?></td><td style="text-align:right;padding:3px 8px;color:#d63638;"><span id="bm-finance-discount">−0,00 zł</span></td></tr>
										<tr style="font-weight:600;border-top:1px solid #c3c4c7;"><td style="padding:6px 8px 3px;"><?php esc_html_e('Do zapłaty:', 'basemgmt'); ?></td><td style="text-align:right;padding:6px 8px 3px;"><span id="bm-finance-total">0,00 zł</span></td></tr>
										<tr><td style="padding:3px 8px;color:#00a32a;"><?php esc_html_e('Zapłacono:', 'basemgmt'); ?></td><td style="text-align:right;padding:3px 8px;color:#00a32a;"><span id="bm-finance-paid">0,00 zł</span></td></tr>
										<tr style="font-weight:600;"><td style="padding:3px 8px;"><?php esc_html_e('Pozostało:', 'basemgmt'); ?></td><td style="text-align:right;padding:3px 8px;"><span id="bm-finance-remaining">0,00 zł</span></td></tr>
									</table>
								</div>


								<p style="margin-top:10px;">
									<button type="button" class="button" id="bm-add-payment-row">+ <?php esc_html_e('Dodaj pozycję', 'basemgmt'); ?></button>
								</p>
							</div>
						</div>
					</div>

					<div class="bm-task-sidebar">
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Pakiet finansowy', 'basemgmt'); ?></h2></div>
					<div class="inside">
						<?php $has_declaration = ! empty($camp_declaration) && $camp_declaration->is_active; ?>
						<?php if ( ! $has_declaration ) : ?>
						<div class="notice notice-warning inline" style="margin:0 0 12px;padding:8px 12px;">
							<p style="margin:0;font-size:12px;"><?php esc_html_e('Brak aktywnej deklaracji — uzupełnij deklarację aby móc zastosować pakiet.', 'basemgmt'); ?></p>
						</div>
						<?php endif; ?>
						<p class="description"><?php esc_html_e('Pakiet wylicza koszty na podstawie deklaracji. Daty terminu płatności obliczane od daty przyjazdu.', 'basemgmt'); ?></p>
						<p>
							<label><strong><?php esc_html_e('Pakiet:', 'basemgmt'); ?></strong></label><br>
							<select id="bm_finance_package" name="apply_package" class="widefat" <?php echo $has_declaration ? '' : 'disabled'; ?>>
								<option value=""><?php esc_html_e('— nie wybrano —', 'basemgmt'); ?></option>
								<?php foreach ( $payment_packages as $pkg ) : ?>
									<option value="<?php echo esc_attr($pkg->id); ?>" <?php selected($camp_finance_package_id ?? 0, $pkg->id); ?>>
										<?php echo esc_html($pkg->name . ($pkg->is_default ? ' ★' : '')); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</p>
						<input type="hidden" name="keep_existing_schedules" id="bm-keep-existing-sched" value="0">
						<p>
							<button type="button" id="bm-apply-package-btn" class="button button-secondary" style="width:100%;" <?php echo $has_declaration ? '' : 'disabled'; ?>>
								<?php esc_html_e('Zastosuj pakiet', 'basemgmt'); ?>
							</button>
						</p>
					</div>
				</div>
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Zapisz', 'basemgmt'); ?></h2></div>
					<div class="inside">
						<button type="submit" class="button button-primary" style="width:100%;">
							<?php esc_html_e('Zapisz harmonogram', 'basemgmt'); ?>
						</button>
					</div>
				</div>
			</div>
			</div>
		</form>
		<script>
		(function() {
			var btn = document.getElementById('bm-apply-package-btn');
			if (!btn) return;
			var keepField = document.getElementById('bm-keep-existing-sched');
			var tbody = document.querySelector('#bm-finance-form tbody');
			var hasRows = tbody ? tbody.querySelectorAll('tr').length > 0 : false;

			function submitApply() {
				var form = btn.closest('form');
				var hidden = document.createElement('input');
				hidden.type = 'hidden';
				hidden.name = 'apply_package_btn';
				hidden.value = '1';
				form.appendChild(hidden);
				form.submit();
			}

			btn.addEventListener('click', function() {
				var pkgSel = document.getElementById('bm_finance_package');
				if (!pkgSel || !pkgSel.value) {
					bmModal.alert('<?php esc_attr_e('Wybierz pakiet finansowy.', 'basemgmt'); ?>');
					return;
				}
				if (hasRows) {
					bmModal.confirm(
						'<?php esc_attr_e('W harmonogramie są już pozycje. Co chcesz zrobić?', 'basemgmt'); ?>',
						function() {},
						'<?php esc_attr_e('Zastosuj pakiet', 'basemgmt'); ?>'
					);
					setTimeout(function() {
						var footer = document.getElementById('bm-modal-footer');
						if (!footer) return;
						footer.innerHTML = '';
						var btnCancel = document.createElement('button');
						btnCancel.type = 'button'; btnCancel.className = 'button';
						btnCancel.textContent = '<?php esc_attr_e('Anuluj', 'basemgmt'); ?>';
						btnCancel.addEventListener('click', function() {
							document.getElementById('bm-modal-overlay').classList.remove('is-open');
						});
						var btnKeep = document.createElement('button');
						btnKeep.type = 'button'; btnKeep.className = 'button button-secondary';
						btnKeep.textContent = '<?php esc_attr_e('Zachowaj istniejące', 'basemgmt'); ?>';
						btnKeep.addEventListener('click', function() {
							keepField.value = '1';
							document.getElementById('bm-modal-overlay').classList.remove('is-open');
							submitApply();
						});
						var btnReplace = document.createElement('button');
						btnReplace.type = 'button'; btnReplace.className = 'button button-primary';
						btnReplace.textContent = '<?php esc_attr_e('Nadpisz harmonogram', 'basemgmt'); ?>';
						btnReplace.addEventListener('click', function() {
							keepField.value = '0';
							document.getElementById('bm-modal-overlay').classList.remove('is-open');
							submitApply();
						});
						footer.appendChild(btnCancel);
						footer.appendChild(btnKeep);
						footer.appendChild(btnReplace);
					}, 20);
				} else {
					keepField.value = '0';
					submitApply();
				}
			});
		})();
		</script>
<!-- ── Szkody ──────────────────────────────────────────────────── -->
			<div class="postbox" style="margin-top:20px;">
				<div class="postbox-header">
					<h2 class="hndle"><span class="dashicons dashicons-warning" style="font-size:16px;width:16px;height:16px;line-height:1;color:#d63638;"></span> <?php esc_html_e('Szkody', 'basemgmt'); ?></h2>
				</div>
				<div class="inside">
					<?php if (empty($camp_damages)): ?>
						<p class="bm-muted"><?php esc_html_e('Brak zarejestrowanych szkód.', 'basemgmt'); ?></p>
					<?php else: ?>
						<table class="widefat bm-table">
							<thead><tr>
								<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
								<th style="width:200px;"><?php esc_html_e('Opis', 'basemgmt'); ?></th>
								<th style="width:100px;"><?php esc_html_e('Koszt (PLN)', 'basemgmt'); ?></th>
								<th style="width:100px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
								<th style="width:60px;"></th>
							</tr></thead>
							<tbody>
								<?php $damage_status_labels = [
									'reported'      => __('Zgłoszona', 'basemgmt'),
									'investigating' => __('W ocenie', 'basemgmt'),
									'settled'       => __('Rozliczona', 'basemgmt'),
									'dismissed'     => __('Odrzucona', 'basemgmt'),
								]; ?>
								<?php foreach ($camp_damages as $dmg): ?>
								<tr>
									<td><strong><?php echo esc_html($dmg->name); ?></strong></td>
									<td class="bm-muted"><?php echo esc_html($dmg->description ?: '—'); ?></td>
									<td><?php echo esc_html(number_format((float)$dmg->cost, 2, ',', ' ')); ?> zł</td>
									<td><span class="bm-badge bm-badge--<?php echo esc_attr($dmg->status === 'settled' ? 'success' : ($dmg->status === 'dismissed' ? 'normal' : 'pending')); ?>">
										<?php echo esc_html($damage_status_labels[$dmg->status] ?? $dmg->status); ?>
									</span></td>
									<td>
										<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_camp_damage&id={$id}&damage_id={$dmg->id}"), "bm_delete_camp_damage_{$dmg->id}")); ?>"
											class="button-link bm-danger"
											data-bm-confirm="<?php esc_attr_e('Usunąć szkodę?', 'basemgmt'); ?>">✕</a>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
							<tfoot><tr>
								<td colspan="2" style="text-align:right;"><strong><?php esc_html_e('Łącznie:', 'basemgmt'); ?></strong></td>
								<td><strong><?php echo esc_html(number_format((float) array_sum(array_column((array)$camp_damages, 'cost')), 2, ',', ' ')); ?> zł</strong></td>
								<td colspan="2"></td>
							</tr></tfoot>
						</table>
					<?php endif; ?>

					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:16px;padding-top:16px;border-top:1px solid #dcdcde;">
						<?php wp_nonce_field('bm_add_camp_damage'); ?>
						<input type="hidden" name="action" value="bm_add_camp_damage">
						<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
						<h4 style="margin:0 0 12px;"><?php esc_html_e('Dodaj szkodę', 'basemgmt'); ?></h4>
						<div class="bm-form-grid" style="margin:0 0 12px;">
							<p>
								<label for="damage_name"><strong><?php esc_html_e('Nazwa szkody', 'basemgmt'); ?></strong></label><br>
								<input type="text" id="damage_name" name="damage_name" class="regular-text" required>
							</p>
							<p>
								<label for="damage_cost"><strong><?php esc_html_e('Koszt (PLN)', 'basemgmt'); ?></strong></label><br>
								<input type="text" id="damage_cost" name="damage_cost" class="regular-text" value="0.00">
							</p>
							<p>
								<label for="damage_status"><strong><?php esc_html_e('Status', 'basemgmt'); ?></strong></label><br>
								<select id="damage_status" name="damage_status" class="regular-text">
									<option value="reported"><?php esc_html_e('Zgłoszona', 'basemgmt'); ?></option>
									<option value="investigating"><?php esc_html_e('W ocenie', 'basemgmt'); ?></option>
									<option value="settled"><?php esc_html_e('Rozliczona', 'basemgmt'); ?></option>
									<option value="dismissed"><?php esc_html_e('Odrzucona', 'basemgmt'); ?></option>
								</select>
							</p>
						</div>
						<p>
							<label for="damage_description"><strong><?php esc_html_e('Opis', 'basemgmt'); ?></strong></label><br>
							<textarea id="damage_description" name="damage_description" class="large-text" rows="2"></textarea>
						</p>
						<p>
							<button type="submit" class="button button-secondary"><?php esc_html_e('Dodaj szkodę', 'basemgmt'); ?></button>
						</p>
					</form>
				</div>
			</div>
		</div><!-- /finance -->


	<?php else : ?>

		<!-- ── NOWY OBÓZ ─────────────────────────────────────────────────────── -->
		<h1><?php esc_html_e('Nowy obóz', 'basemgmt'); ?></h1>
		<div class="bm-form-section">
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<?php wp_nonce_field('bm_save_camp_overview'); ?>
				<input type="hidden" name="action" value="bm_save_camp_overview">
				<input type="hidden" name="camp_id" value="0">
				<div class="bm-form-grid">
					<p>
						<label for="bm_name"><strong><?php esc_html_e('Nazwa obozu', 'basemgmt'); ?></strong></label><br>
						<input type="text" id="bm_name" name="name" class="regular-text" required value="">
					</p>
					<p>
						<label for="bm_status"><strong><?php esc_html_e('Status pobytu', 'basemgmt'); ?></strong></label><br>
						<select id="bm_status" name="status">
							<option value="active"><?php esc_html_e('Aktywny', 'basemgmt'); ?></option>
							<option value="ended"><?php esc_html_e('Zakończony', 'basemgmt'); ?></option>
							<option value="archived"><?php esc_html_e('Archiwalny', 'basemgmt'); ?></option>
						</select>
					</p>
					<p>
						<label for="bm_start"><strong><?php esc_html_e('Data rozpoczęcia', 'basemgmt'); ?></strong></label><br>
						<input type="date" id="bm_start" name="start_date" required value="">
					</p>
					<p>
						<label for="bm_end"><strong><?php esc_html_e('Data zakończenia', 'basemgmt'); ?></strong></label><br>
						<input type="date" id="bm_end" name="end_date" required value="">
					</p>
				</div>
				<p class="submit" style="margin-bottom:0;">
					<button type="submit" class="button button-primary"><?php esc_html_e('Utwórz obóz i rozpocznij workflow', 'basemgmt'); ?></button>
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps')); ?>"><?php esc_html_e('Wróć do listy', 'basemgmt'); ?></a>
				</p>
			</form>
		</div>

	<?php endif; ?>
</div>
<?php if ( $is_edit ) : ?>
<script>
(function() {
	// ── Document modals ───────────────────────────────────────────────────────
	function openModal(id) { document.getElementById(id).style.display = 'flex'; }
	function closeModal(id) { document.getElementById(id).style.display = 'none'; }
	var libBtn = document.getElementById('bm-add-doc-from-library');
	var tplBtn = document.getElementById('bm-add-doc-from-template');
	if (libBtn) libBtn.addEventListener('click', function() { openModal('bm-modal-library'); });
	if (tplBtn) tplBtn.addEventListener('click', function() { openModal('bm-modal-template'); });
	document.querySelectorAll('.bm-modal-close').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var overlay = btn.closest('.bm-modal-overlay');
			if (overlay) overlay.style.display = 'none';
		});
	});
	document.querySelectorAll('.bm-modal-overlay').forEach(function(overlay) {
		overlay.addEventListener('click', function(e) {
			if (e.target === overlay) overlay.style.display = 'none';
		});
	});

	var addTaskBtn = document.getElementById('bm-add-task-from-template');
	if (addTaskBtn) {
		addTaskBtn.addEventListener('click', function() {
			document.getElementById('bm-modal-task-template').style.display = 'flex';
		});
	}

	// ── Finance: add payment row + live totals ──────────────────────────
	var TYPE_OPTS = '<option value="deposit"><?php esc_js(esc_html_e('Zaliczka','basemgmt')); ?></option><option value="accommodation"><?php esc_js(esc_html_e('Nocleg','basemgmt')); ?></option><option value="food"><?php esc_js(esc_html_e('Wyżywienie','basemgmt')); ?></option><option value="tax"><?php esc_js(esc_html_e('Podatek','basemgmt')); ?></option><option value="extra_fee"><?php esc_js(esc_html_e('Opłata dodatkowa','basemgmt')); ?></option><option value="surcharge"><?php esc_js(esc_html_e('Dopłata','basemgmt')); ?></option><option value="discount"><?php esc_js(esc_html_e('Rabat','basemgmt')); ?></option><option value="penalty"><?php esc_js(esc_html_e('Kara umowna','basemgmt')); ?></option><option value="other"><?php esc_js(esc_html_e('Inne','basemgmt')); ?></option>';
	var STATUS_OPTS = '<option value="expected"><?php esc_js(esc_html_e('Oczekiwana','basemgmt')); ?></option><option value="paid"><?php esc_js(esc_html_e('Zapłacona','basemgmt')); ?></option><option value="overdue"><?php esc_js(esc_html_e('Po terminie','basemgmt')); ?></option><option value="cancelled"><?php esc_js(esc_html_e('Anulowana','basemgmt')); ?></option>';
	var AMT_OPTS = '<option value="fixed">PLN</option><option value="percent">%</option>';

	function bmFmt(n) { return n.toFixed(2).replace('.', ',') + ' zł'; }

	function bmRecalcFinance() {
		var rows = document.querySelectorAll('#bm-payment-tbody tr');
		var subtotal = 0, totalDiscount = 0, totalPaid = 0;
		rows.forEach(function(row) {
			var typeEl   = row.querySelector('[name="sched_type[]"]');
			var amtEl    = row.querySelector('[name="sched_amount[]"]');
			var statusEl = row.querySelector('[name="sched_status[]"]');
			if (!typeEl || !amtEl) return;
			var type = typeEl.value, amount = parseFloat(amtEl.value) || 0, status = statusEl ? statusEl.value : '';
			if (status === 'cancelled') return;
			if (type !== 'discount') subtotal += amount;
		});
		rows.forEach(function(row) {
			var typeEl    = row.querySelector('[name="sched_type[]"]');
			var amtEl     = row.querySelector('[name="sched_amount[]"]');
			var amtTypeEl = row.querySelector('[name="sched_amount_type[]"]');
			var statusEl  = row.querySelector('[name="sched_status[]"]');
			if (!typeEl || !amtEl) return;
			var type = typeEl.value, amtType = amtTypeEl ? amtTypeEl.value : 'fixed';
			var amount = parseFloat(amtEl.value) || 0, status = statusEl ? statusEl.value : '';
			if (status === 'cancelled') return;
			if (type === 'discount') { totalDiscount += (amtType === 'percent') ? subtotal * amount / 100 : amount; }
			if (status === 'paid' && type !== 'discount') { totalPaid += amount; }
		});
		var total = subtotal - totalDiscount, remaining = total - totalPaid;
		var el = function(id) { return document.getElementById(id); };
		if (el('bm-finance-subtotal'))  el('bm-finance-subtotal').textContent  = bmFmt(subtotal);
		if (el('bm-finance-discount'))  el('bm-finance-discount').textContent  = '−' + bmFmt(totalDiscount);
		if (el('bm-finance-total'))     el('bm-finance-total').textContent     = bmFmt(total);
		if (el('bm-finance-paid'))      el('bm-finance-paid').textContent      = bmFmt(totalPaid);
		if (el('bm-finance-remaining')) el('bm-finance-remaining').textContent = bmFmt(remaining);
	}

	document.addEventListener('change', function(e) {
		if (e.target.closest && e.target.closest('#bm-payment-lines-table, #bm-payment-tbody')) bmRecalcFinance();
	});
	document.addEventListener('input', function(e) {
		if (e.target.name === 'sched_amount[]') bmRecalcFinance();
	});

	var addPayBtn = document.getElementById('bm-add-payment-row');
	if (addPayBtn) {
		addPayBtn.addEventListener('click', function() {
			var tbody = document.getElementById('bm-payment-tbody');
			if (!tbody) {
				var wrap = addPayBtn.closest('.inside');
				var noRows = wrap.querySelector('.bm-muted');
				if (noRows) noRows.remove();
				var tbl = document.createElement('table');
				tbl.id = 'bm-payment-lines-table';
				tbl.className = 'widefat bm-table';
				tbl.innerHTML = '<thead><tr><th><?php esc_js(esc_html_e('Nazwa','basemgmt')); ?></th><th style="width:120px;"><?php esc_js(esc_html_e('Typ','basemgmt')); ?></th><th style="width:130px;"><?php esc_js(esc_html_e('Kwota','basemgmt')); ?></th><th style="width:120px;"><?php esc_js(esc_html_e('Termin','basemgmt')); ?></th><th style="width:90px;"><?php esc_js(esc_html_e('Status','basemgmt')); ?></th><th style="width:40px;"></th></tr></thead><tbody id="bm-payment-tbody"></tbody>';
				wrap.insertBefore(tbl, addPayBtn.parentElement);
				tbody = document.getElementById('bm-payment-tbody');
			}
			var tr = document.createElement('tr');
			tr.innerHTML = '<td><input type="hidden" name="sched_id[]" value="0"><input type="text" name="sched_label[]" class="widefat" placeholder="<?php esc_js(esc_attr_e('Nazwa','basemgmt')); ?>"></td>'
				+ '<td><select name="sched_type[]" class="widefat bm-sched-type">' + TYPE_OPTS + '</select></td>'
				+ '<td style="white-space:nowrap;"><input type="number" name="sched_amount[]" class="bm-sched-amount" style="width:72px;" step="0.01" value="0.00"><select name="sched_amount_type[]" class="bm-amount-type" style="width:54px;">' + AMT_OPTS + '</select></td>'
				+ '<td><input type="date" name="sched_due_date[]" class="widefat"></td>'
				+ '<td><select name="sched_status[]" class="widefat">' + STATUS_OPTS + '</select></td>'
				+ '<td><button type="button" class="button-link bm-remove-sched-row">✕</button></td>';
			tbody.appendChild(tr);
			bmRecalcFinance();
		});
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('bm-remove-sched-row')) { e.target.closest('tr').remove(); bmRecalcFinance(); }
		});
	}
	bmRecalcFinance();
})();
</script>
<?php endif; ?>



