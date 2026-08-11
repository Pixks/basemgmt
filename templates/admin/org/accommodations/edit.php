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
                <label for="accom_rate"><strong><?php esc_html_e('Stawka za noc (PLN)', 'basemgmt'); ?></strong></label><br>
                <input type="text" id="accom_rate" name="rate_per_night" class="regular-text"
                    value="<?php echo esc_attr(number_format((float)($item->rate_per_night ?? 0), 2, '.', '')); ?>">
                <span class="description"><?php esc_html_e('Stawka za jedną dobę noclegową dla jednej osoby.', 'basemgmt'); ?></span>
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

            <p class="submit">
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
