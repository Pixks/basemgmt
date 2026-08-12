<?php defined('ABSPATH') || exit;
$is_new = is_null($task_tpl);
?>
<div class="wrap bm-admin-wrap">
    <h1><?php echo $is_new ? esc_html__('Nowy szablon zadania', 'basemgmt') : esc_html($task_tpl->title); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-tasks')); ?>">← <?php esc_html_e('Wróć do listy', 'basemgmt'); ?></a>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:20px;">
        <?php wp_nonce_field('bm_save_task_template'); ?>
        <input type="hidden" name="action" value="bm_save_task_template">
        <input type="hidden" name="task_tpl_id" value="<?php echo esc_attr($task_tpl->id ?? 0); ?>">

        <div class="bm-two-col-layout">
            <div class="bm-col-main">
                <div class="postbox">
                    <div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Dane szablonu', 'basemgmt'); ?></h2></div>
                    <div class="inside">
                        <p>
                            <label for="tpl_title"><strong><?php esc_html_e('Tytuł zadania', 'basemgmt'); ?></strong></label><br>
                            <input type="text" id="tpl_title" name="title" class="large-text" required
                                value="<?php echo esc_attr($task_tpl->title ?? ''); ?>">
                        </p>
                        <p>
                            <label for="tpl_description"><strong><?php esc_html_e('Opis', 'basemgmt'); ?></strong></label><br>
                            <textarea id="tpl_description" name="description" class="large-text" rows="5"><?php echo esc_textarea($task_tpl->description ?? ''); ?></textarea>
                        </p>
                    </div>
                </div>

                <div class="notice notice-info inline" style="margin:0;padding:10px 14px;">
                    <p style="margin:0;">
                        <span class="dashicons dashicons-email-alt" style="vertical-align:middle;margin-right:4px;"></span>
                        <?php esc_html_e('Treść powiadomienia email o nowym zadaniu edytowana jest w', 'basemgmt'); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-settings&edit_template=task_assigned')); ?>">
                            <?php esc_html_e('Ustawienia → Szablony email → Zadanie', 'basemgmt'); ?>
                        </a>.
                    </p>
                </div>
            </div>
            <div class="bm-col-sidebar">
                <div class="postbox">
                    <div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Ustawienia', 'basemgmt'); ?></h2></div>
                    <div class="inside">
                        <p>
                            <label for="tpl_priority"><strong><?php esc_html_e('Priorytet', 'basemgmt'); ?></strong></label><br>
                            <select id="tpl_priority" name="priority" class="widefat">
                                <?php foreach ([
                                    'low'      => __('Niski', 'basemgmt'),
                                    'normal'   => __('Normalny', 'basemgmt'),
                                    'high'     => __('Wysoki', 'basemgmt'),
                                    'critical' => __('Krytyczny', 'basemgmt'),
                                ] as $val => $label): ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($task_tpl->priority ?? 'normal', $val); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <label for="tpl_sort_order"><strong><?php esc_html_e('Kolejność', 'basemgmt'); ?></strong></label><br>
                            <input type="number" id="tpl_sort_order" name="sort_order" class="widefat" min="0"
                                value="<?php echo esc_attr($task_tpl->sort_order ?? 0); ?>">
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="auto_add" value="1" <?php checked($task_tpl->auto_add ?? 0, 1); ?>>
                                <strong><?php esc_html_e('Automatycznie dodaj do nowego obozu', 'basemgmt'); ?></strong>
                            </label>
                            <p class="description"><?php esc_html_e('Zadanie zostanie automatycznie dodane do każdego nowo tworzonego obozu.', 'basemgmt'); ?></p>
                        </p>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Zapisz szablon', 'basemgmt'); ?></button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
