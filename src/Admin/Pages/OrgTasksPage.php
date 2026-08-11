<?php
declare(strict_types=1);
namespace BaseMgmt\Admin\Pages;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Database\Schema;
defined('ABSPATH') || exit;

final class OrgTasksPage {
    public function render(): void {
        Capabilities::require_admin();
        $action = sanitize_key($_GET['action'] ?? '');
        $id = (int) ($_GET['id'] ?? 0);

        if (in_array($action, ['new', 'edit'], true)) {
            $task_tpl = $id ? $this->get_one($id) : null;
            include BASEMGMT_DIR . 'templates/admin/org/tasks/edit.php';
        } else {
            $templates = $this->get_all();
            include BASEMGMT_DIR . 'templates/admin/org/tasks/list.php';
        }
    }

    public function handle_save(): void {
        Capabilities::require_admin();
        check_admin_referer('bm_save_task_template');
        global $wpdb;
        $id = (int) ($_POST['task_tpl_id'] ?? 0);
        $data = [
            'title'         => sanitize_text_field(wp_unslash($_POST['title'] ?? '')),
            'description'   => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'priority'      => sanitize_key($_POST['priority'] ?? 'normal'),
            'auto_add'      => (int) ($_POST['auto_add'] ?? 0),
            'sort_order'    => (int) ($_POST['sort_order'] ?? 0),
            'email_subject' => sanitize_text_field(wp_unslash($_POST['email_subject'] ?? '')),
            'email_body'    => sanitize_textarea_field(wp_unslash($_POST['email_body'] ?? '')),
        ];
        if (empty($data['title'])) {
            AdminMenu::set_notice(__('Tytuł jest wymagany.', 'basemgmt'), 'error');
            $this->redirect($id ? "basemgmt-org-tasks&action=edit&id={$id}" : 'basemgmt-org-tasks&action=new');
            return;
        }
        if ($id > 0) {
            $wpdb->update(Schema::table('task_templates'), $data, ['id' => $id]);
        } else {
            $data['created_by'] = get_current_user_id();
            $wpdb->insert(Schema::table('task_templates'), $data);
            $id = (int) $wpdb->insert_id;
        }
        AdminMenu::set_notice(__('Szablon zadania zapisany.', 'basemgmt'));
        $this->redirect("basemgmt-org-tasks");
    }

    public function handle_delete(): void {
        Capabilities::require_admin();
        $id = (int) ($_GET['id'] ?? 0);
        check_admin_referer("bm_delete_task_template_{$id}");
        global $wpdb;
        $wpdb->delete(Schema::table('task_templates'), ['id' => $id]);
        AdminMenu::set_notice(__('Szablon usunięty.', 'basemgmt'));
        $this->redirect('basemgmt-org-tasks');
    }

    public static function get_all(): array {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . Schema::table('task_templates') . " ORDER BY sort_order ASC, id ASC") ?: [];
    }

    public static function get_auto_add(): array {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . Schema::table('task_templates') . " WHERE auto_add = 1 ORDER BY sort_order ASC") ?: [];
    }

    private function get_one(int $id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Schema::table('task_templates') . " WHERE id = %d", $id));
    }

    private function redirect(string $page): void {
        wp_safe_redirect(admin_url("admin.php?page={$page}"));
        exit;
    }
}
