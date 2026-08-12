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
