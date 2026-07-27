<?php
/**
 * Shared item form fields (used for both new and edit).
 * @var object|null $item      – existing item or null
 * @var array       $categories
 * @var array       $item_status_labels
 */
defined('ABSPATH') || exit;
?>
<table class="form-table" style="margin:0 0 8px;">
    <tr>
        <th><label><?php esc_html_e('Od godz.', 'basemgmt'); ?></label></th>
        <td><input type="time" name="time_from" value="<?php echo esc_attr($item->time_from ?? '08:00'); ?>"></td>
        <th><label><?php esc_html_e('Do godz.', 'basemgmt'); ?></label></th>
        <td><input type="time" name="time_to" value="<?php echo esc_attr($item->time_to ?? ''); ?>"></td>
    </tr>
    <tr>
        <th><label><?php esc_html_e('Tytuł *', 'basemgmt'); ?></label></th>
        <td colspan="3"><input type="text" name="item_title" class="large-text" required value="<?php echo esc_attr($item->title ?? ''); ?>"></td>
    </tr>
    <tr>
        <th><label><?php esc_html_e('Opis', 'basemgmt'); ?></label></th>
        <td colspan="3"><textarea name="description" rows="2" class="large-text"><?php echo esc_textarea($item->description ?? ''); ?></textarea></td>
    </tr>
    <tr>
        <th><label><?php esc_html_e('Kategoria', 'basemgmt'); ?></label></th>
        <td>
            <select name="category">
                <?php foreach ($categories as $val => $label): ?>
                <option value="<?php echo esc_attr($val); ?>" <?php selected($item->category ?? \BaseMgmt\Modules\Schedule\ScheduleRepository::CAT_INNE, $val); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <th><label><?php esc_html_e('Status', 'basemgmt'); ?></label></th>
        <td>
            <select name="item_status">
                <?php foreach ($item_status_labels as $val => $label): ?>
                <option value="<?php echo esc_attr($val); ?>" <?php selected($item->item_status ?? \BaseMgmt\Modules\Schedule\ScheduleRepository::ITEM_ACTIVE, $val); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th><?php esc_html_e('Opcje', 'basemgmt'); ?></th>
        <td colspan="3">
            <label><input type="checkbox" name="is_mandatory" value="1" <?php checked($item->is_mandatory ?? 0, 1); ?>> <?php esc_html_e('Obowiązkowa', 'basemgmt'); ?></label>&nbsp;
            <label><input type="checkbox" name="is_new_today" value="1" <?php checked($item->is_new_today ?? 0, 1); ?>> <?php esc_html_e('Nowe na dziś', 'basemgmt'); ?></label>&nbsp;
            <label><input type="checkbox" name="is_updated_today" value="1" <?php checked($item->is_updated_today ?? 0, 1); ?>> <?php esc_html_e('Zaktualizowane dziś', 'basemgmt'); ?></label>
        </td>
    </tr>
    <tr>
        <th><label><?php esc_html_e('Kolejność', 'basemgmt'); ?></label></th>
        <td><input type="number" name="sort_order" value="<?php echo esc_attr($item->sort_order ?? 0); ?>" min="0" style="width:80px;"></td>
    </tr>
</table>
