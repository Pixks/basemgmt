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

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:600px;">
<?php wp_nonce_field('bm_save_diet'); ?>
<input type="hidden" name="action"  value="bm_save_org_diet">
<input type="hidden" name="diet_id" value="<?php echo esc_attr($diet_id); ?>">

<div class="postbox">
<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Dane diety', 'basemgmt'); ?></h2></div>
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
<label for="diet_order"><strong><?php esc_html_e('Kolejność sortowania', 'basemgmt'); ?></strong></label><br>
<input type="number" id="diet_order" name="sort_order" class="small-text"
value="<?php echo esc_attr($diet->sort_order ?? 0); ?>">
</p>
<p class="description">
<?php esc_html_e('Koszty wyżywienia dla tej diety definiuje się w Organizacja → Finanse → Pakiet finansowy.', 'basemgmt'); ?>
</p>
</div>
</div>

<div class="postbox" style="margin-top:16px;">
<div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Domyślne koszty posiłków', 'basemgmt'); ?></h2></div>
<div class="inside">
<p class="description"><?php esc_html_e('Te wartości będą domyślnie podstawiane przy dodawaniu tej diety do pakietu finansowego.', 'basemgmt'); ?></p>
<table class="widefat bm-table" style="margin-top:8px;">
<thead><tr>
    <th><?php esc_html_e('Posiłek', 'basemgmt'); ?></th>
    <th style="width:140px;"><?php esc_html_e('Cena netto', 'basemgmt'); ?></th>
    <th style="width:90px;"><?php esc_html_e('VAT %', 'basemgmt'); ?></th>
    <th style="width:110px;"><?php esc_html_e('Cena brutto', 'basemgmt'); ?></th>
</tr></thead>
<tbody>
<?php foreach ( \BaseMgmt\Admin\Pages\OrgDietsPage::meal_slots() as $slot_key => $slot_label ):
    $sc = $diet_costs[$slot_key] ?? null;
    $sn = $sc ? (float)$sc->cost_netto : 0.00;
    $sv = $sc ? (float)$sc->vat_rate   : 0.00;
    $sb = $sn * (1 + $sv / 100);
?>
<tr class="bm-slot-row">
    <td><?php echo esc_html($slot_label); ?></td>
    <td><input type="number" name="slot_price[<?php echo esc_attr($slot_key); ?>]"
        class="widefat bm-sn" step="0.01" min="0"
        value="<?php echo esc_attr(number_format($sn, 2, '.', '')); ?>"></td>
    <td><input type="number" name="slot_vat[<?php echo esc_attr($slot_key); ?>]"
        class="widefat bm-sv" step="0.01" min="0" max="100"
        value="<?php echo esc_attr(number_format($sv, 2, '.', '')); ?>"></td>
    <td class="bm-sb" style="font-weight:600;padding:6px 8px;"><?php echo number_format($sb, 2, ',', ' '); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<script>
(function(){
    document.querySelectorAll('.bm-slot-row').forEach(function(row){
        function calc(){
            var n=parseFloat(row.querySelector('.bm-sn').value)||0;
            var v=parseFloat(row.querySelector('.bm-sv').value)||0;
            row.querySelector('.bm-sb').textContent=(n*(1+v/100)).toFixed(2).replace('.',',');
        }
        row.querySelector('.bm-sn').addEventListener('input',calc);
        row.querySelector('.bm-sv').addEventListener('input',calc);
    });
})();
</script>
</div>
</div>

<p class="submit">
<button type="submit" class="button button-primary">
<?php echo $is_new ? esc_html__('Utwórz dietę', 'basemgmt') : esc_html__('Zapisz zmiany', 'basemgmt'); ?>
</button>
<a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-diets')); ?>" class="button">
<?php esc_html_e('Anuluj', 'basemgmt'); ?>
</a>
<?php if ( ! $is_new ) : ?>
<a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_org_diet&id={$diet_id}"), "bm_delete_diet_{$diet_id}")); ?>"
class="button bm-danger" style="float:right;"
onclick="return confirm('<?php esc_attr_e('Usunąć dietę?', 'basemgmt'); ?>')">
<?php esc_html_e('Usuń dietę', 'basemgmt'); ?>
</a>
<?php endif; ?>
</p>
</form>
</div>
