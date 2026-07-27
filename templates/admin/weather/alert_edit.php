<?php
defined('ABSPATH') || exit;
/**
 * @var object|null $alert   – null when creating new
 */
$is_edit     = ($alert !== null);
$title       = $is_edit ? __('Edytuj komunikat pogodowy', 'basemgmt') : __('Nowy komunikat pogodowy', 'basemgmt');
$valid_until = $is_edit && $alert->valid_until ? date('Y-m-d\TH:i', strtotime($alert->valid_until)) : '';
$alert_types = [
    'info'    => __('Informacja', 'basemgmt'),
    'warning' => __('Ostrzeżenie', 'basemgmt'),
    'danger'  => __('Niebezpieczeństwo', 'basemgmt'),
];
?>
<div class="wrap bm-wrap">
    <h1><?php echo esc_html($title); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-weather')); ?>" class="button" style="margin-bottom:16px;">
        &larr; <?php esc_html_e('Wróć', 'basemgmt'); ?>
    </a>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:600px;">
        <?php wp_nonce_field('bm_save_weather_alert'); ?>
        <input type="hidden" name="action"   value="bm_save_weather_alert">
        <input type="hidden" name="alert_id" value="<?php echo $is_edit ? esc_attr((string)$alert->id) : '0'; ?>">

        <table class="form-table">
            <tr>
                <th><label for="bm-alert-title"><?php esc_html_e('Tytuł *', 'basemgmt'); ?></label></th>
                <td><input type="text" id="bm-alert-title" name="title" value="<?php echo $is_edit ? esc_attr($alert->title) : ''; ?>" class="large-text" required></td>
            </tr>
            <tr>
                <th><label for="bm-alert-message"><?php esc_html_e('Treść *', 'basemgmt'); ?></label></th>
                <td><textarea id="bm-alert-message" name="message" class="large-text" rows="4" required><?php echo $is_edit ? esc_textarea($alert->message) : ''; ?></textarea></td>
            </tr>
            <tr>
                <th><label for="bm-alert-type"><?php esc_html_e('Typ', 'basemgmt'); ?></label></th>
                <td>
                    <select id="bm-alert-type" name="type">
                        <?php foreach ($alert_types as $val => $label): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php $is_edit ? selected($alert->type, $val) : ''; ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Aktywny', 'basemgmt'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php $is_edit ? checked((int)$alert->is_active, 1) : 'checked'; ?>>
                        <?php esc_html_e('Komunikat widoczny dla obozów', 'basemgmt'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Pilny', 'basemgmt'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="is_urgent" value="1" <?php $is_edit ? checked((int)$alert->is_urgent, 1) : ''; ?>>
                        <?php esc_html_e('Wyróżnij jako pilne ostrzeżenie', 'basemgmt'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="bm-alert-until"><?php esc_html_e('Ważny do', 'basemgmt'); ?></label></th>
                <td>
                    <input type="datetime-local" id="bm-alert-until" name="valid_until" value="<?php echo esc_attr($valid_until); ?>">
                    <p class="description"><?php esc_html_e('Zostaw puste, aby komunikat obowiązywał bezterminowo.', 'basemgmt'); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <?php submit_button($is_edit ? __('Zapisz zmiany', 'basemgmt') : __('Dodaj komunikat', 'basemgmt'), 'primary', 'submit', false); ?>
        </p>
    </form>
</div>
