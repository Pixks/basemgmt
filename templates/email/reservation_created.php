<?php
defined('ABSPATH') || exit;
/**
 * Email template: reservation_created
 * @var array  $reservation  – reservation data array
 * @var string $resource_name
 * @var string $camp_name
 * @var bool   $is_admin     – true when sending to admin, false for camp
 * @var array  $settings     – EmailService settings
 */
?>
<?php if ($is_admin ?? false): ?>
<h2><?php esc_html_e('Nowa prośba o rezerwację', 'basemgmt'); ?></h2>
<p><?php esc_html_e('Złożono nową prośbę o rezerwację zasobu. Przejdź do panelu, aby ją zatwierdzić lub odrzucić.', 'basemgmt'); ?></p>
<?php else: ?>
<h2><?php esc_html_e('Potwierdzenie złożenia rezerwacji', 'basemgmt'); ?></h2>
<p><?php esc_html_e('Twoja prośba o rezerwację została przyjęta i oczekuje na zatwierdzenie przez administratora.', 'basemgmt'); ?></p>
<?php endif; ?>

<table class="meta-table">
  <tr><th><?php esc_html_e('Zasób:', 'basemgmt'); ?></th><td><?php echo esc_html($resource_name ?? '—'); ?></td></tr>
  <tr><th><?php esc_html_e('Obóz:', 'basemgmt'); ?></th><td><?php echo esc_html($camp_name ?? '—'); ?></td></tr>
  <tr><th><?php esc_html_e('Data:', 'basemgmt'); ?></th><td><?php echo esc_html(isset($reservation['res_date']) ? date_i18n('d.m.Y', strtotime($reservation['res_date'])) : '—'); ?></td></tr>
  <tr><th><?php esc_html_e('Godziny:', 'basemgmt'); ?></th><td><?php echo esc_html(($reservation['start_time'] ?? '') . ' – ' . ($reservation['end_time'] ?? '')); ?></td></tr>
  <tr><th><?php esc_html_e('Cel:', 'basemgmt'); ?></th><td><?php echo esc_html($reservation['purpose'] ?? '—'); ?></td></tr>
  <tr>
    <th><?php esc_html_e('Status:', 'basemgmt'); ?></th>
    <td><span class="status-badge status-pending"><?php esc_html_e('Oczekująca', 'basemgmt'); ?></span></td>
  </tr>
</table>
<?php if ($is_admin ?? false): ?>
<p><a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reservations&filter_status=pending')); ?>"><?php esc_html_e('Zarządzaj rezerwacjami →', 'basemgmt'); ?></a></p>
<?php endif; ?>
