<?php
defined('ABSPATH') || exit;
/**
 * @var object|null $resource – resource object or null for new
 * @var array       $blocks   – maintenance blocks for this resource
 * @var array       $types    – ResourceRepository::TYPES
 */
use BaseMgmt\Modules\Reservations\ResourceRepository;
$rid = $resource ? (int) $resource->id : 0;
?>
<div class="wrap bm-wrap">
    <h1><?php echo $resource ? esc_html(sprintf(__('Edytuj zasób: %s', 'basemgmt'), $resource->name)) : esc_html__('Nowy zasób', 'basemgmt'); ?></h1>
    <p><a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reservations&bm_action=resources')); ?>">← <?php esc_html_e('Wróć do zasobów', 'basemgmt'); ?></a></p>

    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_resource'); ?>
            <input type="hidden" name="action"      value="bm_save_resource">
            <input type="hidden" name="resource_id" value="<?php echo esc_attr($rid); ?>">
            <table class="form-table" style="margin:0;">
                <tr>
                    <th><label><?php esc_html_e('Nazwa *', 'basemgmt'); ?></label></th>
                    <td><input type="text" name="name" class="regular-text" required value="<?php echo esc_attr($resource->name ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Typ', 'basemgmt'); ?></label></th>
                    <td>
                        <select name="type">
                            <?php foreach ($types as $val => $label): ?>
                            <option value="<?php echo esc_attr($val); ?>" <?php selected($resource->type ?? ResourceRepository::TYPE_OTHER, $val); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Opis', 'basemgmt'); ?></label></th>
                    <td><textarea name="description" rows="3" class="large-text"><?php echo esc_textarea($resource->description ?? ''); ?></textarea></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Zasady', 'basemgmt'); ?></label></th>
                    <td><textarea name="rules" rows="3" class="large-text"><?php echo esc_textarea($resource->rules ?? ''); ?></textarea></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Status', 'basemgmt'); ?></label></th>
                    <td>
                        <select name="status">
                            <option value="active"   <?php selected($resource->status ?? 'active', 'active'); ?>><?php esc_html_e('Aktywny', 'basemgmt'); ?></option>
                            <option value="inactive" <?php selected($resource->status ?? 'active', 'inactive'); ?>><?php esc_html_e('Nieaktywny', 'basemgmt'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Dostępny od', 'basemgmt'); ?></label></th>
                    <td><input type="time" name="available_from" value="<?php echo esc_attr($resource->available_from ?? '06:00'); ?>"></td>
                    <th><label><?php esc_html_e('Dostępny do', 'basemgmt'); ?></label></th>
                    <td><input type="time" name="available_to" value="<?php echo esc_attr($resource->available_to ?? '22:00'); ?>"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Min. czas (min)', 'basemgmt'); ?></label></th>
                    <td><input type="number" name="min_duration_minutes" value="<?php echo esc_attr($resource->min_duration_minutes ?? 0); ?>" min="0" style="width:80px;">
                    <p class="description"><?php esc_html_e('0 = brak limitu', 'basemgmt'); ?></p></td>
                    <th><label><?php esc_html_e('Max. czas (min)', 'basemgmt'); ?></label></th>
                    <td><input type="number" name="max_duration_minutes" value="<?php echo esc_attr($resource->max_duration_minutes ?? 0); ?>" min="0" style="width:80px;"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Min. wyprzedzenie (h)', 'basemgmt'); ?></label></th>
                    <td><input type="number" name="min_advance_hours" value="<?php echo esc_attr($resource->min_advance_hours ?? 0); ?>" min="0" style="width:80px;"></td>
                    <th><label><?php esc_html_e('Max. z góry (dni)', 'basemgmt'); ?></label></th>
                    <td><input type="number" name="max_advance_days" value="<?php echo esc_attr($resource->max_advance_days ?? 30); ?>" min="1" style="width:80px;"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Anulowanie (h wyprzedzenia)', 'basemgmt'); ?></label></th>
                    <td><input type="number" name="cancel_advance_hours" value="<?php echo esc_attr($resource->cancel_advance_hours ?? 0); ?>" min="0" style="width:80px;">
                    <p class="description"><?php esc_html_e('Min. ile godzin przed rezerwacją obóz może ją anulować. 0 = można zawsze.', 'basemgmt'); ?></p></td>
                    <th><label><?php esc_html_e('Max. akt. rezerwacji / obóz', 'basemgmt'); ?></label></th>
                    <td><input type="number" name="max_reservations_per_camp" value="<?php echo esc_attr($resource->max_reservations_per_camp ?? 0); ?>" min="0" style="width:80px;">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Model cenowy', 'basemgmt'); ?></label></th>
                    <td>
                        <?php $pricing_mode = $resource->pricing_mode ?? 'flat'; ?>
                        <label style="display:block;margin-bottom:4px;">
                            <input type="radio" name="pricing_mode" value="flat" <?php checked($pricing_mode, 'flat'); ?> id="bm-pricing-flat">
                            <?php esc_html_e('Stała opłata za rezerwację (np. krąg ogniskowy — płaci się raz)', 'basemgmt'); ?>
                        </label>
                        <label style="display:block;">
                            <input type="radio" name="pricing_mode" value="per_unit" <?php checked($pricing_mode, 'per_unit'); ?> id="bm-pricing-per-unit">
                            <?php esc_html_e('Opłata za sztukę (np. kajak — obóz podaje ile sztuk chce wypożyczyć)', 'basemgmt'); ?>
                        </label>
                    </td>
                </tr>
                <tr id="bm-total-units-row" style="<?php echo $pricing_mode === 'per_unit' ? '' : 'display:none;'; ?>">
                    <th><label for="total_units"><?php esc_html_e('Całkowita liczba sztuk w bazie', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="number" id="total_units" name="total_units"
                               value="<?php echo esc_attr($resource->total_units ?? 0); ?>"
                               min="0" style="width:80px;">
                        <p class="description"><?php esc_html_e('Ile łącznie sztuk tego zasobu posiada baza (walidacja dostępności podczas rezerwacji).', 'basemgmt'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cost_per_reservation"><?php esc_html_e('Koszt rezerwacji (PLN)', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="number" id="cost_per_reservation" name="cost_per_reservation"
                               value="<?php echo esc_attr($resource->cost_per_reservation ?? '0.00'); ?>"
                               min="0" step="0.01" style="width:120px;">
                        <p class="description" id="bm-cost-desc-flat" <?php echo $pricing_mode === 'per_unit' ? 'style="display:none;"' : ''; ?>>
                            <?php esc_html_e('Jednorazowa opłata za rezerwację tego zasobu.', 'basemgmt'); ?>
                        </p>
                        <p class="description" id="bm-cost-desc-per-unit" <?php echo $pricing_mode !== 'per_unit' ? 'style="display:none;"' : ''; ?>>
                            <?php esc_html_e('Koszt za jedną sztukę. Kwota w finansach obozu = liczba_sztuk × ten_koszt.', 'basemgmt'); ?>
                        </p>
                    </td>
                </tr>
                <script>
                document.querySelectorAll('input[name="pricing_mode"]').forEach(function(r) {
                    r.addEventListener('change', function() {
                        var isPerUnit = document.getElementById('bm-pricing-per-unit').checked;
                        document.getElementById('bm-total-units-row').style.display = isPerUnit ? '' : 'none';
                        document.getElementById('bm-cost-desc-flat').style.display    = isPerUnit ? 'none' : '';
                        document.getElementById('bm-cost-desc-per-unit').style.display = isPerUnit ? '' : 'none';
                    });
                });
                </script>
                    <p class="description"><?php esc_html_e('0 = bez limitu', 'basemgmt'); ?></p></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Blokada globalna', 'basemgmt'); ?></th>
                    <td colspan="3">
                        <label><input type="checkbox" name="is_blocked" value="1" <?php checked($resource->is_blocked ?? 0, 1); ?>> <?php esc_html_e('Zablokowany (brak możliwości rezerwacji)', 'basemgmt'); ?></label>
                        <p class="description" style="margin-top:6px;"><?php esc_html_e('Powód (opcjonalny):', 'basemgmt'); ?></p>
                        <input type="text" name="block_reason" class="regular-text" value="<?php echo esc_attr($resource->block_reason ?? ''); ?>">
                    </td>
                </tr>
            </table>
            <?php submit_button($rid ? __('Zapisz zmiany', 'basemgmt') : __('Utwórz zasób', 'basemgmt')); ?>
        </form>
    </div>

    <?php if ($rid): ?>
    <!-- Maintenance blocks -->
    <div class="postbox" style="padding:16px 20px;max-width:700px;">
        <h2 class="hndle" style="padding:0 0 12px;"><?php esc_html_e('Blokady techniczne', 'basemgmt'); ?></h2>
        <p class="description"><?php esc_html_e('Blokady uniemożliwiają rezerwację w podanym przedziale czasowym (np. konserwacja, serwis).', 'basemgmt'); ?></p>

        <?php if ($blocks): ?>
        <table class="wp-list-table widefat fixed striped" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Od', 'basemgmt'); ?></th>
                    <th><?php esc_html_e('Do', 'basemgmt'); ?></th>
                    <th><?php esc_html_e('Powód', 'basemgmt'); ?></th>
                    <th style="width:80px;"><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blocks as $block):
                    $del_url = wp_nonce_url(admin_url('admin-post.php?action=bm_delete_resource_block&id=' . $block->id . '&resource_id=' . $rid), 'bm_delete_block_' . $block->id);
                ?>
                <tr>
                    <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($block->block_from))); ?></td>
                    <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($block->block_to))); ?></td>
                    <td><?php echo esc_html($block->reason ?: '—'); ?></td>
                    <td><a href="<?php echo esc_url($del_url); ?>" class="button button-small" data-bm-confirm="<?php esc_attr_e('Usunąć blokadę?', 'basemgmt'); ?>"><?php esc_html_e('Usuń', 'basemgmt'); ?></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_resource_block'); ?>
            <input type="hidden" name="action"      value="bm_save_resource_block">
            <input type="hidden" name="resource_id" value="<?php echo esc_attr($rid); ?>">
            <table class="form-table" style="margin:0;">
                <tr>
                    <th><label><?php esc_html_e('Blokada od *', 'basemgmt'); ?></label></th>
                    <td><input type="datetime-local" name="block_from" required></td>
                    <th><label><?php esc_html_e('Blokada do *', 'basemgmt'); ?></label></th>
                    <td><input type="datetime-local" name="block_to" required></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Powód', 'basemgmt'); ?></label></th>
                    <td colspan="3"><input type="text" name="reason" class="large-text"></td>
                </tr>
            </table>
            <button type="submit" class="button button-secondary" style="margin-top:8px;"><?php esc_html_e('Dodaj blokadę', 'basemgmt'); ?></button>
        </form>
    </div>
    <?php endif; ?>
</div>
