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
<div class="postbox">
<div class="postbox-header">
<h2 class="hndle"><?php esc_html_e('Pozycje kosztowe', 'basemgmt'); ?></h2>
</div>
<div class="inside">
<p class="description">
<?php esc_html_e('"Dni przed przyjazdem" określa termin płatności w stosunku do daty przyjazdu obozu.', 'basemgmt'); ?>
</p>
<div id="bm-lines-wrap">
<table class="widefat bm-table bm-finance-lines-table" id="bm-finance-lines">
<thead>
<tr>
<th style="width:160px;"><?php esc_html_e('Typ', 'basemgmt'); ?></th>
<th><?php esc_html_e('Nazwa pozycji', 'basemgmt'); ?></th>
<th style="width:110px;"><?php esc_html_e('Cena jedn. (netto)', 'basemgmt'); ?></th>
<th style="width:85px;"><?php esc_html_e('Jednostka', 'basemgmt'); ?></th>
<th style="width:65px;"><?php esc_html_e('VAT %', 'basemgmt'); ?></th>
<th style="width:90px;"><?php esc_html_e('Cena brutto', 'basemgmt'); ?></th>
<th style="width:90px;" title="<?php esc_attr_e('Ile dni przed datą przyjazdu termin płatności', 'basemgmt'); ?>"><?php esc_html_e('Dni przed', 'basemgmt'); ?> ⓘ</th>
<th style="width:36px;"></th>
</tr>
</thead>
<tbody id="bm-lines-tbody">
<?php if ( ! empty($lines) ) : ?>
<?php foreach ( $lines as $i => $line ) : ?>
<tr class="bm-finance-line">
<td>
<select name="line_type[]" class="widefat bm-line-type">
<?php foreach ( $line_types as $val => $lbl ) : ?>
<option value="<?php echo esc_attr($val); ?>" <?php selected($line->line_type, $val); ?>><?php echo esc_html($lbl); ?></option>
<?php endforeach; ?>
</select>
<input type="text" name="line_custom_type[]" class="widefat bm-line-custom-type"
style="margin-top:4px;<?php echo ($line->line_type === 'custom') ? '' : 'display:none;'; ?>"
placeholder="<?php esc_attr_e('Wpisz typ…', 'basemgmt'); ?>"
value="<?php echo ($line->line_type === 'custom') ? esc_attr($line->label) : ''; ?>">
</td>
<td><input type="text" name="line_label[]" class="widefat" value="<?php echo esc_attr($line->label); ?>" required></td>
<td><input type="number" name="line_price[]" class="widefat bm-line-price" step="0.01" min="0" value="<?php echo esc_attr($line->unit_price); ?>"></td>
<td>
<select name="line_unit[]" class="widefat">
<?php foreach ( $units as $val => $lbl ) : ?>
<option value="<?php echo esc_attr($val); ?>" <?php selected($line->unit, $val); ?>><?php echo esc_html($lbl); ?></option>
<?php endforeach; ?>
</select>
</td>
<td><input type="number" name="line_vat[]" class="widefat bm-line-vat" step="0.01" min="0" max="100" value="<?php echo esc_attr($line->vat_rate); ?>"></td>
<td class="bm-line-brutto" style="text-align:right;font-weight:600;padding-right:6px;"><?php
$brutto = (float)$line->unit_price * (1 + (float)$line->vat_rate / 100);
echo number_format($brutto, 2, ',', ' ');
?></td>
<td><input type="number" name="line_days_before[]" class="widefat" min="0" value="<?php echo esc_attr($line->days_before); ?>"></td>
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

<!-- ── Noclegi ─────────────────────────────────────────────────────────── -->
<div class="postbox">
<div class="postbox-header" style="display:flex;align-items:center;justify-content:space-between;">
	<h2 class="hndle"><?php esc_html_e('Noclegi', 'basemgmt'); ?></h2>
	<div style="margin-right:12px;display:flex;align-items:center;gap:8px;">
		<select id="bm-accom-select" class="bm-add-select">
			<option value=""><?php esc_html_e('— wybierz typ noclegu —', 'basemgmt'); ?></option>
			<?php foreach ($all_accom_types as $at): ?>
				<option value="<?php echo esc_attr($at->id); ?>" data-name="<?php echo esc_attr($at->name); ?>">
					<?php echo esc_html($at->name); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<button type="button" id="bm-add-accom" class="button"><?php esc_html_e('+ Dodaj', 'basemgmt'); ?></button>
	</div>
</div>
<div class="inside" style="padding:0;">
	<table class="widefat bm-table" id="bm-accom-table">
		<thead>
			<tr>
				<th><?php esc_html_e('Typ noclegu', 'basemgmt'); ?></th>
				<th style="width:120px;"><?php esc_html_e('Cena/os./noc (netto)', 'basemgmt'); ?></th>
				<th style="width:70px;"><?php esc_html_e('VAT %', 'basemgmt'); ?></th>
				<th style="width:90px;"><?php esc_html_e('Cena brutto', 'basemgmt'); ?></th>
				<th style="width:90px;" title="<?php esc_attr_e('Dni przed przyjazdem', 'basemgmt'); ?>"><?php esc_html_e('Dni przed', 'basemgmt'); ?> ⓘ</th>
				<th style="width:32px;"></th>
			</tr>
		</thead>
		<tbody id="bm-accom-tbody">
			<?php foreach ($pkg_accom as $ai => $pa): ?>
			<tr class="bm-accom-row">
				<td>
					<input type="hidden" name="accom_type_id[]" value="<?php echo esc_attr($pa->accommodation_type_id); ?>">
					<?php
					$at_name = '';
					foreach ($all_accom_types as $at) { if ((int)$at->id === (int)$pa->accommodation_type_id) { $at_name = $at->name; break; } }
					echo esc_html($at_name);
					?>
				</td>
				<td><input type="number" name="accom_price[]" class="widefat bm-accom-netto" step="0.01" min="0" value="<?php echo esc_attr(number_format((float)$pa->price_netto, 2, '.', '')); ?>"></td>
				<td><input type="number" name="accom_vat[]" class="widefat bm-accom-vat" step="0.01" min="0" max="100" value="<?php echo esc_attr(number_format((float)$pa->vat_rate, 2, '.', '')); ?>"></td>
				<td class="bm-accom-brutto" style="font-weight:600;padding-right:6px;"><?php echo number_format((float)$pa->price_netto * (1 + (float)$pa->vat_rate/100), 2, ',', ' '); ?></td>
				<td><input type="number" name="accom_days_before[]" class="widefat" min="0" value="<?php echo esc_attr($pa->days_before); ?>"></td>
				<td><button type="button" class="button-link bm-remove-accom">✕</button></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
</div>

<!-- ── Diety ──────────────────────────────────────────────────────────── -->
<div class="postbox">
<div class="postbox-header" style="display:flex;align-items:center;justify-content:space-between;">
	<h2 class="hndle"><?php esc_html_e('Diety', 'basemgmt'); ?></h2>
	<div style="margin-right:12px;display:flex;align-items:center;gap:8px;">
		<select id="bm-diet-select" class="bm-add-select">
			<option value=""><?php esc_html_e('— wybierz dietę —', 'basemgmt'); ?></option>
			<?php foreach ($all_diets as $d): ?>
				<option value="<?php echo esc_attr($d->id); ?>" data-name="<?php echo esc_attr($d->name); ?>">
					<?php echo esc_html($d->name); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<button type="button" id="bm-add-diet" class="button"><?php esc_html_e('+ Dodaj', 'basemgmt'); ?></button>
	</div>
</div>
<div class="inside" id="bm-diets-wrap">
	<?php foreach ($pkg_diet_slots as $diet_id => $slots):
		$diet_obj = null;
		foreach ($all_diets as $d) { if ((int)$d->id === $diet_id) { $diet_obj = $d; break; } }
		$diet_days_val = 30;
		foreach ($slots as $s) { $diet_days_val = (int)$s->days_before; break; }
	?>
	<div class="bm-diet-block" style="border:1px solid #e5e7eb;border-radius:4px;margin-bottom:12px;">
		<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;">
			<strong><?php echo esc_html($diet_obj ? $diet_obj->name : "Dieta #{$diet_id}"); ?></strong>
			<div style="display:flex;align-items:center;gap:10px;">
				<label style="font-size:12px;">
					<?php esc_html_e('Dni przed:', 'basemgmt'); ?>
					<input type="number" name="diet_days_before[<?php echo esc_attr($diet_id); ?>]" value="<?php echo esc_attr($diet_days_val); ?>" min="0" style="width:60px;">
				</label>
				<button type="button" class="button-link bm-remove-diet" style="color:#b91c1c;">✕ <?php esc_html_e('Usuń dietę', 'basemgmt'); ?></button>
				<input type="hidden" name="diet_id_entry[]" value="<?php echo esc_attr($diet_id); ?>">
			</div>
		</div>
		<table class="widefat bm-table" style="border:none;">
			<thead>
				<tr>
					<th style="width:36px;"><?php esc_html_e('Akt.', 'basemgmt'); ?></th>
					<th><?php esc_html_e('Posiłek', 'basemgmt'); ?></th>
					<th style="width:120px;"><?php esc_html_e('Koszt netto', 'basemgmt'); ?></th>
					<th style="width:70px;"><?php esc_html_e('VAT %', 'basemgmt'); ?></th>
					<th style="width:90px;"><?php esc_html_e('Brutto', 'basemgmt'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($meal_slots as $slot_key => $slot_label):
					$s = $slots[$slot_key] ?? null;
					$netto  = $s ? (float)$s->cost_netto : 0.00;
					$vat    = $s ? (float)$s->vat_rate   : 0.00;
					$en     = $s ? (bool)$s->enabled      : true;
					$brutto = $netto * (1 + $vat/100);
				?>
				<tr class="bm-diet-slot-row<?php echo $en ? '' : ' bm-slot-disabled'; ?>" style="<?php echo $en ? '' : 'opacity:.45;'; ?>">
					<td style="text-align:center;">
						<input type="checkbox" name="diet_slot_enabled[<?php echo esc_attr($diet_id); ?>][<?php echo esc_attr($slot_key); ?>]"
							value="1" class="bm-slot-toggle" <?php checked($en); ?>>
					</td>
					<td><?php echo esc_html($slot_label); ?></td>
					<td><input type="number" name="diet_slot_price[<?php echo esc_attr($diet_id); ?>][<?php echo esc_attr($slot_key); ?>]"
						class="widefat bm-slot-netto" step="0.01" min="0"
						value="<?php echo esc_attr(number_format($netto, 2, '.', '')); ?>" <?php echo $en ? '' : 'disabled'; ?>></td>
					<td><input type="number" name="diet_slot_vat[<?php echo esc_attr($diet_id); ?>][<?php echo esc_attr($slot_key); ?>]"
						class="widefat bm-slot-vat" step="0.01" min="0" max="100"
						value="<?php echo esc_attr(number_format($vat, 2, '.', '')); ?>" <?php echo $en ? '' : 'disabled'; ?>></td>
					<td class="bm-slot-brutto" style="font-weight:600;padding-right:6px;"><?php echo number_format($brutto, 2, ',', ' '); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr style="background:#f9fafb;">
					<td colspan="2"><strong><?php esc_html_e('Suma dzienna netto', 'basemgmt'); ?></strong></td>
					<td class="bm-diet-total" style="font-weight:700;">
						<?php echo number_format(array_sum(array_map(static fn($s) => (float)$s->cost_netto, $slots)), 2, ',', ' '); ?>
					</td>
					<td colspan="2"></td>
				</tr>
			</tfoot>
		</table>
	</div>
	<?php endforeach; ?>
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
<select name="line_type[]" class="widefat bm-line-type">
<?php foreach ( $line_types as $val => $lbl ) : ?>
<option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($lbl); ?></option>
<?php endforeach; ?>
</select>
<input type="text" name="line_custom_type[]" class="widefat bm-line-custom-type"
style="margin-top:4px;display:none;"
placeholder="<?php esc_attr_e('Wpisz typ…', 'basemgmt'); ?>">
</td>
<td><input type="text" name="line_label[]" class="widefat" required placeholder="<?php esc_attr_e('Nazwa pozycji', 'basemgmt'); ?>"></td>
<td><input type="number" name="line_price[]" class="widefat bm-line-price" step="0.01" min="0" value="0.00"></td>
<td>
<select name="line_unit[]" class="widefat">
<?php foreach ( $units as $val => $lbl ) : ?>
<option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($lbl); ?></option>
<?php endforeach; ?>
</select>
</td>
<td><input type="number" name="line_vat[]" class="widefat bm-line-vat" step="0.01" min="0" max="100" value="0"></td>
<td class="bm-line-brutto" style="text-align:right;font-weight:600;padding-right:6px;">0,00</td>
<td><input type="number" name="line_days_before[]" class="widefat" min="0" value="30"></td>
<td><button type="button" class="button-link bm-remove-line" title="<?php esc_attr_e('Usuń', 'basemgmt'); ?>">✕</button></td>
</tr>
</template>

<script>
(function() {
var tbody = document.getElementById('bm-lines-tbody');
var tmpl  = document.getElementById('bm-line-template');

document.getElementById('bm-add-line').addEventListener('click', function() {
var clone = tmpl.content.cloneNode(true);
tbody.appendChild(clone);
});

tbody.addEventListener('click', function(e) {
if ( e.target.classList.contains('bm-remove-line') ) {
e.target.closest('tr').remove();
}
});

function updateBrutto(row) {
var price  = parseFloat(row.querySelector('.bm-line-price').value) || 0;
var vat    = parseFloat(row.querySelector('.bm-line-vat').value)   || 0;
var brutto = price * (1 + vat / 100);
row.querySelector('.bm-line-brutto').textContent = brutto.toFixed(2).replace('.', ',');
}

function toggleCustomType(row) {
var sel   = row.querySelector('.bm-line-type');
var input = row.querySelector('.bm-line-custom-type');
if ( sel.value === 'custom' ) {
input.style.display = '';
input.required = true;
} else {
input.style.display = 'none';
input.required = false;
}
}

tbody.addEventListener('change', function(e) {
var row = e.target.closest('tr');
if ( ! row ) return;
if ( e.target.classList.contains('bm-line-type') ) toggleCustomType(row);
if ( e.target.classList.contains('bm-line-price') || e.target.classList.contains('bm-line-vat') ) updateBrutto(row);
});

tbody.addEventListener('input', function(e) {
var row = e.target.closest('tr');
if ( ! row ) return;
if ( e.target.classList.contains('bm-line-price') || e.target.classList.contains('bm-line-vat') ) updateBrutto(row);
});

Array.from(tbody.querySelectorAll('tr.bm-finance-line')).forEach(function(row) {
updateBrutto(row);
toggleCustomType(row);
});
})();

// ── Accommodations JS ───────────────────────────────────────────────────
(function() {
var accomTbody = document.getElementById('bm-accom-tbody');
var accomSel   = document.getElementById('bm-accom-select');

document.getElementById('bm-add-accom').addEventListener('click', function() {
	var opt = accomSel.options[accomSel.selectedIndex];
	if (!opt || !opt.value) return;
	var typeId = opt.value, typeName = opt.dataset.name || opt.text;
	var tr = document.createElement('tr');
	tr.className = 'bm-accom-row';
	tr.innerHTML =
		'<td><input type="hidden" name="accom_type_id[]" value="'+typeId+'">'+typeName+'</td>' +
		'<td><input type="number" name="accom_price[]" class="widefat bm-accom-netto" step="0.01" min="0" value="0.00"></td>' +
		'<td><input type="number" name="accom_vat[]" class="widefat bm-accom-vat" step="0.01" min="0" max="100" value="0"></td>' +
		'<td class="bm-accom-brutto" style="font-weight:600;padding-right:6px;">0,00</td>' +
		'<td><input type="number" name="accom_days_before[]" class="widefat" min="0" value="30"></td>' +
		'<td><button type="button" class="button-link bm-remove-accom">✕</button></td>';
	accomTbody.appendChild(tr);
	accomSel.selectedIndex = 0;
});

accomTbody.addEventListener('click', function(e) {
	if (e.target.classList.contains('bm-remove-accom')) e.target.closest('tr').remove();
});

function recalcAccom(row) {
	var n = parseFloat(row.querySelector('.bm-accom-netto').value) || 0;
	var v = parseFloat(row.querySelector('.bm-accom-vat').value)   || 0;
	row.querySelector('.bm-accom-brutto').textContent = (n*(1+v/100)).toFixed(2).replace('.', ',');
}
accomTbody.addEventListener('input', function(e) {
	var row = e.target.closest('tr'); if (!row) return;
	if (e.target.classList.contains('bm-accom-netto') || e.target.classList.contains('bm-accom-vat')) recalcAccom(row);
});
Array.from(accomTbody.querySelectorAll('tr')).forEach(recalcAccom);
})();

// ── Diets JS ────────────────────────────────────────────────────────────
(function() {
var dietSel  = document.getElementById('bm-diet-select');
var dietsWrap = document.getElementById('bm-diets-wrap');

var mealSlots = <?php echo json_encode($meal_slots); ?>;

document.getElementById('bm-add-diet').addEventListener('click', function() {
	var opt = dietSel.options[dietSel.selectedIndex];
	if (!opt || !opt.value) return;
	var dietId = opt.value, dietName = opt.dataset.name || opt.text;
	// Prevent duplicates
	if (dietsWrap.querySelector('[name="diet_id_entry[]"][value="'+dietId+'"]')) {
		alert('Ta dieta jest już dodana.');
		return;
	}
	var rows = '';
	for (var slotKey in mealSlots) {
		rows += '<tr class="bm-diet-slot-row">' +
			'<td style="text-align:center;"><input type="checkbox" name="diet_slot_enabled['+dietId+']['+slotKey+']" value="1" class="bm-slot-toggle" checked></td>' +
			'<td>'+mealSlots[slotKey]+'</td>' +
			'<td><input type="number" name="diet_slot_price['+dietId+']['+slotKey+']" class="widefat bm-slot-netto" step="0.01" min="0" value="0.00"></td>' +
			'<td><input type="number" name="diet_slot_vat['+dietId+']['+slotKey+']" class="widefat bm-slot-vat" step="0.01" min="0" max="100" value="0"></td>' +
			'<td class="bm-slot-brutto" style="font-weight:600;padding-right:6px;">0,00</td>' +
		'</tr>';
	}
	var block = document.createElement('div');
	block.className = 'bm-diet-block';
	block.style.cssText = 'border:1px solid #e5e7eb;border-radius:4px;margin-bottom:12px;';
	block.innerHTML =
		'<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;">' +
			'<strong>'+dietName+'</strong>' +
			'<div style="display:flex;align-items:center;gap:10px;">' +
				'<label style="font-size:12px;">Dni przed: <input type="number" name="diet_days_before['+dietId+']" value="30" min="0" style="width:60px;"></label>' +
				'<button type="button" class="button-link bm-remove-diet" style="color:#b91c1c;">✕ Usuń dietę</button>' +
				'<input type="hidden" name="diet_id_entry[]" value="'+dietId+'">' +
			'</div>' +
		'</div>' +
		'<table class="widefat bm-table" style="border:none;">' +
			'<thead><tr>' +
				'<th style="width:36px;">Akt.</th><th>Posiłek</th>' +
				'<th style="width:120px;">Koszt netto</th><th style="width:70px;">VAT %</th><th style="width:90px;">Brutto</th>' +
			'</tr></thead>' +
			'<tbody>'+rows+'</tbody>' +
			'<tfoot><tr style="background:#f9fafb;">' +
				'<td colspan="2"><strong>Suma dzienna netto</strong></td>' +
				'<td class="bm-diet-total" style="font-weight:700;">0,00</td><td colspan="2"></td>' +
			'</tr></tfoot>' +
		'</table>';
	dietsWrap.appendChild(block);
	dietSel.selectedIndex = 0;
});

dietsWrap.addEventListener('click', function(e) {
	if (e.target.classList.contains('bm-remove-diet')) e.target.closest('.bm-diet-block').remove();
});

function recalcDietBlock(block) {
	var sum = 0;
	Array.from(block.querySelectorAll('tr.bm-diet-slot-row')).forEach(function(row) {
		var n = parseFloat(row.querySelector('.bm-slot-netto').value) || 0;
		var v = parseFloat(row.querySelector('.bm-slot-vat').value)   || 0;
		row.querySelector('.bm-slot-brutto').textContent = (n*(1+v/100)).toFixed(2).replace('.', ',');
		if (!row.querySelector('.bm-slot-netto').disabled) sum += n;
	});
	var tot = block.querySelector('.bm-diet-total'); if(tot) tot.textContent = sum.toFixed(2).replace('.', ',');
}

dietsWrap.addEventListener('input', function(e) {
	var block = e.target.closest('.bm-diet-block'); if (!block) return;
	recalcDietBlock(block);
});

dietsWrap.addEventListener('change', function(e) {
	if (!e.target.classList.contains('bm-slot-toggle')) return;
	var row = e.target.closest('tr');
	var en  = e.target.checked;
	row.style.opacity = en ? '' : '0.45';
	row.querySelector('.bm-slot-netto').disabled = !en;
	row.querySelector('.bm-slot-vat').disabled   = !en;
	var block = e.target.closest('.bm-diet-block'); if(block) recalcDietBlock(block);
});

Array.from(dietsWrap.querySelectorAll('.bm-diet-block')).forEach(recalcDietBlock);
})();
</script>
