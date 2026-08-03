<?php
defined('ABSPATH') || exit;
/**
 * @var array   $camps
 * @var array   $reports
 * @var array   $missing
 * @var object  $totals
 * @var string  $date
 * @var int     $camp_id
 * @var string  $status
 */
$today       = gmdate('Y-m-d');
$status_map  = [
    ''          => __('Wszystkie', 'basemgmt'),
    'none'      => __('Brak', 'basemgmt'),
    'draft'     => __('Roboczy', 'basemgmt'),
    'submitted' => __('Wysłany', 'basemgmt'),
];
?>
<div class="wrap bm-wrap">
    <h1><?php esc_html_e('Meldunki', 'basemgmt'); ?></h1>

    <!-- Summary tiles -->
    <div class="bm-stats-row" style="display:flex;gap:16px;margin:16px 0;">
        <div class="bm-stat-card" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;flex:1;text-align:center;">
            <strong style="font-size:28px;color:#2271b1;"><?php echo esc_html((string) $totals->total_participants); ?></strong>
            <p style="margin:4px 0 0;"><?php esc_html_e('Uczestników', 'basemgmt'); ?></p>
        </div>
        <div class="bm-stat-card" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;flex:1;text-align:center;">
            <strong style="font-size:28px;color:#2271b1;"><?php echo esc_html((string) $totals->total_staff); ?></strong>
            <p style="margin:4px 0 0;"><?php esc_html_e('Kadra', 'basemgmt'); ?></p>
        </div>
        <div class="bm-stat-card" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;flex:1;text-align:center;">
            <strong style="font-size:28px;color:#2271b1;"><?php echo esc_html((string) $totals->total_workers); ?></strong>
            <p style="margin:4px 0 0;"><?php esc_html_e('Pracownicy', 'basemgmt'); ?></p>
        </div>
        <div class="bm-stat-card" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;flex:1;text-align:center;">
            <strong style="font-size:28px;color:#c0392b;"><?php echo count($missing); ?></strong>
            <p style="margin:4px 0 0;"><?php esc_html_e('Brakujące meldunki', 'basemgmt'); ?></p>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="page" value="basemgmt-reports">
        <div>
            <label for="bm-filter-date"><strong><?php esc_html_e('Data', 'basemgmt'); ?></strong></label><br>
            <input type="date" id="bm-filter-date" name="date" value="<?php echo esc_attr($date); ?>">
        </div>
        <div>
            <label for="bm-filter-camp"><strong><?php esc_html_e('Obóz', 'basemgmt'); ?></strong></label><br>
            <select id="bm-filter-camp" name="camp_id">
                <option value="0"><?php esc_html_e('— wszystkie —', 'basemgmt'); ?></option>
                <?php foreach ($camps as $c): ?>
                    <option value="<?php echo esc_attr((string) $c->id); ?>" <?php selected((int)$camp_id, (int)$c->id); ?>>
                        <?php echo esc_html($c->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="bm-filter-status"><strong><?php esc_html_e('Status', 'basemgmt'); ?></strong></label><br>
            <select id="bm-filter-status" name="status">
                <?php foreach ($status_map as $val => $label): ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($status, $val); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>&nbsp;</label><br>
            <button type="submit" class="button"><?php esc_html_e('Filtruj', 'basemgmt'); ?></button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reports&bm_action=view_day&date=' . $date)); ?>" class="button button-secondary">
                <?php esc_html_e('Raport zbiorczy', 'basemgmt'); ?>
            </a>
        </div>
    </form>

    <?php if ($missing): ?>
    <div class="notice notice-warning inline" style="margin:0 0 16px;padding:8px 12px;">
        <strong><?php esc_html_e('Brakujące meldunki:', 'basemgmt'); ?></strong>
        <?php echo esc_html(implode(', ', array_column($missing, 'name'))); ?>
    </div>
    <?php endif; ?>

    <!-- Manual report entry -->
    <details style="margin-bottom:20px;">
        <summary style="cursor:pointer;font-weight:600;color:#2271b1;">📋 <?php esc_html_e('Dodaj / popraw meldunek ręcznie', 'basemgmt'); ?></summary>
        <div style="margin-top:12px;background:#fff;border:1px solid #ddd;border-radius:4px;padding:16px;">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
            <?php wp_nonce_field('bm_save_report'); ?>
            <input type="hidden" name="action" value="bm_save_report">
            <div>
                <label for="bm-rep-camp"><strong><?php esc_html_e('Obóz', 'basemgmt'); ?> *</strong></label><br>
                <select id="bm-rep-camp" name="camp_id" required>
                    <option value=""><?php esc_html_e('— wybierz —', 'basemgmt'); ?></option>
                    <?php foreach ($camps as $c): ?>
                    <option value="<?php echo esc_attr((string) $c->id); ?>"><?php echo esc_html($c->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="bm-rep-date"><strong><?php esc_html_e('Data', 'basemgmt'); ?> *</strong></label><br>
                <input type="date" id="bm-rep-date" name="count_date" value="<?php echo esc_attr($date); ?>" required>
            </div>
            <div>
                <label for="bm-rep-part"><strong><?php esc_html_e('Uczestnicy', 'basemgmt'); ?></strong></label><br>
                <input type="number" id="bm-rep-part" name="participants" value="0" min="0" style="width:80px;">
            </div>
            <div>
                <label for="bm-rep-staff"><strong><?php esc_html_e('Kadra', 'basemgmt'); ?></strong></label><br>
                <input type="number" id="bm-rep-staff" name="staff" value="0" min="0" style="width:80px;">
            </div>
            <div>
                <label for="bm-rep-work"><strong><?php esc_html_e('Pracownicy', 'basemgmt'); ?></strong></label><br>
                <input type="number" id="bm-rep-work" name="workers" value="0" min="0" style="width:80px;">
            </div>
            <div>
                <label for="bm-rep-status"><strong><?php esc_html_e('Status', 'basemgmt'); ?></strong></label><br>
                <select id="bm-rep-status" name="status">
                    <option value="submitted"><?php esc_html_e('Wysłany', 'basemgmt'); ?></option>
                    <option value="draft"><?php esc_html_e('Roboczy', 'basemgmt'); ?></option>
                </select>
            </div>
            <div>
                <label for="bm-rep-notes"><strong><?php esc_html_e('Uwagi', 'basemgmt'); ?></strong></label><br>
                <input type="text" id="bm-rep-notes" name="notes" class="regular-text" placeholder="<?php esc_attr_e('opcjonalnie', 'basemgmt'); ?>">
            </div>
            <div>
                <button type="submit" class="button button-primary"><?php esc_html_e('Zapisz meldunek', 'basemgmt'); ?></button>
            </div>
        </form>
        </div>
    </details>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Data', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Obóz', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Uczestnicy', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Kadra', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Pracownicy', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Status', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Wysłany przez', 'basemgmt'); ?></th>
                <th><?php esc_html_e('Czas wysyłki', 'basemgmt'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr><td colspan="8" style="text-align:center;color:#888;"><?php esc_html_e('Brak meldunków dla wybranych filtrów.', 'basemgmt'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($reports as $r): ?>
                <?php
                    $badge_class = match($r->status) {
                        'submitted' => 'background:#d4edda;color:#155724;',
                        'draft'     => 'background:#fff3cd;color:#856404;',
                        default     => 'background:#f8d7da;color:#721c24;',
                    };
                    $badge_label = $status_map[$r->status] ?? $r->status;
                ?>
                <tr>
                    <td><?php echo esc_html($r->count_date); ?></td>
                    <td><?php echo esc_html($r->camp_name); ?></td>
                    <td><?php echo esc_html((string)$r->participants); ?></td>
                    <td><?php echo esc_html((string)$r->staff); ?></td>
                    <td><?php echo esc_html((string)$r->workers); ?></td>
                    <td><span style="padding:2px 8px;border-radius:4px;font-size:12px;<?php echo $badge_class; ?>"><?php echo esc_html($badge_label); ?></span></td>
                    <td><?php echo esc_html($r->submitted_by_name ?? '—'); ?></td>
                    <td><?php echo esc_html($r->submitted_at ?? '—'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
