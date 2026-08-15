<?php
defined('ABSPATH') || exit;

$o   = $organizer_snapshot;
$doc = $settlement->document_number ?? '';

$total_gross     = (float) ($settlement->total_gross ?? 0);
$total_discounts = (float) ($settlement->total_discounts ?? 0);
$total_damages   = (float) ($settlement->total_damages ?? 0);
$amount_paid     = (float) ($settlement->amount_paid ?? 0);
$outstanding     = (float) ($settlement->outstanding_amount ?? 0);

function bm_pdf_fmt(float $v): string {
	return number_format($v, 2, ',', ' ') . ' zł';
}
?>
<h1>
	<?php esc_html_e('Rozliczenie pobytu', 'basemgmt'); ?>
	<?php if ($doc): ?> – <?php echo esc_html($doc); ?><?php endif; ?>
</h1>
<p class="meta">
	<?php printf(
		/* translators: 1: camp name, 2: issue date, 3: generated at */
		esc_html__('Obóz: %1$s | Data wystawienia: %2$s | Wygenerowano: %3$s', 'basemgmt'),
		esc_html($camp->name),
		esc_html(date_i18n('d.m.Y', strtotime($settlement->issue_date ?? current_time('Y-m-d')))),
		esc_html($generated_at)
	); ?>
</p>

<!-- ── Dane obozu ─────────────────────────────────────────────────────── -->
<h2><?php esc_html_e('Dane obozu', 'basemgmt'); ?></h2>
<table>
	<tbody>
		<tr><th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th><td><?php echo esc_html($camp->name); ?></td>
		    <th><?php esc_html_e('Termin', 'basemgmt'); ?></th>
		    <td><?php echo esc_html(date_i18n('d.m.Y', strtotime($camp->start_date)) . ' – ' . date_i18n('d.m.Y', strtotime($camp->end_date))); ?></td></tr>
		<tr><th><?php esc_html_e('ID obozu', 'basemgmt'); ?></th><td>#<?php echo esc_html((string)$camp->id); ?></td>
		    <th><?php esc_html_e('Status', 'basemgmt'); ?></th><td><?php echo esc_html(ucfirst($camp->status)); ?></td></tr>
		<?php if ( ! empty($settlement->due_date) ): ?>
		<tr><th><?php esc_html_e('Termin płatności', 'basemgmt'); ?></th><td><?php echo esc_html(date_i18n('d.m.Y', strtotime($settlement->due_date))); ?></td><td colspan="2"></td></tr>
		<?php endif; ?>
	</tbody>
</table>

<!-- ── Nabywca ───────────────────────────────────────────────────────── -->
<h2><?php esc_html_e('Dane nabywcy / organizatora', 'basemgmt'); ?></h2>
<table>
	<tbody>
		<tr><th style="width:220px;"><?php esc_html_e('Nazwa organizatora', 'basemgmt'); ?></th><td><?php echo esc_html($o['organization_name'] ?? ''); ?></td></tr>
		<?php if (!empty($o['billing_name']) && $o['billing_name'] !== ($o['organization_name'] ?? '')): ?>
		<tr><th><?php esc_html_e('Nazwa do faktury', 'basemgmt'); ?></th><td><?php echo esc_html($o['billing_name']); ?></td></tr>
		<?php endif; ?>
		<?php if (!empty($o['billing_tax_id'])): ?>
		<tr><th><?php esc_html_e('NIP / identyfikator', 'basemgmt'); ?></th><td><?php echo esc_html($o['billing_tax_id']); ?></td>
		    <?php if (!empty($o['billing_regon'])): ?><th><?php esc_html_e('REGON', 'basemgmt'); ?></th><td><?php echo esc_html($o['billing_regon']); ?></td><?php else: ?><td colspan="2"></td><?php endif; ?>
		</tr>
		<?php endif; ?>
		<?php if (!empty($o['billing_street']) || !empty($o['billing_city'])): ?>
		<tr><th><?php esc_html_e('Adres', 'basemgmt'); ?></th>
		    <td colspan="3"><?php echo esc_html(trim(($o['billing_street'] ?? '') . ', ' . ($o['billing_zip'] ?? '') . ' ' . ($o['billing_city'] ?? ''), ', ')); ?></td></tr>
		<?php endif; ?>
		<?php if (!empty($o['bank_account'])): ?>
		<tr><th><?php esc_html_e('Nr konta', 'basemgmt'); ?></th><td colspan="3"><?php echo esc_html(($o['bank_name'] ?? '') . ' ' . $o['bank_account']); ?></td></tr>
		<?php endif; ?>
		<?php if (!empty($o['settlement_contact_name'])): ?>
		<tr><th><?php esc_html_e('Osoba do rozliczenia', 'basemgmt'); ?></th>
		    <td><?php echo esc_html($o['settlement_contact_name']); ?> | <?php echo esc_html($o['settlement_contact_email'] ?? ''); ?> | <?php echo esc_html($o['settlement_contact_phone'] ?? ''); ?></td>
		    <td colspan="2"></td></tr>
		<?php endif; ?>
	</tbody>
</table>

<!-- ── Podsumowanie pobytu ────────────────────────────────────────────── -->
<?php if (!empty($stay_summary['days'])): ?>
<h2><?php esc_html_e('Podsumowanie pobytu', 'basemgmt'); ?></h2>
<table>
	<tbody>
		<tr>
			<th><?php esc_html_e('Zakres dat', 'basemgmt'); ?></th>
			<td><?php
				$df = !empty($stay_summary['date_from']) ? date_i18n('d.m.Y', strtotime($stay_summary['date_from'])) : '—';
				$dt = !empty($stay_summary['date_to'])   ? date_i18n('d.m.Y', strtotime($stay_summary['date_to']))   : '—';
				echo esc_html("$df – $dt");
			?></td>
			<th><?php esc_html_e('Suma osobodni', 'basemgmt'); ?></th>
			<td><?php echo esc_html((string)($stay_summary['person_days'] ?? 0)); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e('Przyjazd', 'basemgmt'); ?></th><td><?php echo esc_html($stay_summary['arrival_time'] ?: '—'); ?></td>
			<th><?php esc_html_e('Wyjazd', 'basemgmt'); ?></th><td><?php echo esc_html($stay_summary['departure_time'] ?: '—'); ?></td>
		</tr>
		<?php if (!empty($stay_summary['diets'])): ?>
		<tr>
			<th><?php esc_html_e('Diety', 'basemgmt'); ?></th>
			<td colspan="3"><?php
				$diet_parts = [];
				foreach ($stay_summary['diets'] as $name => $cnt) {
					$diet_parts[] = esc_html($name) . ': <strong>' . esc_html((string)$cnt) . '</strong>';
				}
				echo implode(' | ', $diet_parts); // phpcs:ignore WordPress.Security.EscapeOutput
			?></td>
		</tr>
		<?php endif; ?>
		<?php if (!empty($stay_summary['accommodations'])): ?>
		<tr>
			<th><?php esc_html_e('Noclegi', 'basemgmt'); ?></th>
			<td colspan="3"><?php
				$accom_parts = [];
				foreach ($stay_summary['accommodations'] as $name => $cnt) {
					$accom_parts[] = esc_html($name) . ': <strong>' . esc_html((string)$cnt) . '</strong>';
				}
				echo implode(' | ', $accom_parts); // phpcs:ignore WordPress.Security.EscapeOutput
			?></td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>
<?php endif; ?>

<!-- ── Pozycje rozliczenia ────────────────────────────────────────────── -->
<h2><?php esc_html_e('Pozycje rozliczenia', 'basemgmt'); ?></h2>
<table>
	<thead>
		<tr>
			<th style="width:30px;">Lp.</th>
			<th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
			<th><?php esc_html_e('Typ', 'basemgmt'); ?></th>
			<th style="text-align:right;"><?php esc_html_e('Kwota', 'basemgmt'); ?></th>
			<th style="text-align:right;"><?php esc_html_e('Rabat', 'basemgmt'); ?></th>
			<th style="text-align:right;"><?php esc_html_e('Wartość końcowa', 'basemgmt'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	$lp = 0;
	$damage_lines = [];
	foreach ($lines as $line):
		if (!(int)$line->include_in_settlement) continue;
		$lp++;
		$disc_val = '';
		if ((float)$line->discount > 0) {
			$disc_val = $line->discount_type === 'percent'
				? esc_html(number_format((float)$line->discount, 0)) . '%'
				: esc_html(bm_pdf_fmt((float)$line->discount));
		}
		if ($line->line_type === 'damage') {
			$damage_lines[] = $line;
		}
	?>
		<tr<?php if ($line->line_type === 'damage') echo ' style="background:#fef9c3;"'; ?>>
			<td><?php echo esc_html((string)$lp); ?></td>
			<td><?php echo esc_html($line->description); ?></td>
			<td><?php echo esc_html($line_types[$line->line_type] ?? $line->line_type); ?></td>
			<td style="text-align:right;"><?php echo esc_html(bm_pdf_fmt((float)$line->unit_price)); ?></td>
			<td style="text-align:right;"><?php echo $disc_val ?: '—'; // phpcs:ignore ?></td>
			<td style="text-align:right;font-weight:bold;"><?php echo esc_html(bm_pdf_fmt((float)$line->total_amount)); ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<!-- ── Rozliczenie płatności ─────────────────────────────────────────── -->
<h2><?php esc_html_e('Rozliczenie płatności', 'basemgmt'); ?></h2>
<table style="max-width:420px;">
	<tbody>
		<tr><td><?php esc_html_e('Suma brutto:', 'basemgmt'); ?></td><td style="text-align:right;"><?php echo esc_html(bm_pdf_fmt($total_gross + $total_discounts)); ?></td></tr>
		<?php if ($total_discounts > 0): ?>
		<tr><td><?php esc_html_e('Rabaty:', 'basemgmt'); ?></td><td style="text-align:right;">− <?php echo esc_html(bm_pdf_fmt($total_discounts)); ?></td></tr>
		<?php endif; ?>
		<?php if ($total_damages > 0): ?>
		<tr><td><?php esc_html_e('w tym szkody:', 'basemgmt'); ?></td><td style="text-align:right;"><?php echo esc_html(bm_pdf_fmt($total_damages)); ?></td></tr>
		<?php endif; ?>
		<tr class="total-row"><td><strong><?php esc_html_e('Do zapłaty:', 'basemgmt'); ?></strong></td><td style="text-align:right;"><strong><?php echo esc_html(bm_pdf_fmt($total_gross)); ?></strong></td></tr>
		<tr><td><?php esc_html_e('Zapłacono:', 'basemgmt'); ?></td><td style="text-align:right;"><?php echo esc_html(bm_pdf_fmt($amount_paid)); ?></td></tr>
		<?php if ($outstanding > 0): ?>
		<tr class="danger"><td><strong><?php esc_html_e('Pozostało:', 'basemgmt'); ?></strong></td><td style="text-align:right;"><strong><?php echo esc_html(bm_pdf_fmt($outstanding)); ?></strong></td></tr>
		<?php elseif ($amount_paid > $total_gross): ?>
		<tr class="total-row"><td><?php esc_html_e('Nadpłata:', 'basemgmt'); ?></td><td style="text-align:right;"><?php echo esc_html(bm_pdf_fmt($amount_paid - $total_gross)); ?></td></tr>
		<?php else: ?>
		<tr class="total-row"><td colspan="2"><?php esc_html_e('✓ Rozliczone w pełni', 'basemgmt'); ?></td></tr>
		<?php endif; ?>
	</tbody>
</table>

<?php if (!empty($settlement->payment_terms)): ?>
<h2><?php esc_html_e('Warunki płatności', 'basemgmt'); ?></h2>
<p><?php echo nl2br(esc_html($settlement->payment_terms)); ?></p>
<?php endif; ?>

<?php if (!empty($settlement->notes)): ?>
<h2><?php esc_html_e('Uwagi', 'basemgmt'); ?></h2>
<p><?php echo nl2br(esc_html($settlement->notes)); ?></p>
<?php endif; ?>

<!-- ── Podpis ─────────────────────────────────────────────────────────── -->
<div style="margin-top:48px;display:flex;gap:40px;">
	<div style="width:200px;border-top:1px solid #9ca3af;padding-top:6px;font-size:12px;color:#6b7280;">
		<?php esc_html_e('Podpis wystawiającego', 'basemgmt'); ?>
	</div>
	<div style="width:200px;border-top:1px solid #9ca3af;padding-top:6px;font-size:12px;color:#6b7280;">
		<?php esc_html_e('Podpis odbiorcy', 'basemgmt'); ?>
	</div>
</div>
