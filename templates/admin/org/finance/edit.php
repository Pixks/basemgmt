<?php defined('ABSPATH') || exit;
$is_new   = is_null($package);
$pkg_id   = $is_new ? 0 : (int) $package->id;
?>
<div class="wrap bm-admin-wrap">
	<div class="bm-page-header">
		<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-finance')); ?>" class="bm-back-link">← <?php esc_html_e('Pakiety finansowe', 'basemgmt'); ?></a>
		<h1 style="margin-top:8px;">
			<?php echo $is_new ? esc_html__('Nowy pakiet finansowy', 'basemgmt') : esc_html($package->name); ?>
		</h1>
	</div>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="bm-finance-form">
		<?php wp_nonce_field('bm_save_payment_package'); ?>
		<input type="hidden" name="action" value="bm_save_payment_package">
		<input type="hidden" name="package_id" value="<?php echo esc_attr($pkg_id); ?>">

		<div class="bm-task-body">
			<div class="bm-task-main">
				<!-- ── Lines ────────────────────────────────────────────────── -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e('Pozycje kosztowe', 'basemgmt'); ?></h2>
					</div>
					<div class="inside">
						<p class="description">
							<?php esc_html_e('Definiuj koszty osobodnia, zaliczki i inne pozycje. "Dni przed przyjazdem" określa termin płatności w stosunku do daty przyjazdu obozu.', 'basemgmt'); ?>
						</p>

						<div id="bm-lines-wrap">
							<table class="widefat bm-table bm-finance-lines-table" id="bm-finance-lines">
								<thead>
									<tr>
										<th style="width:180px;"><?php esc_html_e('Typ', 'basemgmt'); ?></th>
										<th><?php esc_html_e('Nazwa pozycji', 'basemgmt'); ?></th>
										<th style="width:90px;"><?php esc_html_e('Cena jedn.', 'basemgmt'); ?></th>
										<th style="width:80px;"><?php esc_html_e('Jednostka', 'basemgmt'); ?></th>
										<th style="width:70px;"><?php esc_html_e('VAT %', 'basemgmt'); ?></th>
										<th style="width:90px;" title="<?php esc_attr_e('Ile dni przed datą przyjazdu termin płatności', 'basemgmt'); ?>">
											<?php esc_html_e('Dni przed', 'basemgmt'); ?> ⓘ
										</th>
										<th style="width:70px;"><?php esc_html_e('Zaliczka?', 'basemgmt'); ?></th>
										<th style="width:40px;"></th>
									</tr>
								</thead>
								<tbody id="bm-lines-tbody">
									<?php if ( ! empty($lines) ) : ?>
										<?php foreach ( $lines as $i => $line ) : ?>
											<tr class="bm-finance-line">
												<td>
													<select name="line_type[]" class="widefat">
														<?php foreach ( $line_types as $val => $lbl ) : ?>
															<option value="<?php echo esc_attr($val); ?>" <?php selected($line->line_type, $val); ?>><?php echo esc_html($lbl); ?></option>
														<?php endforeach; ?>
													</select>
												</td>
												<td><input type="text" name="line_label[]" class="widefat" value="<?php echo esc_attr($line->label); ?>" required></td>
												<td><input type="number" name="line_price[]" class="widefat" step="0.01" min="0" value="<?php echo esc_attr($line->unit_price); ?>"></td>
												<td>
													<select name="line_unit[]" class="widefat">
														<?php foreach ( $units as $val => $lbl ) : ?>
															<option value="<?php echo esc_attr($val); ?>" <?php selected($line->unit, $val); ?>><?php echo esc_html($lbl); ?></option>
														<?php endforeach; ?>
													</select>
												</td>
												<td><input type="number" name="line_vat[]" class="widefat" step="0.01" min="0" max="100" value="<?php echo esc_attr($line->vat_rate); ?>"></td>
												<td><input type="number" name="line_days_before[]" class="widefat" min="0" value="<?php echo esc_attr($line->days_before); ?>"></td>
												<td style="text-align:center;"><input type="checkbox" name="line_is_deposit[<?php echo esc_attr($i); ?>]" value="1" <?php checked(!empty($line->is_deposit)); ?>></td>
												<td><button type="button" class="button-link bm-remove-line" title="<?php esc_attr_e('Usuń', 'basemgmt'); ?>">✕</button></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>

						<button type="button" id="bm-add-line" class="button" style="margin-top:10px;">
							+ <?php esc_html_e('Dodaj pozycję', 'basemgmt'); ?>
						</button>
					</div>
				</div>
			</div>

			<div class="bm-task-sidebar">
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Pakiet', 'basemgmt'); ?></h2></div>
					<div class="inside">
						<p>
							<label for="bm_pkg_name"><strong><?php esc_html_e('Nazwa pakietu', 'basemgmt'); ?></strong></label><br>
							<input type="text" id="bm_pkg_name" name="name" class="widefat" required
								value="<?php echo esc_attr($package->name ?? ''); ?>"
								placeholder="<?php esc_attr_e('np. Pakiet standard 2025', 'basemgmt'); ?>">
						</p>
						<p>
							<label for="bm_pkg_desc"><strong><?php esc_html_e('Opis', 'basemgmt'); ?></strong></label><br>
							<textarea id="bm_pkg_desc" name="description" class="widefat" rows="3"><?php echo esc_textarea($package->description ?? ''); ?></textarea>
						</p>
						<p>
							<label for="bm_pkg_currency"><strong><?php esc_html_e('Waluta', 'basemgmt'); ?></strong></label><br>
							<input type="text" id="bm_pkg_currency" name="currency" class="small-text" maxlength="3"
								value="<?php echo esc_attr($package->currency ?? 'PLN'); ?>">
						</p>
						<p>
							<label>
								<input type="checkbox" name="is_default" value="1" <?php checked(!empty($package->is_default)); ?>>
								<?php esc_html_e('Pakiet domyślny', 'basemgmt'); ?>
							</label>
						</p>
					</div>
				</div>

				<div class="bm-task-actions">
					<button type="submit" class="button button-primary button-large" style="width:100%;">
						<?php echo $is_new ? esc_html__('Utwórz pakiet', 'basemgmt') : esc_html__('Zapisz zmiany', 'basemgmt'); ?>
					</button>
					<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-finance')); ?>" class="button button-large" style="width:100%;margin-top:6px;text-align:center;">
						<?php esc_html_e('Anuluj', 'basemgmt'); ?>
					</a>
					<?php if ( ! $is_new ) : ?>
						<hr style="margin:12px 0;">
						<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_payment_package&id={$pkg_id}"), "bm_delete_payment_package_{$pkg_id}")); ?>"
							class="button bm-danger" style="width:100%;text-align:center;"
							onclick="return confirm('<?php esc_attr_e('Usunąć pakiet?', 'basemgmt'); ?>')">
							<?php esc_html_e('Usuń pakiet', 'basemgmt'); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</form>
</div>

<template id="bm-line-template">
	<tr class="bm-finance-line">
		<td>
			<select name="line_type[]" class="widefat">
				<?php foreach ( $line_types as $val => $lbl ) : ?>
					<option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($lbl); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td><input type="text" name="line_label[]" class="widefat" required placeholder="<?php esc_attr_e('Nazwa pozycji', 'basemgmt'); ?>"></td>
		<td><input type="number" name="line_price[]" class="widefat" step="0.01" min="0" value="0.00"></td>
		<td>
			<select name="line_unit[]" class="widefat">
				<?php foreach ( $units as $val => $lbl ) : ?>
					<option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($lbl); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td><input type="number" name="line_vat[]" class="widefat" step="0.01" min="0" max="100" value="0"></td>
		<td><input type="number" name="line_days_before[]" class="widefat" min="0" value="30"></td>
		<td style="text-align:center;"><input type="checkbox" name="line_is_deposit[__IDX__]" value="1"></td>
		<td><button type="button" class="button-link bm-remove-line" title="<?php esc_attr_e('Usuń', 'basemgmt'); ?>">✕</button></td>
	</tr>
</template>

<script>
(function() {
	var tbody = document.getElementById('bm-lines-tbody');
	var tmpl  = document.getElementById('bm-line-template');
	var idx   = <?php echo count($lines); ?>;

	document.getElementById('bm-add-line').addEventListener('click', function() {
		var clone = tmpl.content.cloneNode(true);
		var tr    = clone.querySelector('tr');
		tr.innerHTML = tr.innerHTML.replace(/__IDX__/g, idx);
		tbody.appendChild(clone);
		idx++;
	});

	tbody.addEventListener('click', function(e) {
		if ( e.target.classList.contains('bm-remove-line') ) {
			e.target.closest('tr').remove();
		}
	});
})();
</script>
