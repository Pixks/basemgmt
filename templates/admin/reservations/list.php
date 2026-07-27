<?php
defined('ABSPATH') || exit;
/**
 * @var array  $reservations   – filtered reservations
 * @var array  $resources      – all resources (for filter)
 * @var array  $camps          – active camps (for filter)
 * @var array  $statuses       – ReservationRepository::STATUSES
 */
use BaseMgmt\Modules\Reservations\ReservationRepository;

$filter_resource = (int) ($_GET['filter_resource'] ?? 0);
$filter_camp     = (int) ($_GET['filter_camp']     ?? 0);
$filter_status   = sanitize_key($_GET['filter_status'] ?? '');
$filter_date     = sanitize_text_field($_GET['filter_date'] ?? '');

$status_colors = [
    'pending'   => '#856404',
    'approved'  => '#155724',
    'rejected'  => '#c0392b',
    'cancelled' => '#888',
    'expired'   => '#888',
];
?>
<div class="wrap bm-wrap">
    <h1 style="display:flex;align-items:center;justify-content:space-between;">
        <?php esc_html_e('Rezerwacje', 'basemgmt'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reservations&bm_action=resources')); ?>" class="button">
            <?php esc_html_e('Zarządzaj zasobami', 'basemgmt'); ?>
        </a>
    </h1>

    <!-- Filters -->
    <form method="get" style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
        <input type="hidden" name="page" value="basemgmt-reservations">
        <label><?php esc_html_e('Zasób:', 'basemgmt'); ?><br>
            <select name="filter_resource">
                <option value=""><?php esc_html_e('Wszystkie', 'basemgmt'); ?></option>
                <?php foreach ($resources as $r): ?><option value="<?php echo esc_attr($r->id); ?>" <?php selected($filter_resource, $r->id); ?>><?php echo esc_html($r->name); ?></option><?php endforeach; ?>
            </select>
        </label>
        <label><?php esc_html_e('Obóz:', 'basemgmt'); ?><br>
            <select name="filter_camp">
                <option value=""><?php esc_html_e('Wszystkie', 'basemgmt'); ?></option>
                <?php foreach ($camps as $c): ?><option value="<?php echo esc_attr($c->id); ?>" <?php selected($filter_camp, $c->id); ?>><?php echo esc_html($c->name); ?></option><?php endforeach; ?>
            </select>
        </label>
        <label><?php esc_html_e('Status:', 'basemgmt'); ?><br>
            <select name="filter_status">
                <option value=""><?php esc_html_e('Wszystkie', 'basemgmt'); ?></option>
                <?php foreach ($statuses as $val => $label): ?><option value="<?php echo esc_attr($val); ?>" <?php selected($filter_status, $val); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
            </select>
        </label>
        <label><?php esc_html_e('Data:', 'basemgmt'); ?><br>
            <input type="date" name="filter_date" value="<?php echo esc_attr($filter_date); ?>">
        </label>
        <button type="submit" class="button"><?php esc_html_e('Filtruj', 'basemgmt'); ?></button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reservations')); ?>" class="button"><?php esc_html_e('Wyczyść', 'basemgmt'); ?></a>
    </form>

    <!-- Add reservation (admin) -->
    <details style="margin-bottom:20px;">
        <summary style="cursor:pointer;font-weight:600;color:#2271b1;">+ <?php esc_html_e('Dodaj rezerwację ręcznie', 'basemgmt'); ?></summary>
        <div style="margin-top:12px;background:#f0f7ff;padding:16px;border-radius:4px;max-width:600px;">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('bm_admin_create_reservation'); ?>
                <input type="hidden" name="action" value="bm_admin_create_reservation">
                <table class="form-table" style="margin:0;">
                    <tr>
                        <th><label><?php esc_html_e('Zasób *', 'basemgmt'); ?></label></th>
                        <td>
                            <select name="resource_id" required>
                                <option value=""><?php esc_html_e('Wybierz', 'basemgmt'); ?></option>
                                <?php foreach ($resources as $r): if ($r->status === 'active'): ?><option value="<?php echo esc_attr($r->id); ?>"><?php echo esc_html($r->name); ?></option><?php endif; endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Obóz *', 'basemgmt'); ?></label></th>
                        <td>
                            <select name="camp_id" required>
                                <option value=""><?php esc_html_e('Wybierz', 'basemgmt'); ?></option>
                                <?php foreach ($camps as $c): ?><option value="<?php echo esc_attr($c->id); ?>"><?php echo esc_html($c->name); ?></option><?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Data *', 'basemgmt'); ?></label></th>
                        <td><input type="date" name="res_date" required></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Od *', 'basemgmt'); ?></label></th>
                        <td><input type="time" name="start_time" required></td>
                        <th><label><?php esc_html_e('Do *', 'basemgmt'); ?></label></th>
                        <td><input type="time" name="end_time" required></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Cel', 'basemgmt'); ?></label></th>
                        <td colspan="3"><input type="text" name="purpose" class="large-text"></td>
                    </tr>
                </table>
                <button type="submit" class="button button-primary" style="margin-top:8px;"><?php esc_html_e('Dodaj i zatwierdź', 'basemgmt'); ?></button>
            </form>
        </div>
    </details>

    <!-- Reservations table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:120px;"><?php esc_html_e('Data', 'basemgmt'); ?></th>
                <th style="width:100px;"><?php esc_html_e('Godziny', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Zasób', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Obóz', 'basemgmt'); ?></th>
                <th style="width:90px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Cel', 'basemgmt'); ?></th>
                <th style="width:200px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reservations)): ?>
            <tr><td colspan="7" style="text-align:center;color:#888;"><?php esc_html_e('Brak rezerwacji.', 'basemgmt'); ?></td></tr>
            <?php else: ?>
            <?php foreach ($reservations as $res):
                $resource_name = '';
                foreach ($resources as $r) { if ((int)$r->id === (int)$res->resource_id) { $resource_name = $r->name; break; } }
                $camp_name = '';
                foreach ($camps as $c) { if ((int)$c->id === (int)$res->camp_id) { $camp_name = $c->name; break; } }
            ?>
            <tr>
                <td><?php echo esc_html(date_i18n('d.m.Y', strtotime($res->res_date))); ?></td>
                <td><?php echo esc_html($res->start_time . ' – ' . $res->end_time); ?></td>
                <td><?php echo esc_html($resource_name); ?></td>
                <td><?php echo esc_html($camp_name); ?></td>
                <td style="color:<?php echo esc_attr($status_colors[$res->status] ?? '#555'); ?>; font-weight:600;">
                    <?php echo esc_html($statuses[$res->status] ?? $res->status); ?>
                </td>
                <td><?php echo esc_html(mb_substr($res->purpose, 0, 60)); ?><?php echo mb_strlen($res->purpose) > 60 ? '…' : ''; ?></td>
                <td>
                    <?php if (in_array($res->status, [ReservationRepository::STATUS_PENDING, ReservationRepository::STATUS_APPROVED], true)): ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                        <?php wp_nonce_field('bm_reservation_action'); ?>
                        <input type="hidden" name="action"         value="bm_reservation_action">
                        <input type="hidden" name="reservation_id" value="<?php echo esc_attr($res->id); ?>">
                        <?php if ($res->status === ReservationRepository::STATUS_PENDING): ?>
                        <button name="res_action" value="approve" class="button button-small" style="color:#155724;"><?php esc_html_e('✓ Zatwierdź', 'basemgmt'); ?></button>
                        <button name="res_action" value="reject"  class="button button-small" style="color:#c0392b;"><?php esc_html_e('✗ Odrzuć', 'basemgmt'); ?></button>
                        <?php else: ?>
                        <button name="res_action" value="cancel"  class="button button-small"><?php esc_html_e('Anuluj', 'basemgmt'); ?></button>
                        <?php endif; ?>
                    </form>
                    <?php else: ?>
                    <span style="color:#888;font-size:12px;"><?php echo esc_html($res->admin_comment ?? ''); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- FullCalendar view -->
    <div style="margin-top:32px;">
        <h2><?php esc_html_e('Widok kalendarza', 'basemgmt'); ?></h2>
        <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
            <label><?php esc_html_e('Filtruj zasób:', 'basemgmt'); ?>
                <select id="bm-calendar-resource">
                    <option value="0"><?php esc_html_e('Wszystkie', 'basemgmt'); ?></option>
                    <?php foreach ($resources as $r): ?>
                    <option value="<?php echo esc_attr($r->id); ?>"><?php echo esc_html($r->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div id="bm-reservations-calendar"></div>
    </div>
</div>
<script>
window.bmAdmin = window.bmAdmin || {};
bmAdmin.calendarNonce  = '<?php echo esc_js(wp_create_nonce('bm_calendar')); ?>';
bmAdmin.reorderNonce   = '<?php echo esc_js(wp_create_nonce('bm_reorder_items')); ?>';
</script>
