<?php
defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html($title); ?> – <?php echo esc_html($formatted_date); ?></title>
<style>
* { box-sizing: border-box; }
body { margin: 0; background: #eef2f7; color: #111827; font: 14px/1.5 Arial, sans-serif; }
.page { max-width: 1100px; margin: 0 auto; padding: 24px; }
.toolbar { display:flex; gap:12px; margin-bottom:16px; }
.toolbar button { padding:10px 16px; font-size:14px; cursor:pointer; }
.sheet { background:#fff; border-radius:12px; box-shadow:0 8px 30px rgba(15,23,42,.08); padding:24px 28px 32px; }
.brand { display:flex; align-items:center; gap:18px; border-bottom:3px solid <?php echo esc_attr($settings['accent_color']); ?>; padding-bottom:16px; margin-bottom:20px; }
.brand-logo img { max-width:120px; max-height:72px; display:block; }
.brand-title { margin:0; font-size:28px; line-height:1.2; }
.brand-subtitle { margin:6px 0 0; color:#4b5563; }
.doc-footer { margin-top:24px; padding-top:12px; border-top:1px solid #e5e7eb; font-size:12px; color:#6b7280; }
h1 { font-size: 24px; margin: 0 0 8px; }
h2 { font-size: 16px; margin: 18px 0 8px; background: #f8fafc; padding: 8px 12px; border-left: 4px solid <?php echo esc_attr($settings['accent_color']); ?>; }
.meta { color:#4b5563; font-size:12px; margin-bottom:18px; }
table { width:100%; border-collapse:collapse; margin-bottom:16px; }
th, td { border:1px solid #d1d5db; padding:7px 9px; text-align:left; vertical-align:top; }
th { background:#f8fafc; font-weight:700; }
.pill { display:inline-block; padding:2px 7px; border-radius:999px; font-size:11px; background:#e0f2fe; color:#0c4a6e; }
.warning { color:#b45309; }
.danger { color:#b91c1c; font-weight:700; }
.total-row { font-weight:700; background:#ecfdf5; }
@media print {
	body { background:#fff; }
	.page { max-width:none; padding:0; }
	.toolbar { display:none; }
	.sheet { box-shadow:none; border-radius:0; padding:0; }
}
</style>
</head>
<body>
<div class="page">
	<div class="toolbar">
		<button onclick="window.print()">🖨 <?php esc_html_e('Drukuj / Zapisz PDF', 'basemgmt'); ?></button>
		<button onclick="window.close()"><?php esc_html_e('Zamknij', 'basemgmt'); ?></button>
	</div>

	<div class="sheet">
		<div class="brand">
			<?php if (! empty($settings['logo_url'])): ?>
			<div class="brand-logo">
				<img src="<?php echo esc_url($settings['logo_url']); ?>" alt="<?php echo esc_attr($settings['header_title']); ?>">
			</div>
			<?php endif; ?>
			<div>
				<p class="brand-title"><?php echo esc_html($settings['header_title']); ?></p>
				<?php if (! empty($settings['header_subtitle'])): ?>
				<p class="brand-subtitle"><?php echo esc_html($settings['header_subtitle']); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php if (! empty($settings['footer_text'])): ?>
		<div class="doc-footer"><?php echo esc_html($settings['footer_text']); ?></div>
		<?php endif; ?>
	</div>
</div>
</body>
</html>
