<?php
defined('ABSPATH') || exit;
/**
 * @var array   $headers      – list of plan headers
 * @var string  $filter_date  – currently filtered date
 */
?>
<div class="wrap bm-wrap">
    <h1 style="display:flex;align-items:center;justify-content:space-between;">
        <?php esc_html_e('Plan dnia', 'basemgmt'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-schedule&bm_action=new')); ?>" class="button button-primary">
            + <?php esc_html_e('Nowy plan', 'basemgmt'); ?>
        </a>
    </h1>

    <!-- Filter bar -->
    <form method="get" style="margin-bottom:16px;display:flex;gap:8px;align-items:flex-end;">
        <input type="hidden" name="page" value="basemgmt-schedule">
        <label>
            <?php esc_html_e('Data:', 'basemgmt'); ?><br>
            <input type="date" name="filter_date" value="<?php echo esc_attr($filter_date); ?>">
        </label>
        <button type="submit" class="button"><?php esc_html_e('Filtruj', 'basemgmt'); ?></button>
        <?php if ($filter_date): ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-schedule')); ?>" class="button"><?php esc_html_e('Wyczyść', 'basemgmt'); ?></a>
        <?php endif; ?>
    </form>

    <!-- Copy plan form -->
    <details style="margin-bottom:20px;">
        <summary style="cursor:pointer;font-weight:600;color:#2271b1;"><?php esc_html_e('📋 Kopiuj plan z innego dnia', 'basemgmt'); ?></summary>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;display:flex;gap:12px;align-items:flex-end;">
            <?php wp_nonce_field('bm_copy_plan'); ?>
            <input type="hidden" name="action" value="bm_copy_plan">
            <label>
                <?php esc_html_e('Kopiuj z dnia:', 'basemgmt'); ?><br>
                <input type="date" name="copy_from" required>
            </label>
            <label>
                <?php esc_html_e('Na dzień:', 'basemgmt'); ?><br>
                <input type="date" name="copy_to" required>
            </label>
            <button type="submit" class="button button-secondary"><?php esc_html_e('Kopiuj', 'basemgmt'); ?></button>
        </form>
    </details>

    <!-- Bulk create plans form -->
    <details style="margin-bottom:20px;">
        <summary style="cursor:pointer;font-weight:600;color:#2271b1;"><?php esc_html_e('📆 Masowe tworzenie planów na zakres dat', 'basemgmt'); ?></summary>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
            <?php wp_nonce_field('bm_bulk_create_plans'); ?>
            <input type="hidden" name="action" value="bm_bulk_create_plans">
            <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                <label>
                    <?php esc_html_e('Od:', 'basemgmt'); ?><br>
                    <input type="date" name="bulk_date_from" required>
                </label>
                <label>
                    <?php esc_html_e('Do:', 'basemgmt'); ?><br>
                    <input type="date" name="bulk_date_to" required>
                </label>
                <label>
                    <?php esc_html_e('Prefix tytułu (opcjonalnie):', 'basemgmt'); ?><br>
                    <input type="text" name="bulk_title" class="regular-text" placeholder="<?php esc_attr_e('np. Plan obozu letniego', 'basemgmt'); ?>">
                </label>
                <label>
                    <?php esc_html_e('Zasięg:', 'basemgmt'); ?><br>
                    <select name="bulk_is_global">
                        <option value="1"><?php esc_html_e('Globalny', 'basemgmt'); ?></option>
                        <option value="0"><?php esc_html_e('Wybrane obozy', 'basemgmt'); ?></option>
                    </select>
                </label>
                <button type="submit" class="button button-secondary"><?php esc_html_e('Utwórz plany', 'basemgmt'); ?></button>
            </div>
            <p class="description" style="margin-top:8px;"><?php esc_html_e('Tworzy pusty plan dla każdego dnia w wybranym zakresie (maks. 90 dni). Dni, dla których plan już istnieje, są pomijane.', 'basemgmt'); ?></p>
        </form>
    </details>

    <!-- Plan list -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:120px;"><?php esc_html_e('Data', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Tytuł', 'basemgmt'); ?></th>
                <th style="width:100px;"><?php esc_html_e('Zasięg', 'basemgmt'); ?></th>
                <th style="width:100px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
                <th style="width:80px;"><?php esc_html_e('Poz.', 'basemgmt'); ?></th>
                <th style="width:140px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty($headers) ): ?>
            <tr><td colspan="6" style="text-align:center;color:#888;"><?php esc_html_e('Brak planów.', 'basemgmt'); ?></td></tr>
            <?php else: ?>
            <?php foreach ( $headers as $h ):
                $items = \BaseMgmt\Modules\Schedule\ScheduleRepository::get_items((int) $h->id);
                $del_url = wp_nonce_url(admin_url('admin-post.php?action=bm_delete_plan&id=' . $h->id), 'bm_delete_plan_' . $h->id);
                $status_colors = [
                    'active'   => '#155724',
                    'draft'    => '#856404',
                    'archived' => '#888',
                ];
            ?>
            <tr>
                <td><strong><?php echo esc_html(date_i18n('d.m.Y', strtotime($h->plan_date))); ?></strong></td>
                <td><?php echo esc_html($h->title ?: '—'); ?></td>
                <td>
                    <?php if ($h->is_global): ?>
                    <span style="color:#1a73e8;">🌐 <?php esc_html_e('Globalny', 'basemgmt'); ?></span>
                    <?php else: ?>
                    <span style="color:#555;">🏕 <?php esc_html_e('Wybrane obozy', 'basemgmt'); ?></span>
                    <?php endif; ?>
                </td>
                <td style="color:<?php echo esc_attr($status_colors[$h->status] ?? '#555'); ?>;">
                    <?php echo esc_html(ucfirst($h->status)); ?>
                </td>
                <td><?php echo esc_html((string) count($items)); ?></td>
                <td>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-schedule&bm_action=edit&id=' . $h->id)); ?>" class="button button-small">
                        <?php esc_html_e('Edytuj', 'basemgmt'); ?>
                    </a>
                    <a href="<?php echo esc_url($del_url); ?>" class="button button-small"
                       onclick="return confirm('<?php esc_attr_e('Usunąć plan i wszystkie pozycje?', 'basemgmt'); ?>')">
                        <?php esc_html_e('Usuń', 'basemgmt'); ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
