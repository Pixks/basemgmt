<?php
defined('ABSPATH') || exit;
/**
 * @var array  $reports
 * @var array  $missing
 * @var object $totals
 * @var string $date
 */
?>
<div class="wrap bm-wrap">
    <h1><?php printf(esc_html__('Raport zbiorczy: %s', 'basemgmt'), esc_html($date)); ?></h1>

    <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reports&date=' . $date)); ?>" class="button" style="margin-bottom:16px;">
        &larr; <?php esc_html_e('Wróć do listy', 'basemgmt'); ?>
    </a>

    <div class="bm-stats-row" style="display:flex;gap:16px;margin:16px 0;">
        <div class="bm-stat-card" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;flex:1;text-align:center;">
            <strong style="font-size:28px;color:#2271b1;"><?php echo esc_html((string)$totals->total_participants); ?></strong>
            <p style="margin:4px 0 0;"><?php esc_html_e('Uczestników łącznie', 'basemgmt'); ?></p>
        </div>
        <div class="bm-stat-card" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;flex:1;text-align:center;">
            <strong style="font-size:28px;color:#2271b1;"><?php echo esc_html((string)$totals->total_staff); ?></strong>
            <p style="margin:4px 0 0;"><?php esc_html_e('Kadra łącznie', 'basemgmt'); ?></p>
        </div>
        <div class="bm-stat-card" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;flex:1;text-align:center;">
            <strong style="font-size:28px;color:#2271b1;"><?php echo esc_html((string)$totals->total_workers); ?></strong>
            <p style="margin:4px 0 0;"><?php esc_html_e('Pracownicy łącznie', 'basemgmt'); ?></p>
        </div>
        <div class="bm-stat-card" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;flex:1;text-align:center;">
            <strong style="font-size:28px;color:#2271b1;"><?php echo count($reports); ?></strong>
            <p style="margin:4px 0 0;"><?php esc_html_e('Wysłanych meldunków', 'basemgmt'); ?></p>
        </div>
        <div class="bm-stat-card" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;flex:1;text-align:center;">
            <strong style="font-size:28px;color:#c0392b;"><?php echo count($missing); ?></strong>
            <p style="margin:4px 0 0;"><?php esc_html_e('Brakujących', 'basemgmt'); ?></p>
        </div>
    </div>

    <?php if ($missing): ?>
    <div class="notice notice-warning inline" style="margin:0 0 16px;padding:8px 12px;">
        <strong><?php esc_html_e('Obozy bez meldunku:', 'basemgmt'); ?></strong>
        <?php echo esc_html(implode(', ', array_column($missing, 'name'))); ?>
    </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Obóz', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Uczestnicy', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Kadra', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Pracownicy', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Uwagi', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Godzina wysłania', 'basemgmt'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $r): ?>
            <tr>
                <td><strong><?php echo esc_html($r->camp_name); ?></strong></td>
                <td><?php echo esc_html((string)$r->participants); ?></td>
                <td><?php echo esc_html((string)$r->staff); ?></td>
                <td><?php echo esc_html((string)$r->workers); ?></td>
                <td><?php echo esc_html($r->notes ?? '—'); ?></td>
                <td><?php echo esc_html($r->submitted_at ? date_i18n('H:i', strtotime($r->submitted_at)) : '—'); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reports)): ?>
                <tr><td colspan="6" style="text-align:center;color:#888;"><?php esc_html_e('Brak wysłanych meldunków.', 'basemgmt'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
