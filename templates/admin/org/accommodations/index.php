<?php defined('ABSPATH') || exit; ?>
<div class="wrap bm-admin-wrap">
    <div class="bm-page-header">
        <h1><?php esc_html_e('Typy noclegów', 'basemgmt'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-accommodations&action=new')); ?>" class="page-title-action">
            + <?php esc_html_e('Nowy typ', 'basemgmt'); ?>
        </a>
    </div>

    <?php if (empty($items)): ?>
        <div class="bm-empty-state">
            <span class="dashicons dashicons-admin-home" style="font-size:48px;color:#c3c4c7;"></span>
            <p><?php esc_html_e('Brak typów noclegów. Dodaj pierwszy.', 'basemgmt'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-org-accommodations&action=new')); ?>" class="button button-primary">
                <?php esc_html_e('Dodaj typ noclegu', 'basemgmt'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped bm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
                    <th><?php esc_html_e('Opis', 'basemgmt'); ?></th>
                    <th style="width:80px;"><?php esc_html_e('Kolejność', 'basemgmt'); ?></th>
                    <th style="width:130px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <strong>
                            <a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-accommodations&action=edit&id={$item->id}")); ?>">
                                <?php echo esc_html($item->name); ?>
                            </a>
                        </strong>
                    </td>
                    <td class="bm-muted"><?php echo esc_html($item->description ?: '—'); ?></td>
                    <td><?php echo esc_html($item->sort_order); ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-org-accommodations&action=edit&id={$item->id}")); ?>"
                           class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=bm_delete_accommodation_type&id={$item->id}"), "bm_delete_accommodation_type_{$item->id}")); ?>"
                           class="button button-small bm-danger"
                           onclick="return confirm('<?php esc_attr_e('Usunąć typ noclegu?', 'basemgmt'); ?>')">
                            <?php esc_html_e('Usuń', 'basemgmt'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
