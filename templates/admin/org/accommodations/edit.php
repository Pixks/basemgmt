<?php defined('ABSPATH') || exit;
$is_new  = is_null($item);
$item_id = $item->id ?? 0;
?>
<div class="wrap bm-admin-wrap">
    <div class="bm-page-header">
        <h1><?php echo $is_new ? esc_html__('Nowy typ noclegu', 'basemgmt') : esc_html__('Edytuj typ noclegu', 'basemgmt'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-accommodations')); ?>" class="page-title-action">
            ← <?php esc_html_e('Wróć do listy', 'basemgmt'); ?>
        </a>
    </div>

    <div class="bm-form-section" style="max-width:600px;">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_accommodation_type'); ?>
            <input type="hidden" name="action" value="bm_save_accommodation_type">
            <input type="hidden" name="accom_id" value="<?php echo esc_attr($item_id); ?>">

            <p>
                <label for="accom_name"><strong><?php esc_html_e('Nazwa typu noclegu', 'basemgmt'); ?></strong></label><br>
                <input type="text" id="accom_name" name="name" class="regular-text" required
                    value="<?php echo esc_attr($item->name ?? ''); ?>">
            </p>
            <p>
                <label for="accom_desc"><strong><?php esc_html_e('Opis', 'basemgmt'); ?></strong></label><br>
                <textarea id="accom_desc" name="description" class="large-text" rows="3"><?php echo esc_textarea($item->description ?? ''); ?></textarea>
            </p>
            <p>
                <label for="accom_order"><strong><?php esc_html_e('Kolejność sortowania', 'basemgmt'); ?></strong></label><br>
                <input type="number" id="accom_order" name="sort_order" class="small-text"
                    value="<?php echo esc_attr($item->sort_order ?? 0); ?>">
            </p>

            <hr style="margin:16px 0;">
            <h3 style="margin-bottom:8px;"><?php esc_html_e('Domyślne koszty (do pakietów finansowych)', 'basemgmt'); ?></h3>
            <p class="description" style="margin-bottom:10px;"><?php esc_html_e('Te wartości będą domyślnie podstawiane przy dodawaniu tego noclegu do pakietu finansowego.', 'basemgmt'); ?></p>
            <table class="widefat bm-table" style="max-width:400px;">
                <thead><tr>
                    <th><?php esc_html_e('Cena netto / os. / noc', 'basemgmt'); ?></th>
                    <th style="width:100px;"><?php esc_html_e('VAT %', 'basemgmt'); ?></th>
                    <th style="width:120px;"><?php esc_html_e('Cena brutto', 'basemgmt'); ?></th>
                </tr></thead>
                <tbody><tr>
                    <td><input type="number" id="accom_rate" name="rate_per_night" class="widefat" step="0.01" min="0"
                        value="<?php echo esc_attr(number_format((float)($item->rate_per_night ?? 0), 2, '.', '')); ?>"></td>
                    <td><input type="number" id="accom_vat" name="default_vat" class="widefat" step="0.01" min="0" max="100"
                        value="<?php echo esc_attr(number_format((float)($item->default_vat ?? 0), 2, '.', '')); ?>"></td>
                    <td id="accom_brutto" style="font-weight:600;padding:6px 8px;"><?php
                        $b = (float)($item->rate_per_night ?? 0) * (1 + (float)($item->default_vat ?? 0)/100);
                        echo number_format($b, 2, ',', ' ');
                    ?></td>
                </tr></tbody>
            </table>
            <script>
            (function(){
                var n = document.getElementById('accom_rate'), v = document.getElementById('accom_vat'), b = document.getElementById('accom_brutto');
                function calc(){ if(n&&v&&b) b.textContent = (parseFloat(n.value)||0)*(1+(parseFloat(v.value)||0)/100).toFixed(2).replace('.',','); }
                if(n) n.addEventListener('input', calc);
                if(v) v.addEventListener('input', calc);
            })();
            </script>

            <p class="submit" style="margin-top:16px;">
                <button type="submit" class="button button-primary">
                    <?php echo $is_new ? esc_html__('Dodaj typ noclegu', 'basemgmt') : esc_html__('Zapisz zmiany', 'basemgmt'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-accommodations')); ?>" class="button">
                    <?php esc_html_e('Anuluj', 'basemgmt'); ?>
                </a>
            </p>
        </form>
    </div>
</div>
