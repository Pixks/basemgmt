<?php
defined('ABSPATH') || exit;
/**
 * @var object|null  $header     – plan header (null = new)
 * @var array        $items      – plan items
 * @var array        $all_camps  – active camps for assignment
 * @var array        $assigned   – already-assigned camp IDs
 * @var array        $categories – ScheduleRepository::CATEGORIES
 * @var string       $date       – default date
 */
use BaseMgmt\Modules\Schedule\ScheduleRepository;

$item_status_labels = [
    ScheduleRepository::ITEM_ACTIVE    => 'Aktywna',
    ScheduleRepository::ITEM_CHANGED   => 'Zmieniona',
    ScheduleRepository::ITEM_CANCELLED => 'Odwołana',
];
$plan_statuses = [
    ScheduleRepository::PLAN_ACTIVE   => 'Aktywny',
    ScheduleRepository::PLAN_DRAFT    => 'Roboczy',
    ScheduleRepository::PLAN_ARCHIVED => 'Archiwum',
];
$plan_id = $header ? (int) $header->id : 0;
?>
<div class="wrap bm-wrap">
    <h1>
        <?php if ($header): ?>
            <?php printf(esc_html__('Plan dnia – %s', 'basemgmt'), esc_html(date_i18n('d.m.Y', strtotime($header->plan_date)))); ?>
        <?php else: ?>
            <?php esc_html_e('Nowy plan dnia', 'basemgmt'); ?>
        <?php endif; ?>
    </h1>
    <p><a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-schedule')); ?>">← <?php esc_html_e('Wróć do listy', 'basemgmt'); ?></a></p>

    <!-- Header form -->
    <div class="postbox" style="padding:16px 20px;margin-bottom:24px;max-width:700px;">
        <h2 class="hndle" style="padding:0 0 10px;"><?php esc_html_e('Nagłówek planu', 'basemgmt'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_schedule'); ?>
            <input type="hidden" name="action"  value="bm_save_schedule">
            <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan_id); ?>">
            <table class="form-table" style="margin:0;">
                <tr>
                    <th><label for="plan_date"><?php esc_html_e('Data', 'basemgmt'); ?></label></th>
                    <td><input type="date" id="plan_date" name="plan_date" value="<?php echo esc_attr($date); ?>" required <?php echo $plan_id ? 'readonly' : ''; ?>></td>
                </tr>
                <tr>
                    <th><label for="plan_title"><?php esc_html_e('Tytuł (opcjonalny)', 'basemgmt'); ?></label></th>
                    <td><input type="text" id="plan_title" name="plan_title" class="regular-text" value="<?php echo esc_attr($header->title ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Status', 'basemgmt'); ?></th>
                    <td>
                        <select name="plan_status">
                            <?php foreach ($plan_statuses as $val => $label): ?>
                            <option value="<?php echo esc_attr($val); ?>" <?php selected($header->status ?? ScheduleRepository::PLAN_ACTIVE, $val); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Zasięg', 'basemgmt'); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="is_global" value="1" <?php checked($header->is_global ?? 1, 1); ?> id="global-yes">
                            <?php esc_html_e('Globalny (widoczny dla wszystkich obozów)', 'basemgmt'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="is_global" value="0" <?php checked($header->is_global ?? 1, 0); ?> id="global-no">
                            <?php esc_html_e('Tylko wybrane obozy', 'basemgmt'); ?>
                        </label>
                        <?php if ($all_camps): ?>
                        <div id="camp-select" style="margin-top:8px;display:<?php echo ($header->is_global ?? 1) ? 'none' : 'block'; ?>;">
                            <p class="description"><?php esc_html_e('Zaznacz obozy:', 'basemgmt'); ?></p>
                            <?php foreach ($all_camps as $camp): ?>
                            <label style="display:block;">
                                <input type="checkbox" name="camp_ids[]" value="<?php echo esc_attr($camp->id); ?>"
                                    <?php checked(in_array((string) $camp->id, $assigned, true)); ?>>
                                <?php echo esc_html($camp->name); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <script>
                        document.querySelectorAll('input[name="is_global"]').forEach(function(r){
                            r.addEventListener('change', function(){
                                document.getElementById('camp-select').style.display = (this.value === '0') ? 'block' : 'none';
                            });
                        });
                        </script>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Zapisz nagłówek', 'basemgmt')); ?>
        </form>
    </div>

    <?php if ($plan_id): ?>
    <!-- Items list -->
    <div class="postbox" style="padding:16px 20px;margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <h2 class="hndle" style="padding:0;"><?php esc_html_e('Pozycje planu', 'basemgmt'); ?></h2>
            <div style="display:flex;gap:8px;">
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bm_reset_plan_flags&plan_id=' . $plan_id), 'bm_reset_flags_' . $plan_id)); ?>"
                   class="button button-small"
                   onclick="return confirm('<?php esc_attr_e('Zresetować flagi zmian na dziś?', 'basemgmt'); ?>')">
                    <?php esc_html_e('Resetuj flagi zmian', 'basemgmt'); ?>
                </a>
            </div>
        </div>

        <?php if ($items): ?>
        <table class="wp-list-table widefat fixed striped" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th style="width:30px;"></th>
                    <th style="width:80px;"><?php esc_html_e('Godzina', 'basemgmt'); ?></th>
                    <th><?php esc_html_e('Tytuł', 'basemgmt'); ?></th>
                    <th style="width:120px;"><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
                    <th style="width:90px;"><?php esc_html_e('Status', 'basemgmt'); ?></th>
                    <th style="width:100px;"><?php esc_html_e('Flagi', 'basemgmt'); ?></th>
                    <th style="width:120px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
                </tr>
            </thead>
            <tbody id="bm-plan-items-tbody">
                <?php foreach ($items as $item):
                    $del_url = wp_nonce_url(admin_url('admin-post.php?action=bm_delete_plan_item&item_id=' . $item->id . '&plan_id=' . $plan_id), 'bm_delete_item_' . $item->id);
                    $status_colors = ['active' => '#155724', 'changed' => '#856404', 'cancelled' => '#c0392b'];
                ?>
                <tr data-item-id="<?php echo esc_attr($item->id); ?>">
                    <td><span class="bm-drag-handle" style="cursor:grab;color:#aaa;" title="<?php esc_attr_e('Przeciągnij', 'basemgmt'); ?>">⠿</span></td>
                    <td>
                        <?php echo esc_html($item->time_from); ?>
                        <?php if ($item->time_to): ?>
                        – <?php echo esc_html($item->time_to); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html($item->title); ?></strong>
                        <?php if ($item->description): ?>
                        <p style="margin:2px 0 0;font-size:12px;color:#555;"><?php echo esc_html(mb_substr($item->description, 0, 80)); ?><?php echo mb_strlen($item->description) > 80 ? '…' : ''; ?></p>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($categories[$item->category] ?? $item->category); ?></td>
                    <td style="color:<?php echo esc_attr($status_colors[$item->item_status] ?? '#555'); ?>;">
                        <?php echo esc_html($item_status_labels[$item->item_status] ?? $item->item_status); ?>
                    </td>
                    <td style="font-size:12px;">
                        <?php if ($item->is_new_today):     ?><span style="background:#d4edda;padding:1px 4px;border-radius:3px;">🆕 nowe</span> <?php endif; ?>
                        <?php if ($item->is_updated_today): ?><span style="background:#fff3cd;padding:1px 4px;border-radius:3px;">✏ zmień</span> <?php endif; ?>
                        <?php if ($item->is_mandatory):     ?><span style="background:#f8d7da;padding:1px 4px;border-radius:3px;">⚡ obow.</span><?php endif; ?>
                    </td>
                    <td>
                        <a href="#edit-item-<?php echo esc_attr($item->id); ?>" class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
                        <a href="<?php echo esc_url($del_url); ?>" class="button button-small"
                           onclick="return confirm('<?php esc_attr_e('Usunąć pozycję?', 'basemgmt'); ?>')"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Inline edit forms (hidden by default) -->
        <?php foreach ($items as $item): ?>
        <div id="edit-item-<?php echo esc_attr($item->id); ?>" style="display:none;background:#f9f9f9;padding:16px;border:1px solid #ddd;border-radius:4px;margin-bottom:12px;">
            <h3 style="margin-top:0;"><?php esc_html_e('Edytuj pozycję', 'basemgmt'); ?></h3>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('bm_save_plan_item'); ?>
                <input type="hidden" name="action"  value="bm_save_plan_item">
                <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan_id); ?>">
                <input type="hidden" name="item_id" value="<?php echo esc_attr($item->id); ?>">
                <?php include __DIR__ . '/_item_form.php'; ?>
                <button type="submit" class="button button-primary"><?php esc_html_e('Zapisz', 'basemgmt'); ?></button>
                <button type="button" class="button" onclick="document.getElementById('edit-item-<?php echo esc_attr($item->id); ?>').style.display='none'">
                    <?php esc_html_e('Anuluj', 'basemgmt'); ?>
                </button>
            </form>
        </div>
        <script>document.querySelector('a[href="#edit-item-<?php echo esc_attr($item->id); ?>"]').addEventListener('click',function(e){e.preventDefault();var el=document.getElementById('edit-item-<?php echo esc_attr($item->id); ?>');el.style.display=el.style.display==='none'?'block':'none';});</script>
        <?php endforeach; ?>

        <?php endif; ?>

        <!-- Add new item form -->
        <details style="margin-top:16px;">
            <summary style="cursor:pointer;font-weight:600;color:#2271b1;">+ <?php esc_html_e('Dodaj nową pozycję', 'basemgmt'); ?></summary>
            <div style="margin-top:12px;background:#f0f7ff;padding:16px;border-radius:4px;">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('bm_save_plan_item'); ?>
                    <input type="hidden" name="action"  value="bm_save_plan_item">
                    <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan_id); ?>">
                    <input type="hidden" name="item_id" value="0">
                    <?php $item = null; include __DIR__ . '/_item_form.php'; ?>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Dodaj pozycję', 'basemgmt'); ?></button>
                </form>
            </div>
        </details>
    </div>
    <?php endif; ?>
</div>
<script>
window.bmAdmin = window.bmAdmin || {};
bmAdmin.reorderNonce = '<?php echo esc_js(wp_create_nonce('bm_reorder_items')); ?>';
</script>
