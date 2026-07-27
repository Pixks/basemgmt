<?php
defined('ABSPATH') || exit;
/**
 * @var array  $reservation
 * @var string $resource_name
 * @var string $camp_name
 * @var bool   $cancelled_by_admin
 * @var array  $settings
 */
?>
<h2><?php esc_html_e('Rezerwacja anulowana', 'basemgmt'); ?></h2>
<?php if ($cancelled_by_admin ?? false): ?>
<p><?php esc_html_e('Twoja rezerwacja została anulowana przez administratora ośrodka.', 'basemgmt'); ?></p>
<?php else: ?>
<p><?php esc_html_e('Rezerwacja została anulowana na Twoje życzenie.', 'basemgmt'); ?></p>
<?php endif; ?>

<table class="meta-table">
  <tr><th><?php esc_html_e('Zasób:', 'basemgmt'); ?></th><td><?php echo esc_html($resource_name ?? '—'); ?></td></tr>
  <tr><th><?php esc_html_e('Data:', 'basemgmt'); ?></th><td><?php echo esc_html(isset($reservation['res_date']) ? date_i18n('d.m.Y', strtotime($reservation['res_date'])) : '—'); ?></td></tr>
  <tr><th><?php esc_html_e('Godziny:', 'basemgmt'); ?></th><td><?php echo esc_html(($reservation['start_time'] ?? '') . ' – ' . ($reservation['end_time'] ?? '')); ?></td></tr>
  <tr>
    <th><?php esc_html_e('Status:', 'basemgmt'); ?></th>
    <td><span class="status-badge status-cancelled"><?php esc_html_e('Anulowana', 'basemgmt'); ?></span></td>
  </tr>
</table>
