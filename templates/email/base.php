<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html($subject ?? ''); ?></title>
<style>
  body { margin:0; padding:0; background:#f1f1f1; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; }
  .wrapper { width:100%; background:#f1f1f1; padding:24px 0; }
  .email-card { max-width:560px; margin:0 auto; background:#ffffff; border-radius:6px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.1); }
  .header { background: <?php echo esc_attr($settings['header_color']); ?>; padding:24px 32px; text-align:center; }
  .header img { max-height:50px; max-width:200px; }
  .header-title { color:#ffffff; font-size:20px; font-weight:700; margin:0; }
  .body { padding:28px 32px; color:#333333; font-size:15px; line-height:1.6; }
  .body h2 { margin-top:0; font-size:18px; color:#111; }
  .body a { color:<?php echo esc_attr($settings['header_color']); ?>; }
  .meta-table { width:100%; border-collapse:collapse; margin:16px 0; }
  .meta-table th { text-align:left; padding:6px 12px 6px 0; color:#666; font-weight:600; width:40%; font-size:13px; vertical-align:top; }
  .meta-table td { padding:6px 0; font-size:13px; vertical-align:top; }
  .status-badge { display:inline-block; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:700; }
  .status-pending   { background:#fff3cd; color:#856404; }
  .status-approved  { background:#d4edda; color:#155724; }
  .status-rejected  { background:#f8d7da; color:#721c24; }
  .status-cancelled { background:#e2e3e5; color:#383d41; }
  .footer { background:#f9f9f9; border-top:1px solid #e9e9e9; padding:16px 32px; text-align:center; font-size:12px; color:#888; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="email-card">
    <div class="header">
      <?php if ( ! empty($settings['header_html']) ): ?>
      <?php echo wp_kses_post($settings['header_html']); ?>
      <?php elseif ( ! empty($settings['logo_url']) ): ?>
      <img src="<?php echo esc_url($settings['logo_url']); ?>" alt="<?php echo esc_attr($settings['header_title']); ?>">
      <?php else: ?>
      <p class="header-title"><?php echo esc_html($settings['header_title']); ?></p>
      <?php endif; ?>
    </div>
    <div class="body">
      <?php echo $content; // pre-escaped in template ?>
    </div>
    <div class="footer">
      <?php echo wp_kses_post($settings['footer_text']); ?>
    </div>
  </div>
</div>
</body>
</html>
