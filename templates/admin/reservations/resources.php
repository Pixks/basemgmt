<?php
defined('ABSPATH') || exit;
/**
 * @var array $resources – all resources
 */
use BaseMgmt\Modules\Reservations\ResourceRepository;

$type_labels = ResourceRepository::TYPES;
?>
<div class="wrap bm-wrap">
    <h1 style="display:flex;align-items:center;justify-content:space-between;">
        <?php esc_html_e('Zasoby', 'basemgmt'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reservations&bm_action=new_resource')); ?>" class="button button-primary">
            + <?php esc_html_e('Nowy zasób', 'basemgmt'); ?>
        </a>
    </h1>
    <p><a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reservations')); ?>">← <?php esc_html_e('Lista rezerwacji', 'basemgmt'); ?></a></p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Nazwa', 'basemgmt'); ?></th>
                <th style="width:120px;"><?php esc_html_e('Typ', 'basemgmt'); ?></th>
                <th style="width:120px;"><?php esc_html_e('Dostępność', 'basemgmt'); ?></th>
                <th style="width:80px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
                <th style="width:80px;"><?php esc_html_e('Blokada', 'basemgmt'); ?></th>
                <th style="width:120px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($resources)): ?>
            <tr><td colspan="6" style="text-align:center;color:#888;"><?php esc_html_e('Brak zasobów.', 'basemgmt'); ?></td></tr>
            <?php else: ?>
            <?php foreach ($resources as $r):
                $del_url = wp_nonce_url(admin_url('admin-post.php?action=bm_delete_resource&id=' . $r->id), 'bm_delete_resource_' . $r->id);
            ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($r->name); ?></strong>
                    <?php if ($r->description): ?><p style="margin:2px 0 0;font-size:12px;color:#555;"><?php echo esc_html(mb_substr($r->description, 0, 80)); ?></p><?php endif; ?>
                </td>
                <td><?php echo esc_html($type_labels[$r->type] ?? $r->type); ?></td>
                <td><?php echo esc_html($r->available_from . ' – ' . $r->available_to); ?></td>
                <td style="color:<?php echo $r->status === 'active' ? '#155724' : '#888'; ?>; font-weight:600;">
                    <?php echo esc_html($r->status === 'active' ? __('Aktywny', 'basemgmt') : __('Nieaktywny', 'basemgmt')); ?>
                </td>
                <td style="color:<?php echo $r->is_blocked ? '#c0392b' : '#155724'; ?>; font-weight:600;">
                    <?php echo esc_html($r->is_blocked ? __('🔒 Zablok.', 'basemgmt') : __('✓ OK', 'basemgmt')); ?>
                </td>
                <td>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reservations&bm_action=edit_resource&id=' . $r->id)); ?>" class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
                    <a href="<?php echo esc_url($del_url); ?>" class="button button-small"
                       onclick="return confirm('<?php esc_attr_e('Usunąć zasób?', 'basemgmt'); ?>')"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
