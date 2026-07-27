<?php
defined('ABSPATH') || exit;
/**
 * @var array        $settings        – Open-Meteo location settings
 * @var array        $imgw_settings   – IMGW sync settings
 * @var string|null  $imgw_last_sync  – last sync timestamp
 * @var array        $imgw_last_log   – last sync log array
 * @var array|null   $weather         – cached weather data
 * @var array        $alerts          – all alerts (manual + IMGW)
 * @var array        $voivodeships    – voivodeship options
 * @var array        $all_counties    – all counties [code => [voivodeship_key, name]]
 */
$alert_types = [
    'info'    => __('Informacja', 'basemgmt'),
    'warning' => __('Ostrzeżenie', 'basemgmt'),
    'danger'  => __('Niebezpieczeństwo', 'basemgmt'),
];
$intervals = [
    'hourly'     => __('Co godzinę', 'basemgmt'),
    'twicedaily' => __('Dwa razy dziennie', 'basemgmt'),
    'daily'      => __('Raz dziennie', 'basemgmt'),
];
?>
<div class="wrap bm-wrap">
    <h1><?php esc_html_e('Pogoda', 'basemgmt'); ?></h1>

    <!-- ── Location + IMGW settings form ── -->
    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <h2 class="hndle" style="padding:0 0 12px;"><?php esc_html_e('Ustawienia', 'basemgmt'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bm_save_weather_settings'); ?>
            <input type="hidden" name="action" value="bm_save_weather_settings">

            <h3 style="margin-top:0;"><?php esc_html_e('Lokalizacja (Open-Meteo)', 'basemgmt'); ?></h3>
            <table class="form-table" style="margin:0;">
                <tr>
                    <th><label for="bm-lat"><?php esc_html_e('Szerokość geogr.', 'basemgmt'); ?></label></th>
                    <td><input type="text" id="bm-lat" name="latitude" value="<?php echo esc_attr($settings['latitude']); ?>" class="regular-text" placeholder="np. 52.2297"></td>
                </tr>
                <tr>
                    <th><label for="bm-lon"><?php esc_html_e('Długość geogr.', 'basemgmt'); ?></label></th>
                    <td><input type="text" id="bm-lon" name="longitude" value="<?php echo esc_attr($settings['longitude']); ?>" class="regular-text" placeholder="np. 21.0122"></td>
                </tr>
                <tr>
                    <th><label for="bm-loc"><?php esc_html_e('Nazwa miejsca', 'basemgmt'); ?></label></th>
                    <td><input type="text" id="bm-loc" name="location_name" value="<?php echo esc_attr($settings['location_name']); ?>" class="regular-text" placeholder="np. Ośrodek Zielony Bór"></td>
                </tr>
                <tr>
                    <th><label for="bm-tz"><?php esc_html_e('Strefa czasowa', 'basemgmt'); ?></label></th>
                    <td><input type="text" id="bm-tz" name="timezone" value="<?php echo esc_attr($settings['timezone']); ?>" class="regular-text"></td>
                </tr>
            </table>

            <hr style="margin:16px 0;">
            <h3 style="margin:0 0 8px;"><?php esc_html_e('Synchronizacja IMGW', 'basemgmt'); ?></h3>
            <p class="description" style="margin-bottom:12px;">
                <?php esc_html_e('IMGW (Instytut Meteorologii i Gospodarki Wodnej) udostępnia bezpłatne, publiczne ostrzeżenia meteorologiczne. Po włączeniu synchronizacji, plugin będzie automatycznie pobierał aktywne ostrzeżenia dla wybranego województwa.', 'basemgmt'); ?>
            </p>
            <table class="form-table" style="margin:0;">
                <tr>
                    <th><?php esc_html_e('Synchronizacja IMGW', 'basemgmt'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="imgw_enabled" value="1" <?php checked($imgw_settings['enabled']); ?>>
                            <?php esc_html_e('Włącz automatyczną synchronizację komunikatów IMGW', 'basemgmt'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-voiv"><?php esc_html_e('Województwo', 'basemgmt'); ?></label></th>
                    <td>
                        <select id="bm-voiv" name="voivodeship" onchange="bmFilterCounties(this.value)">
                            <?php foreach ($voivodeships as $val => $label): ?>
                            <option value="<?php echo esc_attr($val); ?>" <?php selected($imgw_settings['voivodeship'], $val); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Zostaw puste, aby pobierać ostrzeżenia dla całej Polski.', 'basemgmt'); ?></p>
                    </td>
                </tr>
                <tr id="bm-county-row">
                    <th><label for="bm-county"><?php esc_html_e('Powiat', 'basemgmt'); ?></label></th>
                    <td>
                        <select id="bm-county" name="county_teryt">
                            <?php
                            $sel_voiv    = $imgw_settings['voivodeship'];
                            $sel_county  = $imgw_settings['county_teryt'] ?? '';
                            echo '<option value="">' . esc_html__('— cały region —', 'basemgmt') . '</option>';
                            foreach ($all_counties as $code => [$voiv, $cname]):
                                if ($sel_voiv && $voiv !== $sel_voiv) continue;
                            ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($sel_county, $code); ?>>
                                <?php echo esc_html($cname); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Opcjonalnie zawęź do konkretnego powiatu. Wymaga wybrania województwa.', 'basemgmt'); ?></p>
                    </td>
                </tr>
                <?php
                // Output all counties as JSON for JS-driven filtering.
                $counties_json = [];
                foreach ($all_counties as $code => [$voiv, $cname]) {
                    $counties_json[] = ['code' => $code, 'voiv' => $voiv, 'name' => $cname];
                }
                ?>
                <script>
                var bmAllCounties = <?php echo wp_json_encode($counties_json); ?>;
                function bmFilterCounties(voiv) {
                    var sel = document.getElementById('bm-county');
                    var prev = sel.value;
                    sel.innerHTML = '<option value=""><?php echo esc_js(__('— cały region —', 'basemgmt')); ?></option>';
                    bmAllCounties.forEach(function(c) {
                        if (!voiv || c.voiv === voiv) {
                            var opt = document.createElement('option');
                            opt.value = c.code;
                            opt.text  = c.name;
                            if (c.code === prev) opt.selected = true;
                            sel.appendChild(opt);
                        }
                    });
                    var row = document.getElementById('bm-county-row');
                    if (row) row.style.display = voiv ? '' : 'none';
                }
                // On page load: hide county row if no voivodeship selected.
                document.addEventListener('DOMContentLoaded', function() {
                    var voiv = document.getElementById('bm-voiv').value;
                    var row = document.getElementById('bm-county-row');
                    if (row) row.style.display = voiv ? '' : 'none';
                });
                </script>
                <tr>
                    <th><label for="bm-interval"><?php esc_html_e('Interwał synchronizacji', 'basemgmt'); ?></label></th>
                    <td>
                        <select id="bm-interval" name="sync_interval">
                            <?php foreach ($intervals as $val => $label): ?>
                            <option value="<?php echo esc_attr($val); ?>" <?php selected($imgw_settings['sync_interval'], $val); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="bm-imgw-url"><?php esc_html_e('URL API IMGW (opcjonalnie)', 'basemgmt'); ?></label></th>
                    <td>
                        <input type="url" id="bm-imgw-url" name="custom_api_url" value="<?php echo esc_attr($imgw_settings['custom_api_url']); ?>" class="large-text" placeholder="https://danepubliczne.imgw.pl/api/data/warno">
                        <p class="description"><?php esc_html_e('Zostaw puste, aby używać domyślnego adresu API IMGW.', 'basemgmt'); ?></p>
                    </td>
                </tr>
            </table>

            <?php if ($imgw_last_sync): ?>
            <p style="margin-top:12px;color:#555;">
                <?php esc_html_e('Ostatnia synchronizacja IMGW:', 'basemgmt'); ?>
                <strong><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($imgw_last_sync))); ?></strong>
                <?php if ($imgw_last_log): ?>
                | <?php echo esc_html(sprintf('Pobrano: %d, Dodano: %d, Zaktualizowano: %d', $imgw_last_log['fetched'] ?? 0, $imgw_last_log['inserted'] ?? 0, $imgw_last_log['updated'] ?? 0)); ?>
                <?php if (!empty($imgw_last_log['error'])): ?>
                | <span style="color:#c0392b;"><?php echo esc_html($imgw_last_log['error']); ?></span>
                <?php endif; ?>
                <?php endif; ?>
            </p>
            <?php endif; ?>

            <p class="submit" style="padding:12px 0 0;display:flex;gap:8px;align-items:center;">
                <?php submit_button(__('Zapisz ustawienia', 'basemgmt'), 'primary', 'submit', false); ?>
                <?php if ($imgw_settings['enabled']): ?>
                <span style="color:#888;"><?php esc_html_e('lub', 'basemgmt'); ?></span>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bm_sync_imgw'), 'bm_sync_imgw')); ?>"
                   class="button button-secondary"
                   onclick="this.textContent='Synchronizuję…'">
                    🔄 <?php esc_html_e('Synchronizuj teraz', 'basemgmt'); ?>
                </a>
                <?php endif; ?>
            </p>
        </form>
    </div>

    <!-- ── Current weather preview ── -->
    <?php if ($weather): ?>
    <div class="postbox" style="max-width:700px;padding:16px 20px;margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <h2 class="hndle" style="padding:0;"><?php esc_html_e('Aktualna pogoda', 'basemgmt'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0;">
                <?php wp_nonce_field('bm_refresh_weather'); ?>
                <input type="hidden" name="action" value="bm_refresh_weather">
                <button type="submit" class="button button-secondary"><?php esc_html_e('Odśwież', 'basemgmt'); ?></button>
            </form>
        </div>
        <?php $c = $weather['current']; ?>
        <p style="font-size:32px;margin:0;"><?php echo esc_html($c['icon'] . ' ' . $c['label']); ?></p>
        <p style="font-size:24px;font-weight:bold;margin:4px 0;"><?php echo esc_html((string)$c['temperature']); ?>°C</p>
        <p style="color:#555;">
            💨 <?php echo esc_html((string)$c['windspeed']); ?> km/h &nbsp;
            💧 <?php echo esc_html((string)$c['humidity']); ?>% &nbsp;
            🌧 <?php echo esc_html((string)$c['precipitation']); ?> mm
        </p>
        <p style="color:#888;font-size:12px;"><?php esc_html_e('Pobrano:', 'basemgmt'); ?> <?php echo esc_html($c['fetched_at']); ?></p>

        <?php if (!empty($weather['forecast'])): ?>
        <hr>
        <h3><?php esc_html_e('Prognoza 3-dniowa', 'basemgmt'); ?></h3>
        <div style="display:flex;gap:12px;">
            <?php foreach ($weather['forecast'] as $day): ?>
            <div style="flex:1;text-align:center;background:#f9f9f9;border-radius:6px;padding:10px;">
                <div style="font-weight:bold;"><?php echo esc_html(date_i18n('D d.m', strtotime($day['date']))); ?></div>
                <div style="font-size:24px;"><?php echo esc_html($day['icon']); ?></div>
                <div style="font-size:12px;"><?php echo esc_html($day['label']); ?></div>
                <div><?php echo esc_html((string)$day['temp_max']); ?>° / <?php echo esc_html((string)$day['temp_min']); ?>°</div>
                <div style="font-size:11px;color:#555;">🌧 <?php echo esc_html((string)$day['precipitation']); ?> mm</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif (!empty($settings['latitude'])): ?>
    <div class="notice notice-warning inline" style="margin-bottom:16px;padding:8px 12px;">
        <p><?php esc_html_e('Nie udało się pobrać danych pogodowych. Sprawdź koordynaty lub spróbuj odświeżyć.', 'basemgmt'); ?></p>
    </div>
    <?php else: ?>
    <div class="notice notice-info inline" style="margin-bottom:16px;padding:8px 12px;">
        <p><?php esc_html_e('Podaj koordynaty lokalizacji, aby włączyć podgląd pogody.', 'basemgmt'); ?></p>
    </div>
    <?php endif; ?>

    <!-- ── Alerts list ── -->
    <div class="postbox" style="padding:16px 20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <h2 class="hndle" style="padding:0;"><?php esc_html_e('Komunikaty pogodowe', 'basemgmt'); ?></h2>
            <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-weather&bm_action=new_alert')); ?>" class="button button-primary">
                + <?php esc_html_e('Dodaj ręczny komunikat', 'basemgmt'); ?>
            </a>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:40%"><?php esc_html_e('Tytuł', 'basemgmt'); ?></th>
                    <th><?php esc_html_e('Źródło', 'basemgmt'); ?></th>
                    <th><?php esc_html_e('Typ', 'basemgmt'); ?></th>
                    <th><?php esc_html_e('Status', 'basemgmt'); ?></th>
                    <th><?php esc_html_e('Pilny', 'basemgmt'); ?></th>
                    <th><?php esc_html_e('Ważny do', 'basemgmt'); ?></th>
                    <th><?php esc_html_e('Akcje', 'basemgmt'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alerts)): ?>
                <tr><td colspan="7" style="text-align:center;color:#888;"><?php esc_html_e('Brak komunikatów.', 'basemgmt'); ?></td></tr>
                <?php else: ?>
                <?php foreach ($alerts as $a):
                    $type_label    = $alert_types[$a->type] ?? $a->type;
                    $is_imgw       = ($a->source ?? 'manual') === 'imgw';
                    $source_label  = $is_imgw ? '<span style="background:#e8f4fd;color:#1a73e8;padding:2px 6px;border-radius:3px;font-size:11px;">IMGW</span>' : '<span style="background:#f0f0f0;color:#555;padding:2px 6px;border-radius:3px;font-size:11px;">' . esc_html__('Ręczny', 'basemgmt') . '</span>';
                    $delete_url    = $is_imgw ? '' : wp_nonce_url(
                        admin_url('admin-post.php?action=bm_delete_weather_alert&id=' . $a->id),
                        'bm_delete_alert_' . $a->id
                    );
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($a->title); ?></strong>
                        <?php if ($a->message): ?>
                        <p style="margin:2px 0 0;font-size:12px;color:#555;white-space:pre-line;"><?php echo esc_html(mb_substr($a->message, 0, 120)); ?><?php echo mb_strlen($a->message) > 120 ? '…' : ''; ?></p>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $source_label; ?></td>
                    <td><?php echo esc_html($type_label); ?></td>
                    <td><?php echo $a->is_active ? '<span style="color:#155724">✓ ' . esc_html__('Aktywny', 'basemgmt') . '</span>' : '<span style="color:#888">' . esc_html__('Nieaktywny', 'basemgmt') . '</span>'; ?></td>
                    <td><?php echo $a->is_urgent ? '🔴 ' . esc_html__('Tak', 'basemgmt') : '—'; ?></td>
                    <td><?php echo $a->valid_until ? esc_html(date_i18n('d.m.Y H:i', strtotime($a->valid_until))) : '—'; ?></td>
                    <td>
                        <?php if (!$is_imgw): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-weather&bm_action=edit_alert&id=' . $a->id)); ?>" class="button button-small"><?php esc_html_e('Edytuj', 'basemgmt'); ?></a>
                        <a href="<?php echo esc_url($delete_url); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Usunąć komunikat?', 'basemgmt'); ?>')"><?php esc_html_e('Usuń', 'basemgmt'); ?></a>
                        <?php else: ?>
                        <span style="color:#888;font-size:12px;"><?php esc_html_e('Zarządzany przez IMGW', 'basemgmt'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
