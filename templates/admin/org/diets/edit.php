<?php defined('ABSPATH') || exit;
$is_new = is_null($diet);
$diet_id = (int) ($diet->id ?? 0);
?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-diets')); ?>" class="bm-back-link">← <?php esc_html_e('Diety', 'basemgmt'); ?></a>
		<h1 style="margin-top:8px;">
			<?php echo $is_new ? esc_html__('Nowa dieta', 'basemgmt') : esc_html($diet->name); ?>
		</h1>
	</div>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field('bm_save_diet'); ?>
		<input type="hidden" name="action"  value="bm_save_org_diet">
		<input type="hidden" name="diet_id" value="<?php echo esc_attr($diet_id); ?>">

		<div class="bm-task-body">
			<div class="bm-task-main">
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Koszty per posiłek (netto, za osobę/dzień)', 'basemgmt'); ?></h2></div>
					<div class="inside">
						<p class="description" style="margin-bottom:14px;">
							<?php esc_html_e('Wpisz koszty netto za każdy posiłek w ramach tej diety. Kolumna "Brutto" jest wyliczana automatycznie.', 'basemgmt'); ?>
						</p>
						<table class="widefat bm-table" id="bm-diet-costs-table">
							<thead>
								<tr>
									<th><?php esc_html_e('Posiłek', 'basemgmt'); ?></th>
									<th style="width:130px;"><?php esc_html_e('Koszt netto (zł)', 'basemgmt'); ?></th>
									<th style="width:90px;"><?php esc_html_e('VAT %', 'basemgmt'); ?></th>
									<th style="width:110px;"><?php esc_html_e('Koszt brutto', 'basemgmt'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $slots as $slot_key => $slot_label ) :
									$c     = $costs[$slot_key] ?? null;
									$netto = $c ? (float) $c->cost_netto : 0.00;
									$vat   = $c ? (float) $c->vat_rate   : 0.00;
									$brutto = $netto * (1 + $vat / 100);
								?>
								<tr>
									<td><strong><?php echo esc_html($slot_label); ?></strong></td>
									<td>
										<input type="number"
											name="slot_price[<?php echo esc_attr($slot_key); ?>]"
											class="widefat bm-slot-netto"
											step="0.01" min="0"
											value="<?php echo esc_attr(number_format($netto, 2, '.', '')); ?>">
									</td>
									<td>
										<input type="number"
											name="slot_vat[<?php echo esc_attr($slot_key); ?>]"
											class="widefat bm-slot-vat"
											step="0.01" min="0" max="100"
											value="<?php echo esc_attr(number_format($vat, 2, '.', '')); ?>">
									</td>
									<td class="bm-slot-brutto" style="font-weight:600;padding-right:6px;">
										<?php echo number_format($brutto, 2, ',', ' '); ?>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
							<tfoot>
								<tr style="background:#f6f7f7;">
									<td><strong><?php esc_html_e('Suma dziennego kosztu netto', 'basemgmt'); ?></strong></td>
									<td id="bm-total-netto" style="font-weight:700;">
										<?php
										$total_netto = array_reduce((array)$costs, static fn($c, $r) => $c + (float)$r->cost_netto, 0.0);
										echo number_format($total_netto, 2, ',', ' ');
										?>
									</td>
									<td></td>
									<td></td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
			</div>

			<div class="bm-task-sidebar">
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Dieta', 'basemgmt'); ?></h2></div>
					<div class="inside">
						<p>
							<label for="diet_name"><strong><?php esc_html_e('Nazwa diety', 'basemgmt'); ?></strong></label><br>
							<input type="text" id="diet_name" name="name" class="widefat" required
								value="<?php echo esc_attr($diet->name ?? ''); ?>"
								placeholder="<?php esc_attr_e('np. Dieta mięsna', 'basemgmt'); ?>">
						</p>
						<p>
							<label for="diet_info"><strong><?php esc_html_e('Opis / informacje', 'basemgmt'); ?></strong></label><br>
							<textarea id="diet_info" name="diet_info" class="widefat" rows="3"><?php echo esc_textarea($diet->diet_info ?? ''); ?></textarea>
						</p>
						<p>
							<label for="diet_order"><strong><?php esc_html_e('Kolejność', 'basemgmt'); ?></strong></label><br>
							<input type="number" id="diet_order" name="sort_order" class="small-text"
								value="<?php echo esc_attr($diet->sort_order ?? 0); ?>">
						</p>
					</div>
				</div>

				<div class="bm-task-actions">
					<button type="submit" class="button button-primary button-large" style="width:100%;">
						<?php echo $is_new ? esc_html__('Utwórz dietę', 'basemgmt') : esc_html__('Zapisz zmiany', 'basemgmt'); ?>
					</button>
					<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-diets')); ?>" class="button button-large" style="width:100%;margin-top:6px;text-align:center;">
						<?php esc_html_e('Anuluj', 'basemgmt'); ?>
					</a>
					<?php if ( ! $is_new ) : ?>
						<hr style="margin:12px 0;">
						<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_org_diet&id={$diet_id}"), "bm_delete_diet_{$diet_id}")); ?>"
							class="button bm-danger" style="width:100%;text-align:center;"
							onclick="return confirm('<?php esc_attr_e('Usunąć dietę?', 'basemgmt'); ?>')">
							<?php esc_html_e('Usuń dietę', 'basemgmt'); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</form>
</div>

<script>
(function() {
	var table = document.getElementById('bm-diet-costs-table');
	if ( ! table ) return;

	function recalc() {
		var rows   = table.querySelectorAll('tbody tr');
		var sumNet = 0;
		rows.forEach(function(row) {
			var netto  = parseFloat(row.querySelector('.bm-slot-netto').value) || 0;
			var vat    = parseFloat(row.querySelector('.bm-slot-vat').value)   || 0;
			var brutto = netto * (1 + vat / 100);
			row.querySelector('.bm-slot-brutto').textContent = brutto.toFixed(2).replace('.', ',');
			sumNet += netto;
		});
		var el = document.getElementById('bm-total-netto');
		if (el) el.textContent = sumNet.toFixed(2).replace('.', ',');
	}

	table.addEventListener('input', recalc);
})();
</script>
