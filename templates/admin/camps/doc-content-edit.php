<?php
/**
 * Template: doc-content-edit.php
 * Edit HTML content of a camp document or camp declaration.
 *
 * Variables:
 * @var \stdClass $doc   The camp_documents or camp_decl_docs row.
 * @var \stdClass $camp  The camp row.
 * @var string    $mode  'document' or 'declaration'
 */

defined('ABSPATH') || exit;

$is_decl   = ( $mode === 'declaration' );
$back_url  = admin_url("admin.php?page=basemgmt-camps&action=edit&id={$camp->id}#bm-section-documents");
$action    = $is_decl ? 'bm_finalize_camp_decl_doc' : 'bm_save_camp_doc_content';
$nonce_key = $action;
$editor_id = 'bm_doc_html_content';

$editor_settings = [
	'textarea_name' => 'html_content',
	'media_buttons' => false,
	'teeny'         => false,
	'quicktags'     => true,
	'tinymce'       => [
		'height' => 480,
	],
];
?>
<div class="wrap">
	<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
		<a href="<?php echo esc_url($back_url); ?>" class="button">&larr; <?php esc_html_e('Powrót do teczki', 'basemgmt'); ?></a>
		<h1 style="margin:0;flex:1;"><?php echo esc_html($doc->title); ?></h1>
		<?php if ( $is_decl ) : ?>
			<span class="bm-badge bm-badge--info"><?php esc_html_e('Deklaracja', 'basemgmt'); ?></span>
		<?php else : ?>
			<span class="bm-badge bm-badge--doctype-document"><?php esc_html_e('Dokument', 'basemgmt'); ?></span>
		<?php endif; ?>
	</div>

	<form id="bm-doc-content-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field($nonce_key); ?>
		<input type="hidden" name="action"  value="<?php echo esc_attr($action); ?>">
		<input type="hidden" name="camp_id" value="<?php echo esc_attr($camp->id); ?>">
		<?php if ( $is_decl ) : ?>
			<input type="hidden" name="decl_id" value="<?php echo esc_attr($doc->id); ?>">
		<?php else : ?>
			<input type="hidden" name="doc_id"  value="<?php echo esc_attr($doc->id); ?>">
		<?php endif; ?>

		<div class="postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php esc_html_e('Treść dokumentu', 'basemgmt'); ?></h2>
			</div>
			<div class="inside">
				<?php wp_editor($doc->html_content ?? '', $editor_id, $editor_settings); ?>
			</div>
		</div>

		<div style="display:flex;gap:10px;align-items:center;margin-top:16px;">
			<button type="submit" id="bm-doc-save-btn" class="button button-primary button-large">
				<?php if ( $is_decl ) : ?>
					<?php esc_html_e('Zatwierdź i generuj PDF', 'basemgmt'); ?>
				<?php else : ?>
					<?php esc_html_e('Zapisz treść', 'basemgmt'); ?>
				<?php endif; ?>
			</button>
			<a href="<?php echo esc_url($back_url); ?>" class="button button-large"><?php esc_html_e('Anuluj', 'basemgmt'); ?></a>
		</div>
	</form>
</div>

<?php if ( $is_decl ) : ?>
<script>
(function() {
	var form = document.getElementById('bm-doc-content-form');
	if ( ! form ) return;
	form.addEventListener('submit', function(e) {
		// After form submits and page reloads with notice, we open the print view.
		// We store a flag in sessionStorage to open print on redirect.
		sessionStorage.setItem('bm_open_print_decl_<?php echo esc_js((string) $doc->id); ?>', '1');
	});
})();
</script>
<?php endif; ?>
<?php
// Trigger print window if redirected back after finalize.
$open_print = sanitize_url(wp_unslash($_GET['bm_open_print'] ?? ''));
if ( ! empty($open_print) ) : ?>
<script>
(function() {
	var printUrl = <?php echo wp_json_encode($open_print); ?>;
	if ( printUrl ) {
		window.open(printUrl, '_blank');
	}
})();
</script>
<?php endif;
