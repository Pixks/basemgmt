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
	'offer'             => ['label' => __('Oferta', 'basemgmt'),        'slug' => 'zapytanie'],
	'negotiation'       => ['label' => __('Oferta', 'basemgmt'),        'slug' => 'zapytanie'],
	'tentative_booking' => ['label' => __('Oferta', 'basemgmt'),        'slug' => 'zapytanie'],
	'contract_draft'    => ['label' => __('Umowa', 'basemgmt'),         'slug' => 'przygotowanie'],
	'contract_signed'   => ['label' => __('Umowa', 'basemgmt'),         'slug' => 'przygotowanie'],
	'awaiting_payment'  => ['label' => __('Umowa', 'basemgmt'),         'slug' => 'przygotowanie'],
	'ready_for_arrival' => ['label' => __('Umowa', 'basemgmt'),         'slug' => 'przygotowanie'],
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
			<a href="#" class="nav-tab" data-tab="equipment"><?php esc_html_e('Sprzęt', 'basemgmt'); ?></a>
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
					<button type="button" class="button" id="bm-add-doc-custom">
						<?php esc_html_e('+ Dodaj własny', 'basemgmt'); ?>
					</button>
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
							<th style="width:140px;"><?php esc_html_e('Podpisano', 'basemgmt'); ?></th>
							<th style="width:200px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $camp_documents as $doc ) :
							$doc_atts = $camp_doc_attachments[(int) $doc->id] ?? [];
						?>
							<tr>
								<td>
									<strong><?php echo esc_html($doc->title); ?></strong>
									<?php if ( ! empty($doc->locked) ) : ?>
										<span title="<?php esc_attr_e('Dokument wysłany — zablokowany', 'basemgmt'); ?>"> 🔒</span>
									<?php endif; ?>
									<?php if ( ! empty($doc_atts) ) : ?>
										<br><span class="bm-muted" style="font-size:11px;">
											<?php foreach ( $doc_atts as $att ) : ?>
												<a href="<?php echo esc_url($att->file_url); ?>" target="_blank"><?php echo esc_html($att->file_name ?: basename($att->file_url)); ?></a>
												<?php if ( empty($doc->locked) ) : ?>
													<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_camp_doc_attachment&id={$id}&att_id={$att->id}"), "bm_delete_camp_doc_att_{$att->id}")); ?>"
														class="bm-danger bm-link-small"
														data-bm-confirm="<?php esc_attr_e('Usunąć załącznik?', 'basemgmt'); ?>">✕</a>
												<?php endif; ?>
											<?php endforeach; ?>
										</span>
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
								<td class="bm-muted" style="font-size:11px;">
									<?php if ( ! empty($doc->signed_at) ) : ?>
										<?php echo esc_html(substr($doc->signed_at, 0, 10)); ?><br>
										<span><?php echo esc_html($doc->signed_method === 'qualified' ? __('kwalifikowany', 'basemgmt') : __('skan', 'basemgmt')); ?></span>
										<?php if ( ! empty($doc->signed_file_url) ) : ?>
											<br><a href="<?php echo esc_url($doc->signed_file_url); ?>" target="_blank"><?php esc_html_e('Pobierz podpisany', 'basemgmt'); ?></a>
										<?php endif; ?>
									<?php else : ?>—<?php endif; ?>
								</td>
								<td>
									<?php if ( ! empty($doc->html_content) ) : ?>
										<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-camps&action=doc_view&id={$id}&doc_id={$doc->id}")); ?>" class="button button-small" target="_blank">
											<?php esc_html_e('Podgląd', 'basemgmt'); ?>
										</a>
										<?php if ( empty($doc->locked) ) : ?>
											<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-camps&action=doc_edit&id={$id}&doc_id={$doc->id}")); ?>" class="button button-small">
												<?php esc_html_e('Edytuj treść', 'basemgmt'); ?>
											</a>
										<?php endif; ?>
									<?php elseif ( ! empty($doc->file_url) ) : ?>
										<a href="<?php echo esc_url($doc->file_url); ?>" class="button button-small" target="_blank">
											<?php esc_html_e('Pobierz', 'basemgmt'); ?>
										</a>
									<?php endif; ?>
									<?php if ( $doc->status !== 'signed' ) : ?>
										<button type="button" class="button button-small bm-modal-open"
											data-modal="bm-modal-sign-doc"
											data-doc-id="<?php echo esc_attr($doc->id); ?>"
											data-doc-title="<?php echo esc_attr($doc->title); ?>">
											<?php esc_html_e('Prześlij podpisany', 'basemgmt'); ?>
										</button>
									<?php endif; ?>
									<?php if ( empty($doc->locked) ) : ?>
										<button type="button" class="button button-small bm-modal-open"
											data-modal="bm-modal-add-camp-doc-att"
											data-doc-id="<?php echo esc_attr($doc->id); ?>"
											data-doc-title="<?php echo esc_attr($doc->title); ?>">
											<?php esc_html_e('+ Załącznik', 'basemgmt'); ?>
										</button>
										<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_send_camp_doc&id={$id}&doc_id={$doc->id}"), "bm_send_camp_doc_{$doc->id}")); ?>"
											class="button button-small"
											data-bm-confirm="<?php esc_attr_e('Wygenerować link do wysłania do klienta?', 'basemgmt'); ?>">
											<?php esc_html_e('Wyślij', 'basemgmt'); ?>
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

			<!-- ── Deklaracje dokumentowe ───────────────────────────────────── -->
			<div class="postbox" style="margin-top:24px;">
				<div class="postbox-header" style="display:flex;align-items:center;justify-content:space-between;">
					<h2 class="hndle"><?php esc_html_e('Deklaracje', 'basemgmt'); ?></h2>
					<div style="display:flex;gap:8px;margin:8px 12px;">
						<button type="button" class="button bm-modal-open" data-modal="bm-modal-add-decl-custom">
							<?php esc_html_e('+ Dodaj własną', 'basemgmt'); ?>
						</button>
						<button type="button" class="button button-primary bm-modal-open" data-modal="bm-modal-add-decl">
							<?php esc_html_e('+ Dodaj z biblioteki', 'basemgmt'); ?>
						</button>
					</div>
				</div>
				<div class="inside">
					<p class="description"><?php esc_html_e('Deklaracje wymagają jedynie kliknięcia „Zatwierdź" przez upoważnioną osobę — system rejestruje, kto i kiedy zatwierdził.', 'basemgmt'); ?></p>
					<?php if ( empty($camp_decl_docs) ) : ?>
						<p class="bm-muted"><?php esc_html_e('Brak deklaracji przypisanych do obozu.', 'basemgmt'); ?></p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped bm-table">
							<thead>
								<tr>
									<th><?php esc_html_e('Tytuł', 'basemgmt'); ?></th>
									<th style="width:110px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
									<th style="width:200px;"><?php esc_html_e('Zatwierdzone przez', 'basemgmt'); ?></th>
									<th style="width:160px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$decl_status_map = [
									'draft'    => ['label' => __('Oczekuje', 'basemgmt'),    'class' => 'bm-badge--normal'],
									'approved' => ['label' => __('Zatwierdzona', 'basemgmt'), 'class' => 'bm-badge--success'],
								];
								foreach ( $camp_decl_docs as $ddoc ) :
									$ds        = $decl_status_map[$ddoc->status] ?? ['label' => $ddoc->status, 'class' => 'bm-badge--normal'];
									$approver  = ! empty($ddoc->approved_by) ? get_user_by('id', (int) $ddoc->approved_by) : null;
									$decl_atts = $camp_decl_attachments[(int) $ddoc->id] ?? [];
									$sent_to_camp = ! empty($ddoc->sent_to_camp);
									$camp_approved = ! empty($ddoc->camp_approved_at);
								?>
									<tr>
										<td>
											<strong><?php echo esc_html($ddoc->title); ?></strong>
											<?php if ( ! empty($ddoc->locked) ) : ?>
												<span title="<?php esc_attr_e('Deklaracja wysłana — zablokowana', 'basemgmt'); ?>"> 🔒</span>
											<?php endif; ?>
											<?php if ( $sent_to_camp ) : ?>
												<span class="bm-badge bm-badge--info" style="margin-left:4px;font-size:10px;"><?php esc_html_e('W obozie', 'basemgmt'); ?></span>
											<?php endif; ?>
											<?php if ( $camp_approved ) : ?>
												<span class="bm-badge bm-badge--success" style="margin-left:4px;font-size:10px;"><?php esc_html_e('Obóz zatwierdził', 'basemgmt'); ?></span>
											<?php endif; ?>
											<?php if ( ! empty($decl_atts) ) : ?>
												<br><span class="bm-muted" style="font-size:11px;">
													<?php foreach ( $decl_atts as $att ) : ?>
														<a href="<?php echo esc_url($att->file_url); ?>" target="_blank"><?php echo esc_html($att->file_name ?: basename($att->file_url)); ?></a>
														<?php if ( empty($ddoc->locked) ) : ?>
															<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_camp_decl_attachment&id={$id}&att_id={$att->id}"), "bm_delete_camp_decl_att_{$att->id}")); ?>"
																class="bm-danger bm-link-small"
																data-bm-confirm="<?php esc_attr_e('Usunąć załącznik?', 'basemgmt'); ?>">✕</a>
														<?php endif; ?>
													<?php endforeach; ?>
												</span>
											<?php endif; ?>
										</td>
										<td><span class="bm-badge <?php echo esc_attr($ds['class']); ?>"><?php echo esc_html($ds['label']); ?></span></td>
										<td class="bm-muted" style="font-size:11px;">
											<?php if ( $approver && ! empty($ddoc->approved_at) ) : ?>
												<?php echo esc_html($approver->display_name); ?><br>
												<?php echo esc_html(substr($ddoc->approved_at, 0, 16)); ?>
											<?php else : ?>—<?php endif; ?>
										</td>
										<td>
											<?php if ( ! empty($ddoc->html_content) && $ddoc->status === 'approved' ) : ?>
												<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-camps&action=decl_view&id={$id}&decl_id={$ddoc->id}")); ?>" class="button button-small" target="_blank">
													<?php esc_html_e('Podgląd', 'basemgmt'); ?>
												</a>
											<?php elseif ( ! empty($ddoc->file_url) && empty($ddoc->html_content) ) : ?>
												<a href="<?php echo esc_url($ddoc->file_url); ?>" class="button button-small" target="_blank">
													<?php esc_html_e('Pobierz', 'basemgmt'); ?>
												</a>
											<?php endif; ?>
											<?php if ( empty($ddoc->locked) ) : ?>
												<?php if ( $ddoc->status !== 'approved' ) : ?>
													<a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-camps&action=decl_edit&id={$id}&decl_id={$ddoc->id}")); ?>" class="button button-small button-primary">
														<?php esc_html_e('Zatwierdź', 'basemgmt'); ?>
													</a>
												<?php endif; ?>
												<button type="button" class="button button-small bm-modal-open"
													data-modal="bm-modal-add-camp-decl-att"
													data-decl-id="<?php echo esc_attr($ddoc->id); ?>"
													data-decl-title="<?php echo esc_attr($ddoc->title); ?>">
													<?php esc_html_e('+ Załącznik', 'basemgmt'); ?>
												</button>
												<?php if ( $ddoc->status === 'approved' && ! $sent_to_camp ) : ?>
													<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_send_decl_to_camp&id={$id}&decl_id={$ddoc->id}"), "bm_send_decl_to_camp_{$ddoc->id}")); ?>"
														class="button button-small button-primary"
														data-bm-confirm="<?php esc_attr_e('Wysłać deklarację do obozu?', 'basemgmt'); ?>">
														<?php esc_html_e('Prześlij do obozu', 'basemgmt'); ?>
													</a>
												<?php endif; ?>
												<?php if ( $ddoc->status === 'approved' ) : ?>
													<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_send_camp_decl_doc&id={$id}&decl_id={$ddoc->id}"), "bm_send_camp_decl_doc_{$ddoc->id}")); ?>"
														class="button button-small"
														data-bm-confirm="<?php esc_attr_e('Wysłać deklarację do podpisu?', 'basemgmt'); ?>">
														<?php esc_html_e('Wyślij do podpisu', 'basemgmt'); ?>
													</a>
												<?php endif; ?>
												<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_camp_decl_doc&id={$id}&decl_doc_id={$ddoc->id}"), "bm_delete_camp_decl_doc_{$ddoc->id}")); ?>"
													class="button button-small bm-danger"
													data-bm-confirm="<?php esc_attr_e('Usunąć deklarację?', 'basemgmt'); ?>">
													<?php esc_html_e('Usuń', 'basemgmt'); ?>
												</a>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<!-- Modal: Dodaj załącznik do dokumentu w teczce -->
			<div id="bm-modal-add-camp-doc-att" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Dodaj załącznik do dokumentu', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_add_camp_doc_attachment'); ?>
						<input type="hidden" name="action"    value="bm_add_camp_doc_attachment">
						<input type="hidden" name="camp_id"   value="<?php echo esc_attr($id); ?>">
						<input type="hidden" name="doc_id"    id="bm-camp-doc-att-doc-id"   value="">
						<input type="hidden" name="file_id"   id="bm-camp-doc-att-file-id"   value="">
						<input type="hidden" name="file_url"  id="bm-camp-doc-att-file-url"  value="">
						<input type="hidden" name="file_name" id="bm-camp-doc-att-file-name" value="">
						<div class="bm-modal-body">
							<p id="bm-camp-doc-att-title" style="font-weight:600;"></p>
							<p>
								<span id="bm-camp-doc-att-display" class="bm-muted"><?php esc_html_e('Brak wybranego pliku', 'basemgmt'); ?></span><br>
								<button type="button" class="button" id="bm-camp-doc-att-select" style="margin-top:6px;"><?php esc_html_e('Wybierz plik', 'basemgmt'); ?></button>
							</p>
						</div>
						<div class="bm-modal-footer">
							<button type="submit" class="button button-primary" id="bm-camp-doc-att-submit" disabled><?php esc_html_e('Dodaj załącznik', 'basemgmt'); ?></button>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>

			<!-- Modal: Dodaj załącznik do deklaracji w teczce -->
			<div id="bm-modal-add-camp-decl-att" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Dodaj załącznik do deklaracji', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_add_camp_decl_attachment'); ?>
						<input type="hidden" name="action"    value="bm_add_camp_decl_attachment">
						<input type="hidden" name="camp_id"   value="<?php echo esc_attr($id); ?>">
						<input type="hidden" name="decl_id"   id="bm-camp-decl-att-decl-id"  value="">
						<input type="hidden" name="file_id"   id="bm-camp-decl-att-file-id"   value="">
						<input type="hidden" name="file_url"  id="bm-camp-decl-att-file-url"  value="">
						<input type="hidden" name="file_name" id="bm-camp-decl-att-file-name" value="">
						<div class="bm-modal-body">
							<p id="bm-camp-decl-att-title" style="font-weight:600;"></p>
							<p>
								<span id="bm-camp-decl-att-display" class="bm-muted"><?php esc_html_e('Brak wybranego pliku', 'basemgmt'); ?></span><br>
								<button type="button" class="button" id="bm-camp-decl-att-select" style="margin-top:6px;"><?php esc_html_e('Wybierz plik', 'basemgmt'); ?></button>
							</p>
						</div>
						<div class="bm-modal-footer">
							<button type="submit" class="button button-primary" id="bm-camp-decl-att-submit" disabled><?php esc_html_e('Dodaj załącznik', 'basemgmt'); ?></button>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>

			<!-- Modal: Prześlij podpisany dokument -->
			<div id="bm-modal-sign-doc" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Prześlij podpisany dokument', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field('bm_sign_camp_doc'); ?>
						<input type="hidden" name="action" value="bm_sign_camp_doc">
						<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
						<input type="hidden" name="doc_id" id="bm-sign-doc-id" value="">
						<div class="bm-modal-body">
							<p id="bm-sign-doc-title" style="font-weight:600;margin-bottom:12px;"></p>
							<p>
								<label><strong><?php esc_html_e('Metoda podpisu:', 'basemgmt'); ?></strong></label><br>
								<label style="display:block;margin:4px 0;">
									<input type="radio" name="sign_method" value="qualified" checked>
									<?php esc_html_e('Podpis kwalifikowany (PDF z podpisem cyfrowym)', 'basemgmt'); ?>
								</label>
								<label style="display:block;margin:4px 0;">
									<input type="radio" name="sign_method" value="scan">
									<?php esc_html_e('Skan podpisanego dokumentu (PDF, JPG, PNG)', 'basemgmt'); ?>
								</label>
							</p>
							<p>
								<label><strong><?php esc_html_e('Plik:', 'basemgmt'); ?></strong></label><br>
								<input type="file" name="signed_file" accept=".pdf,.jpg,.jpeg,.png" required>
							</p>
							<p class="bm-muted" style="font-size:12px;">
								<?php esc_html_e('Podpis kwalifikowany: wymagany plik PDF z osadzonym podpisem cyfrowym. System wykryje podpis automatycznie.', 'basemgmt'); ?>
							</p>
						</div>
						<div class="bm-modal-footer">
							<button type="submit" class="button button-primary"><?php esc_html_e('Prześlij', 'basemgmt'); ?></button>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>

			<!-- Modal: Dodaj deklarację z biblioteki -->
			<div id="bm-modal-add-decl" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Dodaj deklarację z biblioteki', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_add_camp_decl_doc'); ?>
						<input type="hidden" name="action" value="bm_add_camp_decl_doc">
						<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
						<div class="bm-modal-body">
							<?php if ( empty($decl_tpl_options) ) : ?>
								<p><?php esc_html_e('Brak deklaracji w bibliotece. Dodaj najpierw deklaracje w Organizacja → Deklaracje.', 'basemgmt'); ?></p>
							<?php else : ?>
								<label><strong><?php esc_html_e('Wybierz deklarację:', 'basemgmt'); ?></strong></label>
								<select name="decl_template_id" class="widefat" required>
									<option value=""><?php esc_html_e('— Wybierz —', 'basemgmt'); ?></option>
									<?php foreach ( $decl_tpl_options as $dtpl ) : ?>
										<option value="<?php echo esc_attr($dtpl->id); ?>"><?php echo esc_html($dtpl->title); ?></option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>
						</div>
						<div class="bm-modal-footer">
							<?php if ( ! empty($decl_tpl_options) ) : ?>
								<button type="submit" class="button button-primary"><?php esc_html_e('Dodaj', 'basemgmt'); ?></button>
							<?php endif; ?>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>

			<!-- Modal: Dodaj własną deklarację -->
			<div id="bm-modal-add-decl-custom" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Dodaj własną deklarację', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_add_camp_decl_custom'); ?>
						<input type="hidden" name="action"    value="bm_add_camp_decl_custom">
						<input type="hidden" name="camp_id"   value="<?php echo esc_attr($id); ?>">
						<input type="hidden" name="file_id"   id="bm-custom-decl-file-id"   value="">
						<input type="hidden" name="file_url"  id="bm-custom-decl-file-url"  value="">
						<input type="hidden" name="file_name" id="bm-custom-decl-file-name" value="">
						<div class="bm-modal-body" style="display:flex;flex-direction:column;gap:12px;">
							<div>
								<label for="bm-custom-decl-title"><strong><?php esc_html_e('Tytuł deklaracji', 'basemgmt'); ?></strong></label><br>
								<input type="text" id="bm-custom-decl-title" name="title" class="widefat" required placeholder="<?php esc_attr_e('np. Zgoda na przetwarzanie danych', 'basemgmt'); ?>">
							</div>
							<div>
								<label><strong><?php esc_html_e('Plik (opcjonalnie)', 'basemgmt'); ?></strong></label><br>
								<span id="bm-custom-decl-file-display" class="bm-muted"><?php esc_html_e('Brak wybranego pliku', 'basemgmt'); ?></span>
								<button type="button" class="button" id="bm-custom-decl-select" style="margin-left:8px;"><?php esc_html_e('Wybierz plik', 'basemgmt'); ?></button>
								<p class="description" style="margin-top:4px;"><?php esc_html_e('Możesz też pozostawić puste i wpisać treść HTML po dodaniu.', 'basemgmt'); ?></p>
							</div>
						</div>
						<div class="bm-modal-footer">
							<button type="submit" class="button button-primary"><?php esc_html_e('Dodaj deklarację', 'basemgmt'); ?></button>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>

			<!-- Modal: Dodaj własny dokument -->
			<div id="bm-modal-custom-doc" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Dodaj własny dokument', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_add_camp_doc_custom'); ?>
						<input type="hidden" name="action"    value="bm_add_camp_doc_custom">
						<input type="hidden" name="camp_id"   value="<?php echo esc_attr($id); ?>">
						<input type="hidden" name="file_id"   id="bm-custom-doc-file-id"   value="">
						<input type="hidden" name="file_url"  id="bm-custom-doc-file-url"  value="">
						<input type="hidden" name="file_name" id="bm-custom-doc-file-name" value="">
						<div class="bm-modal-body" style="display:flex;flex-direction:column;gap:12px;">
							<div>
								<label for="bm-custom-doc-title"><strong><?php esc_html_e('Tytuł dokumentu', 'basemgmt'); ?></strong></label><br>
								<input type="text" id="bm-custom-doc-title" name="title" class="widefat" required placeholder="<?php esc_attr_e('np. Umowa najmu', 'basemgmt'); ?>">
							</div>
							<div>
								<label for="bm-custom-doc-type"><strong><?php esc_html_e('Typ dokumentu', 'basemgmt'); ?></strong></label><br>
								<select id="bm-custom-doc-type" name="document_type" class="widefat">
									<?php foreach ( $doc_types_map as $key => $label ) : ?>
										<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div>
								<label><strong><?php esc_html_e('Plik (opcjonalnie)', 'basemgmt'); ?></strong></label><br>
								<span id="bm-custom-doc-file-display" class="bm-muted"><?php esc_html_e('Brak wybranego pliku', 'basemgmt'); ?></span>
								<button type="button" class="button" id="bm-custom-doc-select" style="margin-left:8px;"><?php esc_html_e('Wybierz plik', 'basemgmt'); ?></button>
								<p class="description" style="margin-top:4px;"><?php esc_html_e('Możesz też pozostawić puste i edytować treść HTML po dodaniu.', 'basemgmt'); ?></p>
							</div>
						</div>
						<div class="bm-modal-footer">
							<button type="submit" class="button button-primary"><?php esc_html_e('Dodaj dokument', 'basemgmt'); ?></button>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>

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

		<!-- ── SPRZĘT ───────────────────────────────────────────────────────── -->
		<div class="bm-tab-panel" data-tab="equipment" id="bm-section-equipment">
			<div class="bm-workcenter-header" style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
				<h2 style="margin:0;"><?php esc_html_e('Sprzęt obozowy', 'basemgmt'); ?></h2>
				<button type="button" class="button button-primary bm-modal-open" data-modal="bm-modal-add-equipment">
					<?php esc_html_e('+ Dodaj sprzęt', 'basemgmt'); ?>
				</button>
			</div>

			<?php if ( empty($camp_equipment) ) : ?>
				<div class="bm-empty-state">
					<span class="dashicons dashicons-tools" style="font-size:40px;color:#c3c4c7;"></span>
					<p><?php esc_html_e('Brak wydanego sprzętu. Dodaj pierwszy wpis.', 'basemgmt'); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped bm-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Typ', 'basemgmt'); ?></th>
							<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
							<th style="width:90px;"><?php esc_html_e('Wydano', 'basemgmt'); ?></th>
							<th style="width:90px;"><?php esc_html_e('Zwrócono', 'basemgmt'); ?></th>
							<th style="width:90px;"><?php esc_html_e('Do zwrotu', 'basemgmt'); ?></th>
							<th style="width:100px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
							<th><?php esc_html_e('Notatki', 'basemgmt'); ?></th>
							<th style="width:180px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $camp_equipment as $eq ) : ?>
							<?php
							$issued   = (int) $eq->issued_qty;
							$returned = (int) $eq->returned_qty;
							$pending  = $issued - $returned;
							$status   = $pending <= 0 ? 'returned' : ($returned > 0 ? 'partial' : 'issued');
							$status_labels = ['issued' => ['label' => __('Wydany', 'basemgmt'), 'class' => 'bm-badge--urgent'], 'partial' => ['label' => __('Częściowy zwrot', 'basemgmt'), 'class' => 'bm-badge--high'], 'returned' => ['label' => __('Zwrócony', 'basemgmt'), 'class' => 'bm-badge--success']];
							$sl = $status_labels[$status];
							?>
							<tr>
								<td class="bm-muted"><?php echo esc_html($eq->equipment_type ?: '—'); ?></td>
								<td><strong><?php echo esc_html($eq->name); ?></strong></td>
								<td><?php echo esc_html($issued); ?></td>
								<td><?php echo esc_html($returned); ?></td>
								<td><?php echo esc_html($pending); ?></td>
								<td><span class="bm-badge <?php echo esc_attr($sl['class']); ?>"><?php echo esc_html($sl['label']); ?></span></td>
								<td class="bm-muted"><?php echo esc_html($eq->notes ?: '—'); ?></td>
								<td>
									<?php if ( $pending > 0 ) : ?>
										<button type="button" class="button button-small bm-modal-open"
											data-modal="bm-modal-return-equipment"
											data-equip-id="<?php echo esc_attr($eq->id); ?>"
											data-equip-name="<?php echo esc_attr($eq->name); ?>"
											data-pending="<?php echo esc_attr($pending); ?>">
											<?php esc_html_e('Zwrot', 'basemgmt'); ?>
										</button>
									<?php endif; ?>
									<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_camp_equipment&id={$id}&equip_id={$eq->id}"), "bm_delete_equipment_{$eq->id}")); ?>"
										class="button button-small bm-danger"
										data-bm-confirm="<?php esc_attr_e('Usunąć wpis sprzętu?', 'basemgmt'); ?>">
										<?php esc_html_e('Usuń', 'basemgmt'); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<!-- Modal: Dodaj sprzęt -->
			<div id="bm-modal-add-equipment" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Dodaj wydany sprzęt', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_add_camp_equipment'); ?>
						<input type="hidden" name="action" value="bm_add_camp_equipment">
						<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
						<div class="bm-modal-body">
							<p>
								<label><strong><?php esc_html_e('Typ sprzętu', 'basemgmt'); ?></strong></label><br>
								<input type="text" name="equipment_type" class="widefat" placeholder="<?php esc_attr_e('np. Namioty, Sprzęt kuchenny, Sprzęt sportowy…', 'basemgmt'); ?>">
							</p>
							<p>
								<label><strong><?php esc_html_e('Nazwa', 'basemgmt'); ?></strong> <span style="color:red;">*</span></label><br>
								<input type="text" name="equipment_name" class="widefat" required placeholder="<?php esc_attr_e('np. Namiot 6-osobowy, Kajak…', 'basemgmt'); ?>">
							</p>
							<p>
								<label><strong><?php esc_html_e('Ilość wydana', 'basemgmt'); ?></strong></label><br>
								<input type="number" name="issued_qty" class="small-text" min="1" value="1" required>
							</p>
							<p>
								<label><strong><?php esc_html_e('Notatki', 'basemgmt'); ?></strong></label><br>
								<textarea name="equipment_notes" class="widefat" rows="2"></textarea>
							</p>
						</div>
						<div class="bm-modal-footer">
							<button type="submit" class="button button-primary"><?php esc_html_e('Dodaj sprzęt', 'basemgmt'); ?></button>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>

			<!-- Modal: Zwrot sprzętu (POST form, no nonce-in-URL issue) -->
			<div id="bm-modal-return-equipment" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Zwrot sprzętu', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_return_camp_equipment'); ?>
						<input type="hidden" name="action" value="bm_return_camp_equipment">
						<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
						<input type="hidden" name="equip_id" id="bm-return-equip-id-input" value="">
						<div class="bm-modal-body">
							<p id="bm-return-equip-desc"></p>
							<div id="bm-return-normal-fields">
								<label><strong><?php esc_html_e('Ilość zwracana', 'basemgmt'); ?></strong></label><br>
								<input type="number" name="qty" id="bm-return-qty-input" min="1" value="1" class="small-text">
							</div>
							<hr style="margin:14px 0;">
							<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
								<input type="checkbox" name="is_loss" id="bm-return-is-loss" value="1"
									onchange="document.getElementById('bm-return-loss-fields').style.display=this.checked?'block':'none';document.getElementById('bm-return-normal-fields').style.opacity=this.checked?'.4':'1';">
								<strong><?php esc_html_e('Nalicz stratę finansową (sprzęt nie zwrócony / uszkodzony)', 'basemgmt'); ?></strong>
							</label>
							<div id="bm-return-loss-fields" style="display:none;margin-top:12px;padding:12px;background:#fff3cd;border-radius:4px;">
								<p style="margin:0 0 8px;color:#856404;font-size:12px;"><?php esc_html_e('Cały niezwrócony sprzęt zostanie zapisany jako zwrócony, a strata zostanie dodana do finansów obozu.', 'basemgmt'); ?></p>
								<label><strong><?php esc_html_e('Kwota straty (PLN)', 'basemgmt'); ?></strong></label><br>
								<input type="text" name="loss_amount" class="small-text" placeholder="0.00" style="margin-bottom:8px;">
								<br>
								<label><strong><?php esc_html_e('Opis straty', 'basemgmt'); ?></strong></label><br>
								<input type="text" name="loss_description" class="widefat" placeholder="<?php esc_attr_e('Opcjonalnie — zostanie uzupełniony automatycznie', 'basemgmt'); ?>">
							</div>
						</div>
						<div class="bm-modal-footer">
							<button type="submit" class="button button-primary"><?php esc_html_e('Zarejestruj zwrot', 'basemgmt'); ?></button>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div><!-- /equipment -->

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
												<th style="width:110px;"><?php esc_html_e('Zniżka', 'basemgmt'); ?></th>
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
													<td style="white-space:nowrap;"><input type="number" name="sched_amount[]" class="bm-sched-amount" style="width:72px;" step="0.01" value="<?php echo esc_attr($sched->amount); ?>"><input type="hidden" name="sched_amount_type[]" value="fixed"></td>
													<td style="white-space:nowrap;"><input type="number" name="sched_discount[]" class="bm-sched-discount" style="width:60px;" step="0.01" min="0" value="<?php echo esc_attr($sched->discount ?? '0.00'); ?>"><select name="sched_discount_type[]" class="bm-discount-type" style="width:44px;" title="<?php esc_attr_e('Typ zniżki', 'basemgmt'); ?>"><option value="fixed" <?php selected($sched->discount_type ?? 'fixed', 'fixed'); ?>>PLN</option><option value="percent" <?php selected($sched->discount_type ?? 'fixed', 'percent'); ?>>%</option></select></td>
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
								<div id="bm-global-discount-wrap" style="margin-top:10px;padding:10px 14px;background:#fff8e1;border:1px solid #f0c040;border-radius:4px;">
						<label style="font-weight:600;font-size:13px;"><?php esc_html_e('Zniżka globalna:', 'basemgmt'); ?></label>
						<span style="float:right;display:flex;gap:4px;align-items:center;">
							<input type="number" name="global_discount" id="bm-global-discount" step="0.01" min="0" style="width:90px;" value="<?php echo esc_attr(get_post_meta($id, '_bm_finance_global_discount', true) ?: '0'); ?>">
							<select name="global_discount_type" id="bm-global-discount-type" style="width:54px;">
								<option value="fixed" <?php selected(get_post_meta($id, '_bm_finance_global_discount_type', true) ?: 'fixed', 'fixed'); ?>>PLN</option>
								<option value="percent" <?php selected(get_post_meta($id, '_bm_finance_global_discount_type', true) ?: 'fixed', 'percent'); ?>>%</option>
							</select>
						</span>
						<div style="clear:both;"></div>
						<p class="description" style="margin-top:4px;font-size:11px;"><?php esc_html_e('Zniżka od sumy wszystkich pozycji (po odjęciu zniżek per pozycja).', 'basemgmt'); ?></p>
					</div>
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
								<th style="width:90px;"></th>
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
										<button type="button" class="button-link bm-modal-open"
											data-modal="bm-modal-edit-damage"
											data-damage-id="<?php echo esc_attr($dmg->id); ?>"
											data-name="<?php echo esc_attr($dmg->name); ?>"
											data-description="<?php echo esc_attr($dmg->description); ?>"
											data-cost="<?php echo esc_attr($dmg->cost); ?>"
											data-status="<?php echo esc_attr($dmg->status); ?>">✎</button>
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

			<!-- Modal: Edytuj szkodę -->
			<div id="bm-modal-edit-damage" style="display:none;" class="bm-modal-overlay">
				<div class="bm-modal">
					<div class="bm-modal-header">
						<h3><?php esc_html_e('Edytuj szkodę', 'basemgmt'); ?></h3>
						<button type="button" class="bm-modal-close">✕</button>
					</div>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('bm_edit_camp_damage'); ?>
						<input type="hidden" name="action" value="bm_edit_camp_damage">
						<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">
						<input type="hidden" name="damage_id" id="bm-edit-damage-id" value="">
						<div class="bm-modal-body">
							<div class="bm-form-grid">
								<p>
									<label><strong><?php esc_html_e('Nazwa szkody', 'basemgmt'); ?></strong></label><br>
									<input type="text" name="damage_name" id="bm-edit-damage-name" class="widefat" required>
								</p>
								<p>
									<label><strong><?php esc_html_e('Koszt (PLN)', 'basemgmt'); ?></strong></label><br>
									<input type="text" name="damage_cost" id="bm-edit-damage-cost" class="widefat">
								</p>
								<p>
									<label><strong><?php esc_html_e('Status', 'basemgmt'); ?></strong></label><br>
									<select name="damage_status" id="bm-edit-damage-status" class="widefat">
										<option value="reported"><?php esc_html_e('Zgłoszona', 'basemgmt'); ?></option>
										<option value="investigating"><?php esc_html_e('W ocenie', 'basemgmt'); ?></option>
										<option value="settled"><?php esc_html_e('Rozliczona', 'basemgmt'); ?></option>
										<option value="dismissed"><?php esc_html_e('Odrzucona', 'basemgmt'); ?></option>
									</select>
								</p>
							</div>
							<p>
								<label><strong><?php esc_html_e('Opis', 'basemgmt'); ?></strong></label><br>
								<textarea name="damage_description" id="bm-edit-damage-desc" class="widefat" rows="3"></textarea>
							</p>
						</div>
						<div class="bm-modal-footer">
							<button type="submit" class="button button-primary"><?php esc_html_e('Zapisz zmiany', 'basemgmt'); ?></button>
							<button type="button" class="button bm-modal-close"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
						</div>
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
	var customDocBtn = document.getElementById('bm-add-doc-custom');
	var libBtn = document.getElementById('bm-add-doc-from-library');
	var tplBtn = document.getElementById('bm-add-doc-from-template');
	if (customDocBtn) customDocBtn.addEventListener('click', function() { openModal('bm-modal-custom-doc'); });
	if (libBtn) libBtn.addEventListener('click', function() { openModal('bm-modal-library'); });
	if (tplBtn) tplBtn.addEventListener('click', function() { openModal('bm-modal-template'); });

	// Custom doc file picker
	var customDocSelect = document.getElementById('bm-custom-doc-select');
	if (customDocSelect) {
		customDocSelect.addEventListener('click', function() {
			var frame = wp.media({ title: '<?php esc_html_e('Wybierz dokument', 'basemgmt'); ?>', multiple: false });
			frame.on('select', function() {
				var att = frame.state().get('selection').first().toJSON();
				document.getElementById('bm-custom-doc-file-id').value   = att.id;
				document.getElementById('bm-custom-doc-file-url').value  = att.url;
				document.getElementById('bm-custom-doc-file-name').value = att.filename;
				var disp = document.getElementById('bm-custom-doc-file-display');
				if (disp) { disp.textContent = att.filename; disp.classList.remove('bm-muted'); }
			});
			frame.open();
		});
	}
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
	var AMT_OPTS = '<option value="fixed">PLN</option>';

	var DISC_OPTS = '<option value="fixed">PLN</option><option value="percent">%</option>';function bmFmt(n) { return n.toFixed(2).replace('.', ',') + ' zł'; }

	function bmRecalcFinance() {
		var rows = document.querySelectorAll('#bm-payment-tbody tr');
		var subtotal = 0, rowDiscounts = 0, totalPaid = 0;
		rows.forEach(function(row) {
			var amtEl      = row.querySelector('[name="sched_amount[]"]');
			var discEl     = row.querySelector('[name="sched_discount[]"]');
			var discTypeEl = row.querySelector('[name="sched_discount_type[]"]');
			var statusEl   = row.querySelector('[name="sched_status[]"]');
			if (!amtEl) return;
			var amount = parseFloat(amtEl.value) || 0, status = statusEl ? statusEl.value : '';
			if (status === 'cancelled') return;
			subtotal += amount;
			var disc = parseFloat(discEl ? discEl.value : 0) || 0;
			var discType = discTypeEl ? discTypeEl.value : 'fixed';
			rowDiscounts += (discType === 'percent') ? amount * disc / 100 : disc;
			if (status === 'paid') totalPaid += amount;
		});
		var gDiscEl     = document.getElementById('bm-global-discount');
		var gDiscTypeEl = document.getElementById('bm-global-discount-type');
		var gDisc     = parseFloat(gDiscEl ? gDiscEl.value : 0) || 0;
		var gDiscType = gDiscTypeEl ? gDiscTypeEl.value : 'fixed';
		var afterRowDisc = subtotal - rowDiscounts;
		var globalDisc   = (gDiscType === 'percent') ? afterRowDisc * gDisc / 100 : gDisc;
		var totalDiscount = rowDiscounts + globalDisc;
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
		if (['sched_amount[]','sched_discount[]','global_discount'].indexOf(e.target.name) >= 0) bmRecalcFinance();
	});
	document.addEventListener('change', function(e) {
		if (['sched_discount_type[]','global_discount_type'].indexOf(e.target.name) >= 0) bmRecalcFinance();
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
				tbl.innerHTML = '<thead><tr><th><?php esc_js(esc_html_e('Nazwa','basemgmt')); ?></th><th style="width:120px;"><?php esc_js(esc_html_e('Typ','basemgmt')); ?></th><th style="width:100px;"><?php esc_js(esc_html_e('Kwota','basemgmt')); ?></th><th style="width:110px;"><?php esc_js(esc_html_e('Zniżka','basemgmt')); ?></th><th style="width:120px;"><?php esc_js(esc_html_e('Termin','basemgmt')); ?></th><th style="width:90px;"><?php esc_js(esc_html_e('Status','basemgmt')); ?></th><th style="width:40px;"></th></tr></thead><tbody id="bm-payment-tbody"></tbody>';
				wrap.insertBefore(tbl, addPayBtn.parentElement);
				tbody = document.getElementById('bm-payment-tbody');
			}
			var tr = document.createElement('tr');
			tr.innerHTML = '<td><input type="hidden" name="sched_id[]" value="0"><input type="text" name="sched_label[]" class="widefat" placeholder="<?php esc_js(esc_attr_e('Nazwa','basemgmt')); ?>"></td>'
				+ '<td><select name="sched_type[]" class="widefat bm-sched-type">' + TYPE_OPTS + '</select></td>'
				+ '<td style="white-space:nowrap;"><input type="number" name="sched_amount[]" class="bm-sched-amount" style="width:72px;" step="0.01" value="0.00"><input type="hidden" name="sched_amount_type[]" value="fixed"></td>'
				+ '<td style="white-space:nowrap;"><input type="number" name="sched_discount[]" class="bm-sched-discount" style="width:60px;" step="0.01" min="0" value="0.00"><select name="sched_discount_type[]" class="bm-discount-type" style="width:44px;">' + DISC_OPTS + '</select></td>'
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

	// ── Generic: open modals by data-modal attribute ──────────────────────
	document.querySelectorAll('.bm-modal-open').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var modalId = btn.getAttribute('data-modal');
			if (modalId) openModal(modalId);
		});
	});

	// ── Return equipment modal (POST form — just populate hidden equip_id) ──
	document.querySelectorAll('[data-modal="bm-modal-return-equipment"]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var equipId   = btn.getAttribute('data-equip-id');
			var equipName = btn.getAttribute('data-equip-name');
			var pending   = parseInt(btn.getAttribute('data-pending'), 10) || 1;
			var desc   = document.getElementById('bm-return-equip-desc');
			var qtyInp = document.getElementById('bm-return-qty-input');
			var idInp  = document.getElementById('bm-return-equip-id-input');
			if (desc)   desc.textContent = equipName + ' (<?php esc_js(esc_html_e('do zwrotu:', 'basemgmt')); ?> ' + pending + ')';
			if (qtyInp) { qtyInp.max = pending; qtyInp.value = pending; }
			if (idInp)  idInp.value = equipId;
		});
	});

	// ── Damage edit modal ─────────────────────────────────────────────────
	document.querySelectorAll('[data-modal="bm-modal-edit-damage"]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			document.getElementById('bm-edit-damage-id').value      = btn.getAttribute('data-damage-id');
			document.getElementById('bm-edit-damage-name').value    = btn.getAttribute('data-name');
			document.getElementById('bm-edit-damage-cost').value    = btn.getAttribute('data-cost');
			document.getElementById('bm-edit-damage-desc').value    = btn.getAttribute('data-description');
			var statusSel = document.getElementById('bm-edit-damage-status');
			if (statusSel) statusSel.value = btn.getAttribute('data-status');
		});
	});

	// ── Sign document modal ───────────────────────────────────────────────
	document.querySelectorAll('[data-modal="bm-modal-sign-doc"]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var idInp   = document.getElementById('bm-sign-doc-id');
			var titleEl = document.getElementById('bm-sign-doc-title');
			if (idInp)   idInp.value = btn.getAttribute('data-doc-id');
			if (titleEl) titleEl.textContent = btn.getAttribute('data-doc-title');
		});
	});

	// ── Approve declaration modal ─────────────────────────────────────────
	document.querySelectorAll('[data-modal="bm-modal-approve-decl"]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var idInp   = document.getElementById('bm-approve-decl-id');
			var titleEl = document.getElementById('bm-approve-decl-title');
			if (idInp)   idInp.value = btn.getAttribute('data-decl-id');
			if (titleEl) titleEl.textContent = btn.getAttribute('data-decl-title');
		});
	});

	// Custom decl file picker
	var customDeclSelect = document.getElementById('bm-custom-decl-select');
	if (customDeclSelect) {
		customDeclSelect.addEventListener('click', function() {
			var frame = wp.media({ title: '<?php esc_html_e('Wybierz plik deklaracji', 'basemgmt'); ?>', multiple: false });
			frame.on('select', function() {
				var att = frame.state().get('selection').first().toJSON();
				document.getElementById('bm-custom-decl-file-id').value   = att.id;
				document.getElementById('bm-custom-decl-file-url').value  = att.url;
				document.getElementById('bm-custom-decl-file-name').value = att.filename;
				var disp = document.getElementById('bm-custom-decl-file-display');
				if (disp) { disp.textContent = att.filename; disp.classList.remove('bm-muted'); }
			});
			frame.open();
		});
	}

	// ── Camp doc attachment modal ─────────────────────────────────────────
	document.querySelectorAll('[data-modal="bm-modal-add-camp-doc-att"]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var el = document.getElementById('bm-camp-doc-att-doc-id');
			var ti = document.getElementById('bm-camp-doc-att-title');
			if (el) el.value = btn.getAttribute('data-doc-id');
			if (ti) ti.textContent = btn.getAttribute('data-doc-title');
			['file-id','file-url','file-name'].forEach(function(f) {
				var inp = document.getElementById('bm-camp-doc-att-' + f);
				if (inp) inp.value = '';
			});
			var disp = document.getElementById('bm-camp-doc-att-display');
			if (disp) { disp.textContent = '<?php esc_html_e('Brak wybranego pliku', 'basemgmt'); ?>'; disp.classList.add('bm-muted'); }
			var sub = document.getElementById('bm-camp-doc-att-submit');
			if (sub) sub.disabled = true;
		});
	});
	var bmCampDocAttBtn = document.getElementById('bm-camp-doc-att-select');
	if (bmCampDocAttBtn) {
		bmCampDocAttBtn.addEventListener('click', function() {
			var frame = wp.media({ title: '<?php esc_html_e('Wybierz załącznik', 'basemgmt'); ?>', multiple: false });
			frame.on('select', function() {
				var att = frame.state().get('selection').first().toJSON();
				document.getElementById('bm-camp-doc-att-file-id').value   = att.id;
				document.getElementById('bm-camp-doc-att-file-url').value  = att.url;
				document.getElementById('bm-camp-doc-att-file-name').value = att.filename;
				var disp = document.getElementById('bm-camp-doc-att-display');
				if (disp) { disp.textContent = att.filename; disp.classList.remove('bm-muted'); }
				var sub = document.getElementById('bm-camp-doc-att-submit');
				if (sub) sub.disabled = false;
			});
			frame.open();
		});
	}

	// ── Camp decl attachment modal ────────────────────────────────────────
	document.querySelectorAll('[data-modal="bm-modal-add-camp-decl-att"]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var el = document.getElementById('bm-camp-decl-att-decl-id');
			var ti = document.getElementById('bm-camp-decl-att-title');
			if (el) el.value = btn.getAttribute('data-decl-id');
			if (ti) ti.textContent = btn.getAttribute('data-decl-title');
			['file-id','file-url','file-name'].forEach(function(f) {
				var inp = document.getElementById('bm-camp-decl-att-' + f);
				if (inp) inp.value = '';
			});
			var disp = document.getElementById('bm-camp-decl-att-display');
			if (disp) { disp.textContent = '<?php esc_html_e('Brak wybranego pliku', 'basemgmt'); ?>'; disp.classList.add('bm-muted'); }
			var sub = document.getElementById('bm-camp-decl-att-submit');
			if (sub) sub.disabled = true;
		});
	});
	var bmCampDeclAttBtn = document.getElementById('bm-camp-decl-att-select');
	if (bmCampDeclAttBtn) {
		bmCampDeclAttBtn.addEventListener('click', function() {
			var frame = wp.media({ title: '<?php esc_html_e('Wybierz załącznik', 'basemgmt'); ?>', multiple: false });
			frame.on('select', function() {
				var att = frame.state().get('selection').first().toJSON();
				document.getElementById('bm-camp-decl-att-file-id').value   = att.id;
				document.getElementById('bm-camp-decl-att-file-url').value  = att.url;
				document.getElementById('bm-camp-decl-att-file-name').value = att.filename;
				var disp = document.getElementById('bm-camp-decl-att-display');
				if (disp) { disp.textContent = att.filename; disp.classList.remove('bm-muted'); }
				var sub = document.getElementById('bm-camp-decl-att-submit');
				if (sub) sub.disabled = false;
			});
			frame.open();
		});
	}

	// ── Auto-open print window after decl finalize ────────────────────────
	(function() {
		var params = new URLSearchParams(window.location.search);
		var printUrl = params.get('bm_open_print');
		if (printUrl) {
			window.open(decodeURIComponent(printUrl), '_blank');
		}
	})();
})();
</script>
<?php endif; ?>



