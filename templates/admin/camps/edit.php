<?php defined('ABSPATH') || exit;
$is_edit            = ! is_null($camp);
$id                 = $is_edit ? (int) $camp->id : 0;
$process_stage      = $case->process_stage ?? \BaseMgmt\Modules\Camps\CampCaseRepository::STAGE_INQUIRY;
$risk_level         = $case->risk_level ?? \BaseMgmt\Modules\Camps\CampCaseRepository::RISK_LOW;
$owner_user_id      = (int) ($case->owner_user_id ?? 0);
$needs_attention    = ! empty($case->needs_attention);
$readiness_percent  = (int) ($readiness['percent'] ?? 0);
?>
<div class="wrap bm-admin-wrap">
	<h1>
		<?php echo $is_edit ? esc_html__('Teczka obozu', 'basemgmt') : esc_html__('Nowy obóz', 'basemgmt'); ?>
		<?php if ( $is_edit ) : ?>
			<span class="bm-muted">#<?php echo esc_html($id); ?></span>
		<?php endif; ?>
	</h1>

	<?php if ( $is_edit ) : ?>
		<div class="bm-case-grid bm-case-grid--metrics">
			<div class="bm-case-card">
				<span class="bm-stat-label"><?php esc_html_e('Etap procesu', 'basemgmt'); ?></span>
				<strong><?php echo esc_html($process_stages[$process_stage] ?? $process_stage); ?></strong>
			</div>
			<div class="bm-case-card">
				<span class="bm-stat-label"><?php esc_html_e('Gotowość', 'basemgmt'); ?></span>
				<strong><?php echo esc_html($readiness_percent); ?>%</strong>
			</div>
			<div class="bm-case-card">
				<span class="bm-stat-label"><?php esc_html_e('Ryzyko', 'basemgmt'); ?></span>
				<strong><?php echo esc_html($risk_levels[$risk_level] ?? $risk_level); ?></strong>
			</div>
			<div class="bm-case-card">
				<span class="bm-stat-label"><?php esc_html_e('Moduły końcowe', 'basemgmt'); ?></span>
				<strong><?php echo esc_html(array_sum($future_counts)); ?></strong>
			</div>
		</div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper" style="margin-bottom:20px;">
		<a href="#bm-tab-basic" class="nav-tab nav-tab-active"><?php esc_html_e('Proces', 'basemgmt'); ?></a>
		<a href="#bm-tab-organizer" class="nav-tab"><?php esc_html_e('Organizator', 'basemgmt'); ?></a>
		<a href="#bm-tab-checklist" class="nav-tab"><?php esc_html_e('Checklista', 'basemgmt'); ?></a>
		<a href="#bm-tab-prearrival" class="nav-tab"><?php esc_html_e('Dane operacyjne', 'basemgmt'); ?></a>
		<a href="#bm-tab-settlement" class="nav-tab"><?php esc_html_e('Rozliczenie i archiwum', 'basemgmt'); ?></a>
	</nav>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field('bm_save_camp'); ?>
		<input type="hidden" name="action" value="bm_save_camp">
		<input type="hidden" name="camp_id" value="<?php echo esc_attr($id); ?>">

		<div id="bm-tab-basic" class="bm-form-section">
			<h2><?php esc_html_e('Podstawowe dane i proces', 'basemgmt'); ?></h2>
			<div class="bm-form-grid">
				<p>
					<label for="bm_name"><strong><?php esc_html_e('Nazwa obozu', 'basemgmt'); ?></strong></label><br>
					<input type="text" id="bm_name" name="name" class="regular-text" required value="<?php echo esc_attr($camp->name ?? ''); ?>">
				</p>
				<p>
					<label for="bm_status"><strong><?php esc_html_e('Status pobytu', 'basemgmt'); ?></strong></label><br>
					<select id="bm_status" name="status">
						<?php foreach ( ['active' => __('Aktywny', 'basemgmt'), 'ended' => __('Zakończony', 'basemgmt'), 'archived' => __('Archiwalny', 'basemgmt')] as $value => $label ) : ?>
							<option value="<?php echo esc_attr($value); ?>" <?php selected($camp->status ?? 'active', $value); ?>>
								<?php echo esc_html($label); ?>
							</option>
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
				<p>
					<label for="bm_process_stage"><strong><?php esc_html_e('Etap procesu', 'basemgmt'); ?></strong></label><br>
					<select id="bm_process_stage" name="process_stage">
						<?php foreach ( $process_stages as $value => $label ) : ?>
							<option value="<?php echo esc_attr($value); ?>" <?php selected($process_stage, $value); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label for="bm_risk_level"><strong><?php esc_html_e('Poziom ryzyka', 'basemgmt'); ?></strong></label><br>
					<select id="bm_risk_level" name="risk_level">
						<?php foreach ( $risk_levels as $value => $label ) : ?>
							<option value="<?php echo esc_attr($value); ?>" <?php selected($risk_level, $value); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label for="bm_owner_user_id"><strong><?php esc_html_e('Osoba odpowiedzialna', 'basemgmt'); ?></strong></label><br>
					<select id="bm_owner_user_id" name="owner_user_id">
						<option value="0"><?php esc_html_e('— nie przypisano —', 'basemgmt'); ?></option>
						<?php foreach ( $users as $user ) : ?>
							<option value="<?php echo esc_attr($user->ID); ?>" <?php selected($owner_user_id, (int) $user->ID); ?>>
								<?php echo esc_html($user->display_name); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label for="bm_next_action"><strong><?php esc_html_e('Termin następnego działania', 'basemgmt'); ?></strong></label><br>
					<input type="date" id="bm_next_action" name="next_action_due_date" value="<?php echo esc_attr($case->next_action_due_date ?? ''); ?>">
				</p>
				<p class="bm-inline-check">
					<label>
						<input type="checkbox" name="needs_attention" value="1" <?php checked($needs_attention); ?>>
						<?php esc_html_e('Sprawa wymaga pilnej reakcji', 'basemgmt'); ?>
					</label>
				</p>
			</div>

			<p>
				<label for="bm_stage_change_note"><strong><?php esc_html_e('Uzasadnienie zmiany etapu', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_stage_change_note" name="stage_change_note" class="large-text" rows="2"></textarea>
			</p>
			<p>
				<label for="bm_case_notes"><strong><?php esc_html_e('Notatki procesowe', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_case_notes" name="case_notes" class="large-text" rows="4"><?php echo esc_textarea($case->notes ?? ''); ?></textarea>
			</p>
			<p>
				<label for="bm_readiness_notes"><strong><?php esc_html_e('Uwagi do gotowości', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_readiness_notes" name="readiness_notes" class="large-text" rows="3"><?php echo esc_textarea($case->readiness_notes ?? ''); ?></textarea>
			</p>
		</div>

		<div id="bm-tab-organizer" class="bm-form-section">
			<h2><?php esc_html_e('Organizator i rozliczenia', 'basemgmt'); ?></h2>
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
				<p>
					<label for="bm_billing_name"><strong><?php esc_html_e('Nazwa do faktury', 'basemgmt'); ?></strong></label><br>
					<input type="text" id="bm_billing_name" name="billing_name" class="regular-text" value="<?php echo esc_attr($organizer->billing_name ?? ''); ?>">
				</p>
				<p>
					<label for="bm_billing_tax_id"><strong><?php esc_html_e('NIP / identyfikator', 'basemgmt'); ?></strong></label><br>
					<input type="text" id="bm_billing_tax_id" name="billing_tax_id" class="regular-text" value="<?php echo esc_attr($organizer->billing_tax_id ?? ''); ?>">
				</p>
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
			<p>
				<label for="bm_billing_address"><strong><?php esc_html_e('Adres rozliczeniowy', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_billing_address" name="billing_address" class="large-text" rows="3"><?php echo esc_textarea($organizer->billing_address ?? ''); ?></textarea>
			</p>
			<p>
				<label for="bm_organizer_notes"><strong><?php esc_html_e('Uwagi do organizatora', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_organizer_notes" name="organizer_notes" class="large-text" rows="3"><?php echo esc_textarea($organizer->notes ?? ''); ?></textarea>
			</p>
		</div>

		<div id="bm-tab-checklist" class="bm-form-section">
			<h2><?php esc_html_e('Checklista gotowości', 'basemgmt'); ?></h2>
			<p class="description">
				<?php
				printf(
					esc_html__('Ukończono %1$d z %2$d zadań, %3$d po terminie.', 'basemgmt'),
					(int) ($readiness['done'] ?? 0),
					(int) ($readiness['total'] ?? 0),
					(int) ($readiness['overdue'] ?? 0)
				);
				?>
			</p>
			<table class="widefat striped bm-table">
				<thead>
					<tr>
						<th><?php esc_html_e('Zadanie', 'basemgmt'); ?></th>
						<th><?php esc_html_e('Strona', 'basemgmt'); ?></th>
						<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
						<th><?php esc_html_e('Odpowiedzialny', 'basemgmt'); ?></th>
						<th><?php esc_html_e('Termin', 'basemgmt'); ?></th>
						<th><?php esc_html_e('Komentarz', 'basemgmt'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $checklist as $index => $item ) : ?>
						<tr>
							<td><input type="text" name="checklist[label][]" value="<?php echo esc_attr($item['label']); ?>" class="widefat"></td>
							<td>
								<select name="checklist[party][]">
									<?php foreach ( $checklist_parties as $value => $label ) : ?>
										<option value="<?php echo esc_attr($value); ?>" <?php selected($item['party'], $value); ?>>
											<?php echo esc_html($label); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<select name="checklist[status][]">
									<?php foreach ( $checklist_statuses as $value => $label ) : ?>
										<option value="<?php echo esc_attr($value); ?>" <?php selected($item['status'], $value); ?>>
											<?php echo esc_html($label); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
							<td><input type="text" name="checklist[assigned_to][]" value="<?php echo esc_attr($item['assigned_to']); ?>" class="regular-text"></td>
							<td><input type="date" name="checklist[due_date][]" value="<?php echo esc_attr($item['due_date']); ?>"></td>
							<td><input type="text" name="checklist[comment][]" value="<?php echo esc_attr($item['comment']); ?>" class="widefat"></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div id="bm-tab-prearrival" class="bm-form-section">
			<h2><?php esc_html_e('Dane operacyjne przed przyjazdem', 'basemgmt'); ?></h2>
			<div class="bm-form-grid">
				<p>
					<label for="bm_arrival_date"><strong><?php esc_html_e('Dzień przyjazdu', 'basemgmt'); ?></strong></label><br>
					<input type="date" id="bm_arrival_date" name="arrival_date" value="<?php echo esc_attr($prearrival->arrival_date ?? ''); ?>">
				</p>
				<p>
					<label for="bm_arrival_time"><strong><?php esc_html_e('Godzina przyjazdu', 'basemgmt'); ?></strong></label><br>
					<input type="time" id="bm_arrival_time" name="arrival_time" value="<?php echo esc_attr($prearrival->arrival_time ?? ''); ?>">
				</p>
				<p>
					<label for="bm_departure_date"><strong><?php esc_html_e('Dzień wyjazdu', 'basemgmt'); ?></strong></label><br>
					<input type="date" id="bm_departure_date" name="departure_date" value="<?php echo esc_attr($prearrival->departure_date ?? ''); ?>">
				</p>
				<p>
					<label for="bm_departure_time"><strong><?php esc_html_e('Godzina wyjazdu', 'basemgmt'); ?></strong></label><br>
					<input type="time" id="bm_departure_time" name="departure_time" value="<?php echo esc_attr($prearrival->departure_time ?? ''); ?>">
				</p>
				<p>
					<label for="bm_declared_participants"><strong><?php esc_html_e('Deklarowani uczestnicy', 'basemgmt'); ?></strong></label><br>
					<input type="number" min="0" id="bm_declared_participants" name="declared_participants" value="<?php echo esc_attr((string) ($prearrival->declared_participants ?? '0')); ?>">
				</p>
				<p>
					<label for="bm_declared_staff"><strong><?php esc_html_e('Deklarowana kadra', 'basemgmt'); ?></strong></label><br>
					<input type="number" min="0" id="bm_declared_staff" name="declared_staff" value="<?php echo esc_attr((string) ($prearrival->declared_staff ?? '0')); ?>">
				</p>
				<p>
					<label for="bm_declared_support"><strong><?php esc_html_e('Deklarowana obsługa', 'basemgmt'); ?></strong></label><br>
					<input type="number" min="0" id="bm_declared_support" name="declared_support" value="<?php echo esc_attr((string) ($prearrival->declared_support ?? '0')); ?>">
				</p>
			</div>
			<p>
				<label for="bm_dietary_requirements"><strong><?php esc_html_e('Zapotrzebowanie żywieniowe i diety', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_dietary_requirements" name="dietary_requirements" class="large-text" rows="3"><?php echo esc_textarea($prearrival->dietary_requirements ?? ''); ?></textarea>
			</p>
			<p>
				<label for="bm_allergens"><strong><?php esc_html_e('Alergeny i potrzeby szczególne', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_allergens" name="allergens" class="large-text" rows="3"><?php echo esc_textarea($prearrival->allergens ?? ''); ?></textarea>
			</p>
			<p>
				<label for="bm_infrastructure_plan"><strong><?php esc_html_e('Plan korzystania z infrastruktury', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_infrastructure_plan" name="infrastructure_plan" class="large-text" rows="3"><?php echo esc_textarea($prearrival->infrastructure_plan ?? ''); ?></textarea>
			</p>
			<p>
				<label for="bm_additional_needs"><strong><?php esc_html_e('Potrzeby dodatkowe', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_additional_needs" name="additional_needs" class="large-text" rows="3"><?php echo esc_textarea($prearrival->additional_needs ?? ''); ?></textarea>
			</p>
			<p>
				<label for="bm_invoice_details"><strong><?php esc_html_e('Dane do faktury / ustaleń', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_invoice_details" name="invoice_details" class="large-text" rows="3"><?php echo esc_textarea($prearrival->invoice_details ?? ''); ?></textarea>
			</p>
			<p>
				<label for="bm_authorized_contacts"><strong><?php esc_html_e('Osoby upoważnione', 'basemgmt'); ?></strong></label><br>
				<textarea id="bm_authorized_contacts" name="authorized_contacts" class="large-text" rows="3"><?php echo esc_textarea($prearrival->authorized_contacts ?? ''); ?></textarea>
			</p>
		</div>

		<div id="bm-tab-settlement" class="bm-form-section">
			<h2><?php esc_html_e('Rozliczenie, dokumenty i archiwum', 'basemgmt'); ?></h2>
			<div class="bm-case-grid">
				<div class="bm-case-card"><span class="bm-stat-label"><?php esc_html_e('Dokumenty', 'basemgmt'); ?></span><strong><?php echo esc_html((string) $future_counts['documents']); ?></strong></div>
				<div class="bm-case-card"><span class="bm-stat-label"><?php esc_html_e('Wpłaty', 'basemgmt'); ?></span><strong><?php echo esc_html((string) $future_counts['payments']); ?></strong></div>
				<div class="bm-case-card"><span class="bm-stat-label"><?php esc_html_e('Ewidencja pobytu', 'basemgmt'); ?></span><strong><?php echo esc_html((string) $future_counts['actuals']); ?></strong></div>
				<div class="bm-case-card"><span class="bm-stat-label"><?php esc_html_e('Reguły cenowe', 'basemgmt'); ?></span><strong><?php echo esc_html((string) $future_counts['pricing']); ?></strong></div>
				<div class="bm-case-card"><span class="bm-stat-label"><?php esc_html_e('Rozliczenia', 'basemgmt'); ?></span><strong><?php echo esc_html((string) $future_counts['settlements']); ?></strong></div>
				<div class="bm-case-card"><span class="bm-stat-label"><?php esc_html_e('Uwagi / rozbieżności', 'basemgmt'); ?></span><strong><?php echo esc_html((string) $future_counts['issues']); ?></strong></div>
				<div class="bm-case-card"><span class="bm-stat-label"><?php esc_html_e('Zamknięcia', 'basemgmt'); ?></span><strong><?php echo esc_html((string) $future_counts['closures']); ?></strong></div>
			</div>

			<?php if ( ! empty($history) ) : ?>
				<h3><?php esc_html_e('Historia etapów', 'basemgmt'); ?></h3>
				<table class="widefat striped bm-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Data', 'basemgmt'); ?></th>
							<th><?php esc_html_e('Zmiana', 'basemgmt'); ?></th>
							<th><?php esc_html_e('Uwagi', 'basemgmt'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $history as $item ) : ?>
							<tr>
								<td><?php echo esc_html($item->created_at); ?></td>
								<td>
									<?php
									echo esc_html($process_stages[$item->old_stage] ?? ($item->old_stage ?: '—'));
									echo ' → ';
									echo esc_html($process_stages[$item->new_stage] ?? $item->new_stage);
									?>
								</td>
								<td><?php echo esc_html($item->change_note ?: '—'); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php echo $is_edit ? esc_html__('Zapisz teczkę', 'basemgmt') : esc_html__('Dodaj obóz', 'basemgmt'); ?>
			</button>
			<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps')); ?>">
				<?php esc_html_e('Wróć do listy', 'basemgmt'); ?>
			</a>
		</p>
	</form>
</div>
