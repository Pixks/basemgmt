<?php
defined('ABSPATH') || exit;
/**
 * @var array  $reservation
 * @var string $resource_name
 * @var string $camp_name
 * @var string $admin_comment
 * @var array  $settings
 */
?>
<h2><?php esc_html_e('Rezerwacja zatwierdzona ✓', 'basemgmt'); ?></h2>
<p><?php esc_html_e('Twoja rezerwacja została zatwierdzona przez administratora ośrodka.', 'basemgmt'); ?></p>

<table class="meta-table">
  <tr><th><?php esc_html_e('Zasób:', 'basemgmt'); ?></th><td><?php echo esc_html($resource_name ?? '—'); ?></td></tr>
  <tr><th><?php esc_html_e('Obóz:', 'basemgmt'); ?></th><td><?php echo esc_html($camp_name ?? '—'); ?></td></tr>
  <tr><th><?php esc_html_e('Data:', 'basemgmt'); ?></th><td><?php echo esc_html(isset($reservation['res_date']) ? date_i18n('d.m.Y', strtotime($reservation['res_date'])) : '—'); ?></td></tr>
  <tr><th><?php esc_html_e('Godziny:', 'basemgmt'); ?></th><td><?php echo esc_html(($reservation['start_time'] ?? '') . ' – ' . ($reservation['end_time'] ?? '')); ?></td></tr>
  <tr><th><?php esc_html_e('Cel:', 'basemgmt'); ?></th><td><?php echo esc_html($reservation['purpose'] ?? '—'); ?></td></tr>
  <tr>
    <th><?php esc_html_e('Status:', 'basemgmt'); ?></th>
    <td><span class="status-badge status-approved"><?php esc_html_e('Zatwierdzona', 'basemgmt'); ?></span></td>
  </tr>
  <?php if ($admin_comment ?? ''): ?>
  <tr><th><?php esc_html_e('Komentarz administratora:', 'basemgmt'); ?></th><td><?php echo esc_html($admin_comment); ?></td></tr>
  <?php endif; ?>
</table>
