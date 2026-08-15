<?php
defined('ABSPATH') || exit;

use BaseMgmt\Modules\Camps\CampSettlementRepository;
use BaseMgmt\Database\Schema;

$status         = $settlement->status ?? CampSettlementRepository::STATUS_DRAFT;
$doc_number     = $settlement->document_number ?? '';
$issue_date     = $settlement->issue_date ?? current_time('Y-m-d');
$due_date       = $settlement->due_date ?? '';
$payment_terms  = $settlement->payment_terms ?? '';
$global_discount= (float) ($settlement->global_discount ?? 0);
$global_disc_t  = $settlement->global_discount_type ?? 'fixed';
$sett_notes     = $settlement->notes ?? '';

$total_gross     = (float) ($settlement->total_gross ?? 0);
$total_discounts = (float) ($settlement->total_discounts ?? 0);
$total_damages   = (float) ($settlement->total_damages ?? 0);
$amount_paid     = (float) ($settlement->amount_paid ?? 0);
$outstanding     = (float) ($settlement->outstanding_amount ?? 0);

$pdf_url = admin_url("admin.php?page=basemgmt-camps&action=settlement_pdf&id={$camp->id}");
$back_url = admin_url("admin.php?page=basemgmt-camps&action=edit&id={$camp->id}#bm-section-overview");

function bm_fmt_pln(float $v): string {
	return number_format($v, 2, ',', ' ') . ' zł';
}
?>
<div class="wrap bm-admin-wrap">

	<div class="bm-camp-header">
		<div class="bm-camp-header__title">
			<h1>📋 <?php
				/* translators: %s: camp name */
				printf(esc_html__('Rozliczenie – %s', 'basemgmt'), esc_html($camp->name));
			?></h1>
			<a class="bm-muted" href="<?php echo esc_url($back_url); ?>" style="font-size:13px;">← <?php esc_html_e('Wróć do obozu', 'basemgmt'); ?></a>
		</div>
		<div class="bm-camp-header__meta">
			<span class="bm-status-badge bm-status-badge--<?php echo esc_attr($status); ?>">
				<?php echo esc_html($statuses[$status] ?? $status); ?>
			</span>
			<?php if ( $doc_number ): ?>
				<span class="bm-muted"><?php echo esc_html($doc_number); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<div style="display:flex;gap:12px;margin:12px 0 4px;flex-wrap:wrap;">
		<a href="<?php echo esc_url($back_url); ?>" class="button"><?php esc_html_e('← Wróć do obozu', 'basemgmt'); ?></a>
		<?php if ( in_array($status, [CampSettlementRepository::STATUS_READY, CampSettlementRepository::STATUS_ISSUED, CampSettlementRepository::STATUS_PAID], true) ): ?>
			<a href="<?php echo esc_url($pdf_url); ?>" class="button button-secondary" target="_blank">🖨 <?php esc_html_e('Podgląd / Pobierz PDF', 'basemgmt'); ?></a>
		<?php endif; ?>
	</div>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="bm-settlement-form">
		<?php wp_nonce_field('bm_save_settlement'); ?>
		<input type="hidden" name="action" value="bm_save_settlement">
		<input type="hidden" name="camp_id" value="<?php echo esc_attr((string) $camp->id); ?>">
		<input type="hidden" name="settlement_id" value="<?php echo esc_attr((string) $settlement->id); ?>">
		<input type="hidden" name="status_intent" value="draft" id="bm-status-intent">

		<!-- ── SECTION A: Dane obozu ─────────────────────────────────────── -->
		<div class="bm-tab-panel" data-tab="overview" style="display:block;">
			<h2 class="bm-section-title">📁 <?php esc_html_e('A. Dane obozu', 'basemgmt'); ?></h2>
			<div class="bm-case-grid bm-case-grid--3col" style="margin-bottom:16px;">
				<div class="bm-case-card">
					<span class="bm-stat-label"><?php esc_html_e('Nazwa obozu', 'basemgmt'); ?></span>
					<strong><?php echo esc_html($camp->name); ?></strong>
				</div>
				<div class="bm-case-card">
					<span class="bm-stat-label"><?php esc_html_e('Termin pobytu', 'basemgmt'); ?></span>
					<strong><?php echo esc_html(date_i18n('d.m.Y', strtotime($camp->start_date)) . ' – ' . date_i18n('d.m.Y', strtotime($camp->end_date))); ?></strong>
				</div>
				<div class="bm-case-card">
					<span class="bm-stat-label"><?php esc_html_e('Status', 'basemgmt'); ?></span>
					<strong><?php echo esc_html(ucfirst($camp->status)); ?></strong>
				</div>
				<div class="bm-case-card">
					<span class="bm-stat-label"><?php esc_html_e('ID obozu', 'basemgmt'); ?></span>
					<strong>#<?php echo esc_html((string) $camp->id); ?></strong>
				</div>
				<div class="bm-case-card">
					<span class="bm-stat-label"><?php esc_html_e('Etap', 'basemgmt'); ?></span>
					<strong><?php echo esc_html($case->process_stage ?? '—'); ?></strong>
				</div>
				<div class="bm-case-card">
					<span class="bm-stat-label"><?php esc_html_e('Data rozliczenia', 'basemgmt'); ?></span>
					<strong><?php echo esc_html(date_i18n('d.m.Y', strtotime($issue_date))); ?></strong>
				</div>
			</div>

			<table class="form-table" style="max-width:700px;">
				<tr>
					<th scope="row"><label for="document_number"><?php esc_html_e('Numer dokumentu', 'basemgmt'); ?></label></th>
					<td><input type="text" name="document_number" id="document_number" class="regular-text" value="<?php echo esc_attr($doc_number); ?>" placeholder="np. ROZL/2025/001"></td>
				</tr>
				<tr>
					<th scope="row"><label for="issue_date"><?php esc_html_e('Data wystawienia', 'basemgmt'); ?></label></th>
					<td><input type="date" name="issue_date" id="issue_date" class="regular-text" value="<?php echo esc_attr($issue_date); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="due_date"><?php esc_html_e('Termin płatności', 'basemgmt'); ?></label></th>
					<td><input type="date" name="due_date" id="due_date" class="regular-text" value="<?php echo esc_attr($due_date); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="payment_terms"><?php esc_html_e('Warunki płatności', 'basemgmt'); ?></label></th>
					<td><textarea name="payment_terms" id="payment_terms" rows="2" class="large-text"><?php echo esc_textarea($payment_terms); ?></textarea></td>
				</tr>
			</table>
		</div>

		<!-- ── SECTION B: Dane organizatora ──────────────────────────────── -->
		<div class="bm-tab-panel" style="display:block;">
			<h2 class="bm-section-title">🏢 <?php esc_html_e('B. Dane organizatora (snapshot)', 'basemgmt'); ?></h2>
			<p class="description"><?php esc_html_e('Dane zostaną zapisane w rozliczeniu. Zmiany w zakładce Organizator nie wpłyną na istniejące rozliczenie.', 'basemgmt'); ?></p>
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:0 24px;max-width:900px;">
				<?php
				$org_text_fields = [
					'organization_name'        => __('Nazwa organizatora', 'basemgmt'),
					'contact_person'           => __('Osoba kontaktowa', 'basemgmt'),
					'contact_email'            => __('E-mail kontaktowy', 'basemgmt'),
					'contact_phone'            => __('Telefon kontaktowy', 'basemgmt'),
					'billing_name'             => __('Nazwa do faktury', 'basemgmt'),
					'billing_tax_id'           => __('NIP / identyfikator', 'basemgmt'),
					'billing_regon'            => __('REGON', 'basemgmt'),
					'billing_krs'              => __('KRS', 'basemgmt'),
					'billing_street'           => __('Ulica i numer', 'basemgmt'),
					'billing_city'             => __('Miejscowość', 'basemgmt'),
					'billing_zip'              => __('Kod pocztowy', 'basemgmt'),
					'bank_name'                => __('Nazwa banku', 'basemgmt'),
					'bank_account'             => __('Numer konta', 'basemgmt'),
					'settlement_contact_name'  => __('Osoba do rozliczenia', 'basemgmt'),
					'settlement_contact_email' => __('E-mail do rozliczenia', 'basemgmt'),
					'settlement_contact_phone' => __('Telefon do rozliczenia', 'basemgmt'),
				];
				foreach ( $org_text_fields as $field => $label ):
					$val = esc_attr((string) ($organizer_snapshot[$field] ?? ''));
				?>
				<p>
					<label style="display:block;font-weight:600;margin-bottom:2px;"><?php echo esc_html($label); ?></label>
					<input type="text" name="org_<?php echo esc_attr($field); ?>" class="large-text" value="<?php echo $val; ?>">
				</p>
				<?php endforeach; ?>
			</div>
			<p>
				<label style="display:block;font-weight:600;margin-bottom:2px;"><?php esc_html_e('Adres rozliczeniowy / uwagi', 'basemgmt'); ?></label>
				<textarea name="org_billing_address" rows="2" class="large-text" style="max-width:900px;"><?php echo esc_textarea((string) ($organizer_snapshot['billing_address'] ?? '')); ?></textarea>
			</p>
			<p>
				<label style="display:block;font-weight:600;margin-bottom:2px;"><?php esc_html_e('Uwagi od organizatora', 'basemgmt'); ?></label>
				<textarea name="org_notes" rows="2" class="large-text" style="max-width:900px;"><?php echo esc_textarea((string) ($organizer_snapshot['notes'] ?? '')); ?></textarea>
			</p>
		</div>

		<!-- ── SECTION C: Podsumowanie pobytu ────────────────────────────── -->
		<div class="bm-tab-panel" style="display:block;">
			<h2 class="bm-section-title">📅 <?php esc_html_e('C. Podsumowanie pobytu', 'basemgmt'); ?></h2>
			<?php if ( ! empty($stay_summary['days']) ): ?>
				<div class="bm-case-grid bm-case-grid--metrics" style="margin-bottom:16px;">
					<div class="bm-case-card">
						<span class="bm-stat-label"><?php esc_html_e('Zakres dat', 'basemgmt'); ?></span>
						<strong>
							<?php
							$df = ! empty($stay_summary['date_from']) ? date_i18n('d.m.Y', strtotime($stay_summary['date_from'])) : '—';
							$dt = ! empty($stay_summary['date_to'])   ? date_i18n('d.m.Y', strtotime($stay_summary['date_to']))   : '—';
							echo esc_html("{$df} – {$dt}");
							?>
						</strong>
					</div>
					<div class="bm-case-card">
						<span class="bm-stat-label"><?php esc_html_e('Suma osobodni', 'basemgmt'); ?></span>
						<strong><?php echo esc_html((string) ($stay_summary['person_days'] ?? 0)); ?></strong>
					</div>
					<div class="bm-case-card">
						<span class="bm-stat-label"><?php esc_html_e('Przyjazd', 'basemgmt'); ?></span>
						<strong><?php echo esc_html($stay_summary['arrival_time'] ?: '—'); ?></strong>
					</div>
					<div class="bm-case-card">
						<span class="bm-stat-label"><?php esc_html_e('Wyjazd', 'basemgmt'); ?></span>
						<strong><?php echo esc_html($stay_summary['departure_time'] ?: '—'); ?></strong>
					</div>
				</div>

				<?php if ( ! empty($stay_summary['diets']) ): ?>
					<h3 style="font-size:13px;margin:12px 0 6px;"><?php esc_html_e('Diety (suma)', 'basemgmt'); ?></h3>
					<table class="wp-list-table widefat striped" style="max-width:500px;">
						<thead><tr><th><?php esc_html_e('Dieta', 'basemgmt'); ?></th><th><?php esc_html_e('Liczba', 'basemgmt'); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $stay_summary['diets'] as $diet_name => $count ): ?>
							<tr><td><?php echo esc_html($diet_name); ?></td><td><?php echo esc_html((string) $count); ?></td></tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<?php if ( ! empty($stay_summary['accommodations']) ): ?>
					<h3 style="font-size:13px;margin:12px 0 6px;"><?php esc_html_e('Noclegi (suma)', 'basemgmt'); ?></h3>
					<table class="wp-list-table widefat striped" style="max-width:500px;">
						<thead><tr><th><?php esc_html_e('Typ noclegu', 'basemgmt'); ?></th><th><?php esc_html_e('Liczba', 'basemgmt'); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $stay_summary['accommodations'] as $accom_name => $count ): ?>
							<tr><td><?php echo esc_html($accom_name); ?></td><td><?php echo esc_html((string) $count); ?></td></tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			<?php else: ?>
				<p class="description"><?php esc_html_e('Brak danych deklaracji dziennej dla tego obozu.', 'basemgmt'); ?></p>
			<?php endif; ?>
		</div>

		<!-- ── SECTION D: Pozycje finansowe ──────────────────────────────── -->
		<div class="bm-tab-panel" style="display:block;">
			<h2 class="bm-section-title">💰 <?php esc_html_e('D. Pozycje rozliczenia', 'basemgmt'); ?></h2>
			<p class="description"><?php esc_html_e('Pozycje wczytane z harmonogramu płatności oraz szkód. Możesz je edytować, usuwać lub dodawać nowe.', 'basemgmt'); ?></p>

			<div id="bm-settlement-lines">
				<table class="wp-list-table widefat fixed striped bm-settlement-table" id="bm-lines-table">
					<thead>
						<tr>
							<th style="width:30px;">#</th>
							<th><?php esc_html_e('Nazwa pozycji', 'basemgmt'); ?></th>
							<th style="width:130px;"><?php esc_html_e('Typ', 'basemgmt'); ?></th>
							<th style="width:110px;"><?php esc_html_e('Kwota', 'basemgmt'); ?></th>
							<th style="width:90px;"><?php esc_html_e('Rabat', 'basemgmt'); ?></th>
							<th style="width:80px;"><?php esc_html_e('Typ rabatu', 'basemgmt'); ?></th>
							<th style="width:110px;"><?php esc_html_e('Wartość końcowa', 'basemgmt'); ?></th>
							<th style="width:110px;"><?php esc_html_e('Status płatności', 'basemgmt'); ?></th>
							<th style="width:60px;"><?php esc_html_e('Uwzgl.', 'basemgmt'); ?></th>
							<th style="width:50px;"></th>
						</tr>
					</thead>
					<tbody id="bm-lines-tbody">
					<?php foreach ( $lines as $idx => $line ):
						$amt     = (float) $line->unit_price;
						$disc    = (float) $line->discount;
						$disc_t  = (string) $line->discount_type;
						$total_l = (float) $line->total_amount;
						$inc     = (int) $line->include_in_settlement;
						$pay_st  = (string) $line->payment_status;
					?>
						<tr class="bm-settlement-line" data-idx="<?php echo esc_attr((string) $idx); ?>">
							<td class="bm-line-num"><?php echo esc_html((string) ($idx + 1)); ?></td>
							<td>
								<input type="text" name="lines[label][]" class="large-text bm-line-label"
									value="<?php echo esc_attr((string) $line->description); ?>" required>
								<input type="hidden" name="lines[source_schedule_id][]"  value="<?php echo esc_attr((string) ($line->source_schedule_id  ?? '')); ?>">
								<input type="hidden" name="lines[source_damage_id][]"    value="<?php echo esc_attr((string) ($line->source_damage_id    ?? '')); ?>">
								<input type="hidden" name="lines[source_equipment_id][]" value="<?php echo esc_attr((string) ($line->source_equipment_id ?? '')); ?>">
							</td>
							<td>
								<select name="lines[line_type][]" class="bm-line-type">
									<?php foreach ( $line_types as $lt_key => $lt_label ): ?>
										<option value="<?php echo esc_attr($lt_key); ?>"<?php selected($line->line_type, $lt_key); ?>><?php echo esc_html($lt_label); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<input type="number" name="lines[amount][]" class="small-text bm-line-amount"
									step="0.01" min="0" value="<?php echo esc_attr(number_format($amt, 2, '.', '')); ?>">
							</td>
							<td>
								<input type="number" name="lines[discount][]" class="small-text bm-line-discount"
									step="0.01" min="0" value="<?php echo esc_attr(number_format($disc, 2, '.', '')); ?>">
							</td>
							<td>
								<select name="lines[discount_type][]" class="bm-line-disc-type">
									<option value="fixed"<?php selected($disc_t, 'fixed'); ?>><?php esc_html_e('zł', 'basemgmt'); ?></option>
									<option value="percent"<?php selected($disc_t, 'percent'); ?>>%</option>
								</select>
							</td>
							<td class="bm-line-total"><?php echo esc_html(bm_fmt_pln($total_l)); ?></td>
							<td>
								<select name="lines[payment_status][]" class="bm-line-pay-status">
									<option value="expected"<?php selected($pay_st, 'expected'); ?>><?php esc_html_e('oczekiwana', 'basemgmt'); ?></option>
									<option value="paid"<?php selected($pay_st, 'paid'); ?>><?php esc_html_e('zapłacona', 'basemgmt'); ?></option>
									<option value="overdue"<?php selected($pay_st, 'overdue'); ?>><?php esc_html_e('zaległa', 'basemgmt'); ?></option>
								</select>
							</td>
							<td style="text-align:center;">
								<input type="checkbox" name="lines[include_in_settlement][<?php echo esc_attr((string) $idx); ?>]" class="bm-line-include" value="1"<?php checked($inc, 1); ?>>
							</td>
							<td>
								<button type="button" class="button bm-remove-line" title="<?php esc_attr_e('Usuń pozycję', 'basemgmt'); ?>">✕</button>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<p style="margin-top:8px;">
				<button type="button" class="button" id="bm-add-line">+ <?php esc_html_e('Dodaj pozycję', 'basemgmt'); ?></button>
			</p>

			<!-- Global discount -->
			<div style="margin-top:16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
				<label><strong><?php esc_html_e('Globalna zniżka:', 'basemgmt'); ?></strong></label>
				<input type="number" name="global_discount" id="bm-global-discount" class="small-text"
					step="0.01" min="0" value="<?php echo esc_attr(number_format($global_discount, 2, '.', '')); ?>">
				<select name="global_discount_type" id="bm-global-disc-type">
					<option value="fixed"<?php selected($global_disc_t, 'fixed'); ?>><?php esc_html_e('zł (kwota)', 'basemgmt'); ?></option>
					<option value="percent"<?php selected($global_disc_t, 'percent'); ?>>% (procent)</option>
				</select>
			</div>
		</div>

		<!-- ── SECTION E+F: Sprzęt ───────────────────────────────────────── -->
		<?php if ( ! empty($camp_equipment) ):
			$has_missing = false;
			foreach ($camp_equipment as $eq) {
				if ((int)$eq->returned_qty < (int)$eq->issued_qty) { $has_missing = true; break; }
			}
		?>
		<div class="bm-tab-panel" style="display:block;">
			<h2 class="bm-section-title">🛠 <?php esc_html_e('E/F. Sprzęt', 'basemgmt'); ?></h2>
			<?php if ($has_missing): ?>
				<p class="bm-badge bm-badge--warning" style="display:inline-block;padding:4px 10px;">⚠ <?php esc_html_e('Wykryto braki w zwrocie sprzętu', 'basemgmt'); ?></p>
			<?php endif; ?>
			<table class="wp-list-table widefat striped" style="max-width:800px;margin-top:8px;">
				<thead>
					<tr>
						<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
						<th><?php esc_html_e('Wydano', 'basemgmt'); ?></th>
						<th><?php esc_html_e('Zwrócono', 'basemgmt'); ?></th>
						<th><?php esc_html_e('Brakuje', 'basemgmt'); ?></th>
						<th><?php esc_html_e('Uwagi', 'basemgmt'); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($camp_equipment as $eq):
					$missing_qty = max(0, (int)$eq->issued_qty - (int)$eq->returned_qty);
				?>
					<tr<?php if ($missing_qty > 0) echo ' style="background:#fef2f2;"'; ?>>
						<td><?php echo esc_html($eq->name); ?></td>
						<td><?php echo esc_html((string)$eq->issued_qty); ?></td>
						<td><?php echo esc_html((string)$eq->returned_qty); ?></td>
						<td><?php if ($missing_qty > 0) echo '<strong style="color:#b91c1c;">' . esc_html((string)$missing_qty) . '</strong>'; else echo '—'; ?></td>
						<td style="font-size:12px;"><?php echo esc_html((string)($eq->notes ?? '')); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description" style="margin-top:8px;"><?php esc_html_e('Jeśli chcesz naliczyć opłatę za brak zwrotu sprzętu, dodaj ją ręcznie jako pozycję w sekcji D (typ: Sprzęt).', 'basemgmt'); ?></p>
		</div>
		<?php endif; ?>

		<!-- ── SECTION G: Uwagi końcowe ─────────────────────────────────── -->
		<div class="bm-tab-panel" style="display:block;">
			<h2 class="bm-section-title">📝 <?php esc_html_e('G. Uwagi końcowe', 'basemgmt'); ?></h2>
			<table class="form-table" style="max-width:700px;">
				<tr>
					<th scope="row"><label for="settlement_notes"><?php esc_html_e('Notatki do rozliczenia', 'basemgmt'); ?></label></th>
					<td><textarea name="settlement_notes" id="settlement_notes" rows="3" class="large-text"><?php echo esc_textarea($sett_notes); ?></textarea></td>
				</tr>
			</table>
		</div>

		<!-- ── PODSUMOWANIE FINANSOWE ─────────────────────────────────────── -->
		<div class="bm-settlement-summary" id="bm-settlement-summary">
			<h2 class="bm-section-title">📊 <?php esc_html_e('Podsumowanie finansowe', 'basemgmt'); ?></h2>
			<table style="width:100%;max-width:460px;border-collapse:collapse;">
				<tr>
					<td style="padding:6px 12px;"><?php esc_html_e('Suma pozycji brutto:', 'basemgmt'); ?></td>
					<td style="text-align:right;font-weight:bold;" id="bm-sum-gross"><?php echo esc_html(bm_fmt_pln($total_gross + $total_discounts)); ?></td>
				</tr>
				<tr>
					<td style="padding:6px 12px;color:#6b7280;"><?php esc_html_e('Rabaty:', 'basemgmt'); ?></td>
					<td style="text-align:right;color:#6b7280;" id="bm-sum-discounts">- <?php echo esc_html(bm_fmt_pln($total_discounts)); ?></td>
				</tr>
				<tr style="background:#fef9c3;">
					<td style="padding:6px 12px;font-weight:bold;"><?php esc_html_e('Do zapłaty:', 'basemgmt'); ?></td>
					<td style="text-align:right;font-weight:bold;font-size:16px;" id="bm-sum-total"><?php echo esc_html(bm_fmt_pln($total_gross)); ?></td>
				</tr>
				<tr>
					<td style="padding:6px 12px;"><?php esc_html_e('Zapłacono:', 'basemgmt'); ?></td>
					<td style="text-align:right;" id="bm-sum-paid"><?php echo esc_html(bm_fmt_pln($amount_paid)); ?></td>
				</tr>
				<?php if ( $outstanding > 0 ): ?>
				<tr style="background:#fef2f2;">
					<td style="padding:6px 12px;font-weight:bold;color:#b91c1c;"><?php esc_html_e('Pozostało:', 'basemgmt'); ?></td>
					<td style="text-align:right;font-weight:bold;color:#b91c1c;" id="bm-sum-outstanding"><?php echo esc_html(bm_fmt_pln($outstanding)); ?></td>
				</tr>
				<?php elseif ( $amount_paid > $total_gross ): ?>
				<tr style="background:#ecfdf5;">
					<td style="padding:6px 12px;font-weight:bold;color:#059669;"><?php esc_html_e('Nadpłata:', 'basemgmt'); ?></td>
					<td style="text-align:right;font-weight:bold;color:#059669;" id="bm-sum-outstanding"><?php echo esc_html(bm_fmt_pln($amount_paid - $total_gross)); ?></td>
				</tr>
				<?php else: ?>
				<tr style="background:#ecfdf5;">
					<td style="padding:6px 12px;font-weight:bold;color:#059669;"><?php esc_html_e('Rozliczone', 'basemgmt'); ?></td>
					<td style="text-align:right;" id="bm-sum-outstanding">✓</td>
				</tr>
				<?php endif; ?>
			</table>
		</div>

		<!-- ── BUTTONS ───────────────────────────────────────────────────── -->
		<div style="display:flex;gap:12px;margin:20px 0;flex-wrap:wrap;align-items:center;">
			<button type="submit" class="button button-secondary" onclick="document.getElementById('bm-status-intent').value='draft'">
				💾 <?php esc_html_e('Zapisz szkic', 'basemgmt'); ?>
			</button>
			<button type="submit" class="button button-primary" onclick="document.getElementById('bm-status-intent').value='ready'">
				✅ <?php esc_html_e('Zapisz i oznacz jako gotowe', 'basemgmt'); ?>
			</button>
			<?php if ( in_array($status, [CampSettlementRepository::STATUS_READY, CampSettlementRepository::STATUS_ISSUED, CampSettlementRepository::STATUS_PAID], true) ): ?>
				<a href="<?php echo esc_url($pdf_url); ?>" class="button" target="_blank">
					🖨 <?php esc_html_e('Generuj PDF', 'basemgmt'); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url($back_url); ?>" class="button" style="margin-left:auto;">
				← <?php esc_html_e('Wróć do obozu', 'basemgmt'); ?>
			</a>
		</div>

	</form><!-- /bm-settlement-form -->

</div><!-- /wrap -->

<!-- JS template for new line row -->
<script type="text/html" id="bm-line-template">
<tr class="bm-settlement-line" data-idx="__IDX__">
	<td class="bm-line-num">__NUM__</td>
	<td>
		<input type="text" name="lines[label][]" class="large-text bm-line-label" value="" placeholder="<?php esc_attr_e('Nazwa pozycji', 'basemgmt'); ?>" required>
		<input type="hidden" name="lines[source_schedule_id][]"  value="">
		<input type="hidden" name="lines[source_damage_id][]"    value="">
		<input type="hidden" name="lines[source_equipment_id][]" value="">
	</td>
	<td>
		<select name="lines[line_type][]" class="bm-line-type">
			<?php foreach ($line_types as $lt_key => $lt_label): ?>
			<option value="<?php echo esc_attr($lt_key); ?>"><?php echo esc_html($lt_label); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
	<td><input type="number" name="lines[amount][]" class="small-text bm-line-amount" step="0.01" min="0" value="0.00"></td>
	<td><input type="number" name="lines[discount][]" class="small-text bm-line-discount" step="0.01" min="0" value="0.00"></td>
	<td>
		<select name="lines[discount_type][]" class="bm-line-disc-type">
			<option value="fixed"><?php esc_html_e('zł', 'basemgmt'); ?></option>
			<option value="percent">%</option>
		</select>
	</td>
	<td class="bm-line-total">0,00 zł</td>
	<td>
		<select name="lines[payment_status][]" class="bm-line-pay-status">
			<option value="expected"><?php esc_html_e('oczekiwana', 'basemgmt'); ?></option>
			<option value="paid"><?php esc_html_e('zapłacona', 'basemgmt'); ?></option>
			<option value="overdue"><?php esc_html_e('zaległa', 'basemgmt'); ?></option>
		</select>
	</td>
	<td style="text-align:center;"><input type="checkbox" name="lines[include_in_settlement][__IDX__]" class="bm-line-include" value="1" checked></td>
	<td><button type="button" class="button bm-remove-line" title="<?php esc_attr_e('Usuń pozycję', 'basemgmt'); ?>">✕</button></td>
</tr>
</script>
