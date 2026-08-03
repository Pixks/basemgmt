<?php
defined('ABSPATH') || exit;
/**
 * Email template editor view.
 *
 * @var string $slug           – template slug
 * @var array  $tpl_def        – registry entry: label, default_subject, variables, default_html
 * @var string $current_subject
 * @var string $current_html
 * @var bool   $is_customised  – true when DB override exists
 */

use BaseMgmt\Core\EmailTemplateRepository;

$registry   = EmailTemplateRepository::get_registry();
$list_url   = admin_url('admin.php?page=basemgmt-settings');
$editor_id  = 'bm_template_html';

// Enqueue CodeMirror HTML editor (built into WordPress core since 4.9).
$cm_settings = wp_enqueue_code_editor(['type' => 'text/html', 'codemirror' => ['lineNumbers' => true, 'lineWrapping' => true]]);
wp_enqueue_script('wp-theme-plugin-editor');
wp_enqueue_style('wp-codemirror');
?>
<div class="wrap bm-admin-wrap">
    <h1>
        <a href="<?php echo esc_url($list_url); ?>" style="text-decoration:none;color:#1d2327;font-size:0.85em;font-weight:400;">
            ← <?php esc_html_e('Ustawienia', 'basemgmt'); ?>
        </a>
        <span style="color:#ccc;margin:0 6px;">/</span>
        <?php echo esc_html($tpl_def['label']); ?>
        <?php if ($is_customised): ?>
            <span style="font-size:0.65em;font-weight:400;color:#2271b1;vertical-align:middle;">
                ● <?php esc_html_e('Customised', 'basemgmt'); ?>
            </span>
        <?php endif; ?>
    </h1>

    <?php if ($is_customised): ?>
    <p class="description" style="margin-bottom:16px;">
        <?php esc_html_e('Używasz własnego szablonu. Zapisane nadpisuje domyślny.', 'basemgmt'); ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
            <?php wp_nonce_field("bm_reset_template_$slug", '_wpnonce', true, true); ?>
            <input type="hidden" name="action" value="bm_reset_email_template">
            <input type="hidden" name="slug"   value="<?php echo esc_attr($slug); ?>">
            <button type="submit" class="button-link" style="color:#b32d2e;"
                    onclick="return confirm('<?php esc_attr_e('Przywrócić domyślny szablon? Twoje zmiany zostaną usunięte.', 'basemgmt'); ?>')">
                <?php esc_html_e('Przywróć domyślny', 'basemgmt'); ?>
            </button>
        </form>
    </p>
    <?php endif; ?>

    <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">

        <!-- ── Editor form ── -->
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="flex:1;min-width:560px;">
            <?php wp_nonce_field('bm_save_email_template'); ?>
            <input type="hidden" name="action"        value="bm_save_email_template">
            <input type="hidden" name="template_slug" value="<?php echo esc_attr($slug); ?>">

            <!-- Subject -->
            <div style="margin-bottom:14px;">
                <label for="bm_tpl_subject" style="display:block;font-weight:600;margin-bottom:4px;">
                    <?php esc_html_e('Temat wiadomości', 'basemgmt'); ?>
                </label>
                <input type="text" id="bm_tpl_subject" name="template_subject" class="large-text"
                       value="<?php echo esc_attr($current_subject); ?>"
                       placeholder="<?php echo esc_attr($tpl_def['default_subject']); ?>">
                <p class="description"><?php esc_html_e('Możesz użyć zmiennych {{zasob}}, {{oboz}} itp.', 'basemgmt'); ?></p>
            </div>

            <!-- HTML body -->
            <div style="margin-bottom:14px;">
                <label for="<?php echo esc_attr($editor_id); ?>" style="display:block;font-weight:600;margin-bottom:4px;">
                    <?php esc_html_e('Treść emaila (HTML)', 'basemgmt'); ?>
                </label>
                <textarea id="<?php echo esc_attr($editor_id); ?>" name="template_html"
                          rows="24" class="large-text code"
                          style="font-family:monospace;font-size:13px;width:100%;"><?php echo esc_textarea($current_html); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Tylko treść wewnętrzna – nagłówek i stopka emaila dodawane są automatycznie z ustawień.', 'basemgmt'); ?>
                </p>
            </div>

            <div style="display:flex;gap:10px;align-items:center;">
                <button type="submit" class="button button-primary"><?php esc_html_e('Zapisz szablon', 'basemgmt'); ?></button>
                <a href="<?php echo esc_url($list_url); ?>" class="button"><?php esc_html_e('Anuluj', 'basemgmt'); ?></a>
            </div>
        </form>

        <!-- ── Variable reference ── -->
        <div style="min-width:240px;max-width:300px;">
            <div class="postbox" style="padding:14px 18px;">
                <h3 style="margin:0 0 12px;font-size:13px;text-transform:uppercase;color:#6b7280;letter-spacing:.05em;">
                    <?php esc_html_e('Dostępne zmienne', 'basemgmt'); ?>
                </h3>
                <p class="description" style="margin:0 0 10px;">
                    <?php esc_html_e('Kliknij zmienną, aby wkleić ją do edytora.', 'basemgmt'); ?>
                </p>
                <ul style="margin:0;padding:0;list-style:none;">
                    <?php foreach ($tpl_def['variables'] as $token => $desc): ?>
                    <li style="margin-bottom:8px;">
                        <button type="button" class="bm-var-btn button button-small"
                                data-token="<?php echo esc_attr($token); ?>"
                                style="font-family:monospace;font-size:12px;">
                            <?php echo esc_html($token); ?>
                        </button>
                        <span style="display:block;font-size:11px;color:#6b7280;margin-top:2px;">
                            <?php echo esc_html($desc); ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Template navigation -->
            <div class="postbox" style="padding:14px 18px;margin-top:12px;">
                <h3 style="margin:0 0 10px;font-size:13px;text-transform:uppercase;color:#6b7280;letter-spacing:.05em;">
                    <?php esc_html_e('Inne szablony', 'basemgmt'); ?>
                </h3>
                <ul style="margin:0;padding:0;list-style:none;">
                    <?php foreach ($registry as $s => $def): ?>
                    <li style="margin-bottom:6px;">
                        <?php if ($s === $slug): ?>
                            <strong><?php echo esc_html($def['label']); ?></strong>
                        <?php else: ?>
                            <a href="<?php echo esc_url(admin_url("admin.php?page=basemgmt-settings&edit_template=$s")); ?>">
                                <?php echo esc_html($def['label']); ?>
                            </a>
                        <?php endif; ?>
                        <?php if (EmailTemplateRepository::get_saved($s)): ?>
                            <span style="font-size:10px;color:#2271b1;">●</span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

    </div><!-- /flex -->
</div>

<script>
(function() {
    // Initialise CodeMirror on the textarea.
    var cmSettings = <?php echo wp_json_encode($cm_settings ?: new stdClass()); ?>;
    if (typeof wp !== 'undefined' && wp.codeEditor && Object.keys(cmSettings).length) {
        var editor = wp.codeEditor.initialize(document.getElementById('<?php echo esc_js($editor_id); ?>'), cmSettings);

        // "Insert variable" buttons.
        document.querySelectorAll('.bm-var-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var token = this.dataset.token;
                if (editor && editor.codemirror) {
                    editor.codemirror.replaceSelection(token);
                    editor.codemirror.focus();
                } else {
                    // Fallback: plain textarea.
                    var ta = document.getElementById('<?php echo esc_js($editor_id); ?>');
                    var s = ta.selectionStart, e = ta.selectionEnd;
                    ta.value = ta.value.substring(0, s) + token + ta.value.substring(e);
                    ta.focus();
                    ta.selectionStart = ta.selectionEnd = s + token.length;
                }
            });
        });
    } else {
        // CodeMirror not available – still wire up insert buttons.
        document.querySelectorAll('.bm-var-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var token = this.dataset.token;
                var ta = document.getElementById('<?php echo esc_js($editor_id); ?>');
                var s = ta.selectionStart, e = ta.selectionEnd;
                ta.value = ta.value.substring(0, s) + token + ta.value.substring(e);
                ta.focus();
                ta.selectionStart = ta.selectionEnd = s + token.length;
            });
        });
    }
})();
</script>
