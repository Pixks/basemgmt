<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
    <div class="bm-page-header">
        <h1><?php esc_html_e('Szablony zadań', 'basemgmt'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-tasks&action=new')); ?>" class="page-title-action">
            + <?php esc_html_e('Nowy szablon', 'basemgmt'); ?>
        </a>
    </div>
    <?php if (empty($templates)): ?>
        <div class="bm-empty-state">
            <span class="dashicons dashicons-list-view" style="font-size:48px;color:#c3c4c7;"></span>
            <p><?php esc_html_e('Brak szablonów zadań. Utwórz pierwszy.', 'basemgmt'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-tasks&action=new')); ?>" class="button button-primary">
                <?php esc_html_e('Utwórz szablon', 'basemgmt'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped bm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Tytuł', 'basemgmt'); ?></th>
                    <th style="width:120px;"><?php esc_html_e('Priorytet', 'basemgmt'); ?></th>
                    <th style="width:100px;"><?php esc_html_e('Auto-dodaj', 'basemgmt'); ?></th>
                    <th style="width:80px;"><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
                    <th style="width:130px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $priority_labels = [
                    'low'      => __('Niski', 'basemgmt'),
                    'normal'   => __('Normalny', 'basemgmt'),
                    'high'     => __('Wysoki', 'basemgmt'),
                    'critical' => __('Krytyczny', 'basemgmt'),
                ];
                foreach ($templates as $tpl): ?>
                <tr>
                    <td>
                        <strong>
                            <a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-tasks&action=edit&id={$tpl->id}")); ?>">
                                <?php echo esc_html($tpl->title); ?>
                            </a>
                        </strong>
                        <?php if (!empty($tpl->description)): ?>
                            <p class="description"><?php echo esc_html(mb_strimwidth($tpl->description, 0, 100, '…')); ?></p>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="bm-badge bm-badge--<?php echo esc_attr($tpl->priority); ?>">
                            <?php echo esc_html($priority_labels[$tpl->priority] ?? $tpl->priority); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($tpl->auto_add): ?>
                            <span class="bm-badge bm-badge--success">✓ <?php esc_html_e('Tak', 'basemgmt'); ?></span>
                        <?php else: ?>
                            <span class="bm-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($tpl->sort_order); ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-tasks&action=edit&id={$tpl->id}")); ?>" class="button button-small">
                            <?php esc_html_e('Edytuj', 'basemgmt'); ?>
                        </a>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_task_template&id={$tpl->id}"), "bm_delete_task_template_{$tpl->id}")); ?>"
                            class="button button-small bm-danger"
                            onclick="return confirm('<?php esc_attr_e('Usunąć szablon?', 'basemgmt'); ?>')">
                            <?php esc_html_e('Usuń', 'basemgmt'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
