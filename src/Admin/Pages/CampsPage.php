<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Core\OperationLogger;
use BaseMgmt\Database\Schema;
use BaseMgmt\Modules\Camps\CampCaseRepository;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Camps\CampWorkflowAutomationRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for managing camps (Obozy).
 */
final class CampsPage {

	public function render(): void {
		Capabilities::require_admin();
		$this->ensure_tables_ready();

		if ( isset($_GET['bm_create_tables']) ) {
			if ( ! current_user_can('manage_options') ) {
				wp_die(esc_html__('Brak uprawnień do aktualizacji schematu.', 'basemgmt'));
			}
			check_admin_referer('bm_create_tables');
			Schema::create_tables();
			CampCaseRepository::invalidate_tables_ready_cache();
			AdminMenu::set_notice(__('Tabele zostały utworzone / zaktualizowane.', 'basemgmt'));
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-camps'));
			exit;
		}

		$action = sanitize_key($_GET['action'] ?? 'list');
		$id     = (int) ($_GET['id'] ?? 0);

		match ($action) {
			'new'      => $this->render_edit_form(null),
			'edit'     => $this->render_edit_form(CampRepository::get($id)),
			'task_edit'=> $this->render_task_edit((int) ($_GET['task_id'] ?? 0), $id),
			'task_new' => $this->render_task_edit(0, $id),
			'doc_view' => $this->render_doc_view($id, (int) ($_GET['doc_id'] ?? 0)),
			default    => $this->render_list(),
		};
	}

	private function render_list(): void {
		$status          = sanitize_key($_GET['filter_status'] ?? '');
		$process_stage   = sanitize_key($_GET['filter_stage'] ?? '');
		$readiness       = sanitize_key($_GET['filter_readiness'] ?? '');
		$needs_attention = ! empty($_GET['filter_attention']) ? 1 : 0;
		$search          = sanitize_text_field($_GET['s'] ?? '');
		$page            = max(1, (int) ($_GET['paged'] ?? 1));

		$args  = [
			'status'          => $status,
			'process_stage'   => $process_stage,
			'readiness'       => $readiness,
			'needs_attention' => $needs_attention,
			'search'          => $search,
			'per_page'        => 20,
			'page'            => $page,
		];
		$camps = CampRepository::get_all($args);
		$total = CampRepository::count($args);
		$pages = (int) ceil(max(1, $total) / 20);

		$statuses       = [
			'all'      => __('Wszystkie', 'basemgmt'),
			'active'   => __('Aktywne', 'basemgmt'),
			'ended'    => __('Zakończone', 'basemgmt'),
			'archived' => __('Archiwalne', 'basemgmt'),
		];
		$stage_options  = CampCaseRepository::process_stages();
		$risk_levels    = CampCaseRepository::risk_levels();
		$readiness_map  = [
			''            => __('Dowolna gotowość', 'basemgmt'),
			'not_started' => __('Nie rozpoczęto', 'basemgmt'),
			'in_progress' => __('W przygotowaniu', 'basemgmt'),
			'ready'       => __('Gotowe', 'basemgmt'),
			'overdue'     => __('Po terminie', 'basemgmt'),
		];

		include BASEMGMT_DIR . 'templates/admin/camps/list.php';
	}

	private function render_edit_form(?object $camp): void {
		$workflow_view = sanitize_key($_GET['workflow_view'] ?? 'all');
		$camp_id       = (int) ($camp->id ?? 0); // local alias used for data fetching
		$case          = null;
		$organizer     = null;
		$prearrival    = null;
		$checklist     = CampCaseRepository::default_checklist_rows();
		$history       = [];
		$workflow_events = [];
		$recent_workflow_events = [];
		$open_tasks     = [];
		$recent_activity = [];
		$module_summary = [];
		$readiness     = ['total' => 0, 'done' => 0, 'overdue' => 0, 'percent' => 0];
		$future_counts = [
			'documents' => 0,
			'payments' => 0,
			'actuals' => 0,
			'pricing' => 0,
			'settlements' => 0,
			'issues' => 0,
			'closures' => 0,
		];

		if ( $camp ) {
			// Throttle: evaluate at most once every 15 minutes per camp to avoid redundant DB writes on every page view.
			$throttle_key = 'bm_wf_eval_' . (int) $camp->id;
			if ( false === get_transient($throttle_key) ) {
				CampWorkflowAutomationRepository::evaluate_camp((int) $camp->id);
				set_transient($throttle_key, 1, 15 * MINUTE_IN_SECONDS);
			}
			$case                 = CampCaseRepository::get_case((int) $camp->id);
			$organizer            = CampCaseRepository::get_organizer((int) $camp->id);
			$prearrival           = CampCaseRepository::get_prearrival((int) $camp->id);
			$history              = CampCaseRepository::get_history((int) $camp->id);
			$readiness            = CampCaseRepository::get_readiness_summary((int) $camp->id);
			$future_counts        = CampCaseRepository::get_future_module_counts((int) $camp->id);
			$module_summary       = CampCaseRepository::get_module_summary((int) $camp->id);
			$workflow_events      = CampWorkflowAutomationRepository::get_open_events((int) $camp->id);
			$recent_workflow_events = CampWorkflowAutomationRepository::get_recent_events((int) $camp->id);
			$open_tasks           = CampCaseRepository::get_open_checklist_items((int) $camp->id);
			$recent_activity      = CampCaseRepository::get_recent_activity((int) $camp->id);
			$checklist_db         = CampCaseRepository::get_checklist((int) $camp->id);
			if ( ! empty($checklist_db) ) {
				$checklist = array_map(
					static fn(object $item): array => [
						'label'       => (string) $item->label,
						'id'          => (string) $item->id,
						'party'       => (string) $item->party,
						'description' => (string) ($item->description ?? ''),
						'status'      => (string) $item->status,
						'priority'    => (string) ($item->priority ?? CampCaseRepository::CHECKLIST_PRIORITY_NORMAL),
						'assigned_to' => (string) $item->assigned_to,
						'due_date'    => (string) ($item->due_date ?? ''),
						'comment'     => (string) ($item->comment ?? ''),
					],
					$checklist_db
				);
				$checklist = CampCaseRepository::pad_checklist_rows($checklist);
			}
		}

		$workflow = CampCaseRepository::build_workflow_snapshot($camp, $case, $organizer, $prearrival, $readiness, $future_counts);
		$workspace = CampCaseRepository::stage_workspace((string) ($case->process_stage ?? CampCaseRepository::STAGE_INQUIRY));

		$process_stages    = CampCaseRepository::process_stages();
		$risk_levels       = CampCaseRepository::risk_levels();
		$checklist_parties = CampCaseRepository::checklist_parties();
		$checklist_statuses= CampCaseRepository::checklist_statuses();
		$checklist_priorities = CampCaseRepository::checklist_priorities();
		$allowed_transitions = CampCaseRepository::allowed_stage_transitions()[(string) ($case->process_stage ?? CampCaseRepository::STAGE_INQUIRY)] ?? [];
		$users             = get_users(['fields' => ['ID', 'display_name']]);

		// ── Documents tab data ────────────────────────────────────────────────
		global $wpdb;
		$camp_documents   = $camp_id > 0 ? $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_documents') . " WHERE camp_id = %d ORDER BY created_at DESC",
			$camp_id
		)) : [];
		$doc_templates    = $wpdb->get_results("SELECT * FROM " . Schema::table('doc_templates') . " ORDER BY sort_order ASC, id ASC") ?: [];
		$doc_library_items = $wpdb->get_results("SELECT * FROM " . Schema::table('doc_library') . " ORDER BY sort_order ASC, id ASC") ?: [];

		// ── Finance tab data ──────────────────────────────────────────────────
		$payment_schedules      = $camp_id > 0 ? $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_payment_schedules') . " WHERE camp_id = %d ORDER BY due_date ASC, id ASC",
			$camp_id
		)) : [];
		$payment_packages       = OrgFinancePage::get_packages();
		$camp_finance_package_id = $camp_id > 0 ? (int) get_post_meta($camp_id, '_bm_finance_package_id', true) : 0;

		// ── Declaration tab data ──────────────────────────────────────────────
		$camp_declaration = $camp_id > 0 ? $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_declarations') . " WHERE camp_id = %d",
			$camp_id
		)) : null;

		// ── Damages tab data ──────────────────────────────────────────────────
		$camp_damages = $camp_id > 0 ? $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_damages') . " WHERE camp_id = %d ORDER BY created_at DESC",
			$camp_id
		)) : [];

		include BASEMGMT_DIR . 'templates/admin/camps/edit.php';
	}

	public function handle_save_overview(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp_overview');
		$this->ensure_tables_ready();

		$id       = (int) ($_POST['camp_id'] ?? 0);
		$campData = $this->collect_camp_data();

		if ( $campData['name'] === '' ) {
			AdminMenu::set_notice(__('Nazwa obozu jest wymagana.', 'basemgmt'), 'error');
			$this->redirect_back($id ? "basemgmt-camps&action=edit&id={$id}#bm-section-overview" : 'basemgmt-camps&action=new');
			return;
		}

		$is_new_camp = ( $id === 0 );
		$camp_id = $this->save_camp_record($id, $campData);
		if ( ! $camp_id ) {
			AdminMenu::set_notice(__('Błąd zapisu.', 'basemgmt'), 'error');
			$this->redirect_back('basemgmt-camps');
			return;
		}

		if ( $is_new_camp ) {
			$this->auto_add_templates_to_camp($camp_id);
		}

		CampWorkflowAutomationRepository::evaluate_camp($camp_id);

		AdminMenu::set_notice(__('Podstawowe dane workflow obozu zostały zapisane.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-overview");
	}

	public function handle_save_process(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp_process');
		$this->ensure_tables_ready();

		$camp_id   = $this->require_camp_id();
		$existing  = CampCaseRepository::get_case($camp_id);
		$caseData  = $this->collect_case_data();
		$stage     = (string) ($caseData['process_stage'] ?? CampCaseRepository::STAGE_INQUIRY);
		$old_stage = (string) ($existing->process_stage ?? CampCaseRepository::STAGE_INQUIRY);

		if ( ! CampCaseRepository::can_transition($old_stage, $stage) ) {
			AdminMenu::set_notice(__('Niedozwolona zmiana etapu workflow. Przejdź zgodnie z dozwolonymi przejściami.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-process");
			return;
		}

		CampCaseRepository::save_case($camp_id, $caseData);
		if ( $old_stage !== $stage ) {
			CampWorkflowAutomationRepository::handle_stage_change($camp_id, $old_stage, $stage);
		}
		CampWorkflowAutomationRepository::evaluate_camp($camp_id);

		OperationLogger::log(
			OperationLogger::ACTION_CAMP_CASE_UPDATED,
			'camp_case',
			$camp_id,
			[
				'section'         => 'process',
				'process_stage'   => $stage ?: CampCaseRepository::STAGE_INQUIRY,
				'needs_attention' => ! empty($caseData['needs_attention']),
				'risk_level'      => (string) ($caseData['risk_level'] ?: CampCaseRepository::RISK_LOW),
			]
		);

		AdminMenu::set_notice(__('Etap workflow i automatyczna checklista zostały zapisane.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-process");
	}

	public function handle_save_organizer(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp_organizer');
		$this->ensure_tables_ready();

		$camp_id        = $this->require_camp_id();
		$organizer_data = $this->collect_organizer_data();

		CampCaseRepository::save_organizer($camp_id, $organizer_data);
		CampWorkflowAutomationRepository::evaluate_camp($camp_id);
		OperationLogger::log(
			OperationLogger::ACTION_CAMP_UPDATED,
			'camp',
			$camp_id,
			[
				'section'           => 'organizer',
				'organization_name' => $organizer_data['organization_name'],
			]
		);

		AdminMenu::set_notice(__('Dane organizatora zostały zapisane.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-organizer");
	}

	public function handle_save_checklist(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp_checklist');
		$this->ensure_tables_ready();

		$camp_id = $this->require_camp_id();
		CampCaseRepository::replace_checklist($camp_id, $this->parse_checklist($_POST['checklist'] ?? []));
		CampWorkflowAutomationRepository::evaluate_camp($camp_id);

		OperationLogger::log(
			OperationLogger::ACTION_CAMP_CHECKLIST_UPDATED,
			'camp_checklist',
			$camp_id,
			[
				'section' => 'checklist',
			]
		);

		AdminMenu::set_notice(__('Checklista workflow została zapisana.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-checklist");
	}

	public function handle_save_prearrival(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp_prearrival');
		$this->ensure_tables_ready();

		$camp_id         = $this->require_camp_id();
		$prearrival_data = $this->collect_prearrival_data();

		CampCaseRepository::save_prearrival($camp_id, $prearrival_data);
		CampWorkflowAutomationRepository::evaluate_camp($camp_id);
		OperationLogger::log(
			OperationLogger::ACTION_CAMP_UPDATED,
			'camp',
			$camp_id,
			[
				'section'      => 'prearrival',
				'arrival_date' => $prearrival_data['arrival_date'],
			]
		);

		AdminMenu::set_notice(__('Dane operacyjne zostały zapisane.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-prearrival");
	}

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp');
		$this->ensure_tables_ready();

		$id       = (int) ($_POST['camp_id'] ?? 0);
		$campData = $this->collect_camp_data();

		if ( $campData['name'] === '' ) {
			AdminMenu::set_notice(__('Nazwa obozu jest wymagana.', 'basemgmt'), 'error');
			$this->redirect_back($id ? "basemgmt-camps&action=edit&id={$id}" : 'basemgmt-camps&action=new');
			return;
		}

		$camp_id = $this->save_camp_record($id, $campData);
		if ( ! $camp_id ) {
			AdminMenu::set_notice(__('Błąd zapisu.', 'basemgmt'), 'error');
			$this->redirect_back('basemgmt-camps');
			return;
		}

		$case_existing  = CampCaseRepository::get_case($camp_id);
		$old_stage      = (string) ($case_existing->process_stage ?? CampCaseRepository::STAGE_INQUIRY);
		$caseData       = $this->collect_case_data();
		$organizerData  = $this->collect_organizer_data();
		$prearrivalData = $this->collect_prearrival_data();

		if ( ! CampCaseRepository::can_transition($old_stage, (string) ($caseData['process_stage'] ?? CampCaseRepository::STAGE_INQUIRY)) ) {
			AdminMenu::set_notice(__('Niedozwolona zmiana etapu workflow. Przejdź zgodnie z dozwolonymi przejściami.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-process");
			return;
		}

		CampCaseRepository::save_case($camp_id, $caseData);
		CampCaseRepository::save_organizer($camp_id, $organizerData);
		CampCaseRepository::save_prearrival($camp_id, $prearrivalData);
		CampCaseRepository::replace_checklist($camp_id, $this->parse_checklist($_POST['checklist'] ?? []));
		if ( $old_stage !== (string) ($caseData['process_stage'] ?? CampCaseRepository::STAGE_INQUIRY) ) {
			CampWorkflowAutomationRepository::handle_stage_change($camp_id, $old_stage, (string) $caseData['process_stage']);
		}
		CampWorkflowAutomationRepository::evaluate_camp($camp_id);

		OperationLogger::log(
			OperationLogger::ACTION_CAMP_CASE_UPDATED,
			'camp_case',
			$camp_id,
			[
				'process_stage'   => $caseData['process_stage'] ?: CampCaseRepository::STAGE_INQUIRY,
				'needs_attention' => $caseData['needs_attention'] === '1',
				'risk_level'      => $caseData['risk_level'] ?: CampCaseRepository::RISK_LOW,
			]
		);

		AdminMenu::set_notice(__('Teczka obozu została zapisana.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}");
	}

	public function handle_delete(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer("bm_delete_camp_{$id}");

		if ( $id ) {
			CampRepository::delete($id);
			OperationLogger::log(OperationLogger::ACTION_CAMP_DELETED, 'camp', $id);
			AdminMenu::set_notice(__('Obóz usunięty.', 'basemgmt'));
		}

		$this->redirect_back('basemgmt-camps');
	}

	/**
	 * @param mixed $raw
	 * @return array<int,array<string,string>>
	 */
	private function parse_checklist(mixed $raw): array {
		if ( ! is_array($raw) ) {
			return [];
		}

		$labels      = array_values((array) ($raw['label'] ?? []));
		$parties     = array_values((array) ($raw['party'] ?? []));
		$statuses    = array_values((array) ($raw['status'] ?? []));
		$priorities  = array_values((array) ($raw['priority'] ?? []));
		$ids         = array_values((array) ($raw['id'] ?? []));
		$assigned    = array_values((array) ($raw['assigned_to'] ?? []));
		$due_dates   = array_values((array) ($raw['due_date'] ?? []));
		$comments    = array_values((array) ($raw['comment'] ?? []));
		$descriptions = array_values((array) ($raw['description'] ?? []));
		$total_rows  = max(count($labels), count($parties), count($statuses), count($priorities), count($ids), count($assigned), count($due_dates), count($comments));
		$items       = [];

		for ( $i = 0; $i < $total_rows; $i++ ) {
			$items[] = [
				'label'       => wp_unslash((string) ($labels[$i] ?? '')),
				'id'          => (string) (int) wp_unslash((string) ($ids[$i] ?? '0')),
				'party'       => wp_unslash((string) ($parties[$i] ?? CampCaseRepository::CHECKLIST_PARTY_SHARED)),
				'description' => wp_unslash((string) ($descriptions[$i] ?? '')),
				'status'      => wp_unslash((string) ($statuses[$i] ?? CampCaseRepository::CHECKLIST_STATUS_PENDING)),
				'priority'    => wp_unslash((string) ($priorities[$i] ?? CampCaseRepository::CHECKLIST_PRIORITY_NORMAL)),
				'assigned_to' => wp_unslash((string) ($assigned[$i] ?? '')),
				'due_date'    => wp_unslash((string) ($due_dates[$i] ?? '')),
				'comment'     => wp_unslash((string) ($comments[$i] ?? '')),
			];
		}

		return $items;
	}

	private function render_task_edit(int $task_id, int $camp_id): void {
		if ( $camp_id <= 0 ) {
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-camps'));
			exit;
		}

		$camp = CampRepository::get($camp_id);
		if ( ! $camp ) {
			wp_safe_redirect(admin_url('admin.php?page=basemgmt-camps'));
			exit;
		}

		$task                 = $task_id > 0 ? CampCaseRepository::get_single_checklist_item($task_id) : null;
		$default_status       = sanitize_key($_GET['default_status'] ?? CampCaseRepository::CHECKLIST_STATUS_PENDING);
		$checklist_parties    = CampCaseRepository::checklist_parties();
		$checklist_statuses   = CampCaseRepository::checklist_statuses();
		$checklist_priorities = CampCaseRepository::checklist_priorities();
		$users                = get_users(['fields' => ['ID', 'display_name']]);

		include BASEMGMT_DIR . 'templates/admin/camps/task-edit.php';
	}

	public function handle_save_task(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp_task');
		$this->ensure_tables_ready();

		$camp_id = (int) ($_POST['camp_id'] ?? 0);
		$task_id = (int) ($_POST['task_id'] ?? 0);

		if ( $camp_id <= 0 ) {
			AdminMenu::set_notice(__('Brak identyfikatora obozu.', 'basemgmt'), 'error');
			$this->redirect_back('basemgmt-camps');
			return;
		}

		$data = [
			'label'       => sanitize_text_field(wp_unslash($_POST['label'] ?? '')),
			'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
			'party'       => sanitize_key(wp_unslash($_POST['party'] ?? '')),
			'status'      => sanitize_key(wp_unslash($_POST['status'] ?? '')),
			'priority'    => sanitize_key(wp_unslash($_POST['priority'] ?? '')),
			'assigned_to' => sanitize_text_field(wp_unslash($_POST['assigned_to'] ?? '')),
			'due_date'    => sanitize_text_field(wp_unslash($_POST['due_date'] ?? '')),
			'comment'     => sanitize_textarea_field(wp_unslash($_POST['comment'] ?? '')),
		];

		if ( $data['label'] === '' ) {
			AdminMenu::set_notice(__('Nazwa zadania jest wymagana.', 'basemgmt'), 'error');
			$back = $task_id > 0
				? "basemgmt-camps&action=task_edit&id={$camp_id}&task_id={$task_id}"
				: "basemgmt-camps&action=task_new&id={$camp_id}";
			$this->redirect_back($back);
			return;
		}

		if ( $task_id > 0 ) {
			CampCaseRepository::update_checklist_item($task_id, $camp_id, $data);
			AdminMenu::set_notice(__('Zadanie zostało zaktualizowane.', 'basemgmt'));
		} else {
			$task_id = CampCaseRepository::insert_checklist_item($camp_id, $data);
			AdminMenu::set_notice(__('Zadanie zostało dodane.', 'basemgmt'));
		}

		if ( ! empty($_POST['_continue_editing']) ) {
			$this->redirect_back("basemgmt-camps&action=task_edit&id={$camp_id}&task_id={$task_id}");
		} else {
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-checklist");
		}
	}

	public function handle_delete_task(): void {
		Capabilities::require_admin();
		$camp_id = (int) ($_GET['id'] ?? 0);
		$task_id = (int) ($_GET['task_id'] ?? 0);
		check_admin_referer("bm_delete_task_{$task_id}");

		if ( $task_id > 0 && $camp_id > 0 ) {
			CampCaseRepository::delete_checklist_item($task_id, $camp_id);
			AdminMenu::set_notice(__('Zadanie usunięte.', 'basemgmt'));
		}

		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-checklist");
	}

	private function redirect_back(string $page): void {
		wp_safe_redirect(admin_url("admin.php?page={$page}"));
		exit;
	}

	// ── Document handlers ─────────────────────────────────────────────────────

	private function render_doc_view(int $camp_id, int $doc_id): void {
		Capabilities::require_admin();
		global $wpdb;
		$doc  = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Schema::table('camp_documents') . " WHERE id = %d AND camp_id = %d", $doc_id, $camp_id));
		$camp = CampRepository::get($camp_id);
		if ( ! $doc || empty($doc->html_content) ) {
			wp_die(esc_html__('Dokument nie istnieje lub nie ma zawartości HTML.', 'basemgmt'));
		}
		$back_url = admin_url("admin.php?page=basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html($doc->title); ?></title>
			<style>
				body { font-family: Georgia, serif; max-width: 800px; margin: 30px auto; padding: 20px; color: #1d2327; }
				.bm-doc-toolbar { background: #f0f0f1; border-bottom: 1px solid #ddd; padding: 10px 20px; position: fixed; top: 0; left: 0; right: 0; display: flex; gap: 10px; align-items: center; z-index: 100; }
				.bm-doc-body { margin-top: 60px; }
				@media print { .bm-doc-toolbar { display: none; } .bm-doc-body { margin-top: 0; } }
			</style>
		</head>
		<body>
			<div class="bm-doc-toolbar">
				<a href="<?php echo esc_url($back_url); ?>">&larr; <?php esc_html_e('Powrót', 'basemgmt'); ?></a>
				<button onclick="window.print()" style="margin-left:auto;"><?php esc_html_e('Drukuj / Zapisz PDF', 'basemgmt'); ?></button>
			</div>
			<div class="bm-doc-body">
				<?php echo wp_kses_post($doc->html_content); ?>
			</div>
		</body>
		</html>
		<?php
		exit;
	}

	public function handle_add_camp_doc_library(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_add_camp_doc_library');
		$camp_id  = (int) ($_POST['camp_id'] ?? 0);
		$lib_id   = (int) ($_POST['library_doc_id'] ?? 0);
		if ( $camp_id <= 0 || $lib_id <= 0 ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}
		global $wpdb;
		$lib_doc = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Schema::table('doc_library') . " WHERE id = %d", $lib_id));
		if ( ! $lib_doc ) {
			AdminMenu::set_notice(__('Dokument nie istnieje.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}
		$wpdb->insert(Schema::table('camp_documents'), [
			'camp_id'       => $camp_id,
			'document_type' => $lib_doc->doc_type,
			'doc_category'  => 'library',
			'title'         => $lib_doc->title,
			'status'        => 'ready',
			'file_id'       => $lib_doc->file_id,
			'file_url'      => $lib_doc->file_url,
		]);
		AdminMenu::set_notice(__('Dokument dodany do obozu.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
	}

	public function handle_create_camp_doc_from_template(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_create_camp_doc_from_template');
		$camp_id     = (int) ($_POST['camp_id'] ?? 0);
		$template_id = (int) ($_POST['template_id'] ?? 0);
		if ( $camp_id <= 0 || $template_id <= 0 ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}
		global $wpdb;
		$tpl  = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Schema::table('doc_templates') . " WHERE id = %d", $template_id));
		$camp = CampRepository::get($camp_id);
		$org  = CampCaseRepository::get_organizer($camp_id);
		$pre  = CampCaseRepository::get_prearrival($camp_id);
		if ( ! $tpl || ! $camp ) {
			AdminMenu::set_notice(__('Nie znaleziono szablonu lub obozu.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}
		$vars = [
			'{{camp_name}}'        => $camp->name,
			'{{organizer_name}}'   => $org->organization_name ?? '',
			'{{organizer_email}}'  => $org->contact_email ?? '',
			'{{start_date}}'       => $camp->start_date,
			'{{end_date}}'         => $camp->end_date,
			'{{participants}}'     => (string) ($pre->declared_participants ?? 0),
		];
		$html_content = str_replace(array_keys($vars), array_values($vars), $tpl->html_content);
		$wpdb->insert(Schema::table('camp_documents'), [
			'camp_id'       => $camp_id,
			'document_type' => $tpl->doc_type,
			'doc_category'  => 'template',
			'title'         => $tpl->title . ' — ' . $camp->name,
			'status'        => 'draft',
			'template_id'   => $template_id,
			'html_content'  => $html_content,
		]);
		AdminMenu::set_notice(__('Dokument wygenerowany z szablonu.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
	}

	public function handle_send_camp_doc(): void {
		Capabilities::require_admin();
		$camp_id = (int) ($_GET['id'] ?? 0);
		$doc_id  = (int) ($_GET['doc_id'] ?? 0);
		check_admin_referer("bm_send_camp_doc_{$doc_id}");
		global $wpdb;
		$doc = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Schema::table('camp_documents') . " WHERE id = %d AND camp_id = %d", $doc_id, $camp_id));
		if ( ! $doc || ! empty($doc->locked) ) {
			AdminMenu::set_notice(__('Nie można wysłać tego dokumentu.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}
		$token = bin2hex(random_bytes(32));
		$wpdb->update(Schema::table('camp_documents'), [
			'status'     => 'sent',
			'sent_at'    => current_time('mysql'),
			'sent_token' => $token,
			'locked'     => 1,
		], ['id' => $doc_id]);
		$sign_url = add_query_arg(['bm_doc_token' => $token], home_url('/'));
		AdminMenu::set_notice(sprintf(__('Dokument zablokowany. Link do klienta: %s', 'basemgmt'), $sign_url));
		if (get_option('bm_notify_doc_sent') === '1') {
			$notify_email = get_option('bm_notify_doc_email');
			if (empty($notify_email)) {
				$org_notify = CampCaseRepository::get_organizer($camp_id);
				$notify_email = $org_notify->contact_email ?? '';
			}
			if (!empty($notify_email)) {
				$camp_obj = CampRepository::get($camp_id);
				$action_word = ($doc->document_type === 'declaration')
					? __('zaakceptowania', 'basemgmt')
					: __('podpisania', 'basemgmt');
				wp_mail(
					$notify_email,
					sprintf(__('[CampLink] Dokument do %s: %s', 'basemgmt'), $action_word, $doc->title),
					sprintf(__("Dokument \"%s\" obozu \"%s\" oczekuje na %s.\n\nLink: %s", 'basemgmt'), $doc->title, $camp_obj->name ?? '', $action_word, $sign_url)
				);
			}
		}
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
	}

	public function handle_delete_camp_doc(): void {
		Capabilities::require_admin();
		$camp_id = (int) ($_GET['id'] ?? 0);
		$doc_id  = (int) ($_GET['doc_id'] ?? 0);
		check_admin_referer("bm_delete_camp_doc_{$doc_id}");
		global $wpdb;
		$wpdb->delete(Schema::table('camp_documents'), ['id' => $doc_id, 'camp_id' => $camp_id, 'locked' => 0]);
		AdminMenu::set_notice(__('Dokument usunięty.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
	}

	// ── Finance handler ───────────────────────────────────────────────────────

	public function handle_save_camp_finance(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp_finance');
		$camp_id = (int) ($_POST['camp_id'] ?? 0);
		if ( $camp_id <= 0 ) {
			$this->redirect_back('basemgmt-camps');
			return;
		}
		global $wpdb;
		$tbl = Schema::table('camp_payment_schedules');

		$apply_package = ! empty($_POST['apply_package_btn']) ? (int) ($_POST['apply_package'] ?? 0) : 0;

		if ( $apply_package > 0 ) {
			// Apply package: replace all schedules.
			$pkg_lines = OrgFinancePage::get_package_lines($apply_package);
			$camp      = CampRepository::get($camp_id);
			$pre       = CampCaseRepository::get_prearrival($camp_id);
			$arrival   = $pre->arrival_date ?? $camp->start_date ?? '';
			$wpdb->delete($tbl, ['camp_id' => $camp_id]);
			foreach ( $pkg_lines as $line ) {
				$due_date = '';
				if ( $arrival && $line->days_before >= 0 ) {
					$dt = new \DateTime($arrival);
					$dt->modify("-{$line->days_before} days");
					$due_date = $dt->format('Y-m-d');
				}
				$wpdb->insert($tbl, [
					'camp_id'      => $camp_id,
					'payment_type' => $line->line_type,
					'label'        => $line->label,
					'amount'       => $line->unit_price,
					'due_date'     => $due_date ?: null,
					'status'       => 'expected',
					'description'  => $line->unit,
				]);
			}
			update_post_meta($camp_id, '_bm_finance_package_id', $apply_package);
			AdminMenu::set_notice(__('Pakiet finansowy zastosowany.', 'basemgmt'));
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-finance");
			return;
		}

		// Manual save.
		$ids       = (array) ($_POST['sched_id'] ?? []);
		$labels    = (array) ($_POST['sched_label'] ?? []);
		$types     = (array) ($_POST['sched_type'] ?? []);
		$amounts   = (array) ($_POST['sched_amount'] ?? []);
		$due_dates = (array) ($_POST['sched_due_date'] ?? []);
		$statuses  = (array) ($_POST['sched_status'] ?? []);

		// Delete removed rows.
		$existing_ids = array_filter(array_map('intval', $ids));
		if ( $existing_ids ) {
			$placeholders = implode(',', array_fill(0, count($existing_ids), '%d'));
			$wpdb->query($wpdb->prepare(
				"DELETE FROM {$tbl} WHERE camp_id = %d AND id NOT IN ({$placeholders})",
				array_merge([$camp_id], $existing_ids)
			)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$wpdb->delete($tbl, ['camp_id' => $camp_id]);
		}

		foreach ( $labels as $i => $label ) {
			$label = sanitize_text_field($label);
			if ( empty($label) ) {
				continue;
			}
			$row_id = (int) ($ids[$i] ?? 0);
			$data   = [
				'camp_id'      => $camp_id,
				'payment_type' => sanitize_key($types[$i] ?? 'other'),
				'label'        => $label,
				'amount'       => (float) str_replace(',', '.', $amounts[$i] ?? '0'),
				'due_date'     => sanitize_text_field($due_dates[$i] ?? '') ?: null,
				'status'       => sanitize_key($statuses[$i] ?? 'expected'),
			];
			if ( $row_id > 0 ) {
				$wpdb->update($tbl, $data, ['id' => $row_id, 'camp_id' => $camp_id]);
			} else {
				$wpdb->insert($tbl, $data);
			}
		}

		AdminMenu::set_notice(__('Harmonogram płatności zapisany.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-finance");
	}

	private function ensure_tables_ready(): void {
		if ( ! CampCaseRepository::tables_ready() ) {
			Schema::create_tables();
			CampCaseRepository::invalidate_tables_ready_cache();
		}
	}

	private function require_camp_id(): int {
		$camp_id = (int) ($_POST['camp_id'] ?? 0);
		if ( $camp_id > 0 ) {
			return $camp_id;
		}

		AdminMenu::set_notice(__('Najpierw zapisz podstawowe dane obozu.', 'basemgmt'), 'error');
		$this->redirect_back('basemgmt-camps&action=new');
		return 0;
	}

	private function save_camp_record(int $id, array $camp_data): int {
		if ( $id ) {
			CampRepository::update($id, $camp_data);
			OperationLogger::log(
				OperationLogger::ACTION_CAMP_UPDATED,
				'camp',
				$id,
				[
					'name'   => $camp_data['name'],
					'status' => $camp_data['status'],
				]
			);
			return $id;
		}

		$camp_id = (int) CampRepository::insert($camp_data);
		if ( $camp_id > 0 ) {
			OperationLogger::log(
				OperationLogger::ACTION_CAMP_CREATED,
				'camp',
				$camp_id,
				[
					'name'   => $camp_data['name'],
					'status' => $camp_data['status'],
				]
			);
		}

		return $camp_id;
	}

	/**
	 * @return array{name:string,start_date:string,end_date:string,status:string}
	 */
	private function collect_camp_data(): array {
		return [
			'name'       => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
			'start_date' => sanitize_text_field(wp_unslash($_POST['start_date'] ?? '')),
			'end_date'   => sanitize_text_field(wp_unslash($_POST['end_date'] ?? '')),
			'status'     => sanitize_key(wp_unslash($_POST['status'] ?? 'active')),
		];
	}

	/**
	 * @return array<string,string>
	 */
	private function collect_case_data(): array {
		return [
			'process_stage'        => sanitize_key(wp_unslash($_POST['process_stage'] ?? '')),
			'needs_attention'      => ! empty($_POST['needs_attention']) ? '1' : '',
			'risk_level'           => sanitize_key(wp_unslash($_POST['risk_level'] ?? '')),
			'owner_user_id'        => (string) (int) wp_unslash($_POST['owner_user_id'] ?? '0'),
			'next_action_due_date' => sanitize_text_field(wp_unslash($_POST['next_action_due_date'] ?? '')),
			'notes'                => sanitize_textarea_field(wp_unslash($_POST['case_notes'] ?? '')),
			'readiness_notes'      => sanitize_textarea_field(wp_unslash($_POST['readiness_notes'] ?? '')),
			'stage_change_note'    => sanitize_textarea_field(wp_unslash($_POST['stage_change_note'] ?? '')),
		];
	}

	/**
	 * Auto-add task templates, documents, and declarations marked as auto-add to a newly created camp.
	 */
	private function auto_add_templates_to_camp( int $camp_id ): void {
		global $wpdb;

		// Auto-add task templates
		$task_templates = OrgTasksPage::get_auto_add();
		foreach ( $task_templates as $tmpl ) {
			$wpdb->insert(Schema::table('camp_checklist_items'), [
				'camp_id'     => $camp_id,
				'party'       => 'shared',
				'label'       => $tmpl->title,
				'description' => $tmpl->description ?? '',
				'status'      => 'pending',
				'priority'    => $tmpl->priority ?? 'normal',
				'assigned_to' => '',
				'created_at'  => current_time('mysql'),
				'updated_at'  => current_time('mysql'),
			]);
		}
	}

	/**
	 * @return array<string,string>
	 */
	private function collect_organizer_data(): array {
		return [
			'organization_name'        => sanitize_text_field(wp_unslash($_POST['organization_name'] ?? '')),
			'contact_person'           => sanitize_text_field(wp_unslash($_POST['contact_person'] ?? '')),
			'contact_email'            => sanitize_email(wp_unslash($_POST['contact_email'] ?? '')),
			'contact_phone'            => sanitize_text_field(wp_unslash($_POST['contact_phone'] ?? '')),
			'billing_name'             => sanitize_text_field(wp_unslash($_POST['billing_name'] ?? '')),
			'billing_tax_id'           => sanitize_text_field(wp_unslash($_POST['billing_tax_id'] ?? '')),
			'billing_address'          => sanitize_textarea_field(wp_unslash($_POST['billing_address'] ?? '')),
			'settlement_contact_name'  => sanitize_text_field(wp_unslash($_POST['settlement_contact_name'] ?? '')),
			'settlement_contact_email' => sanitize_email(wp_unslash($_POST['settlement_contact_email'] ?? '')),
			'settlement_contact_phone' => sanitize_text_field(wp_unslash($_POST['settlement_contact_phone'] ?? '')),
			'notes'                    => sanitize_textarea_field(wp_unslash($_POST['organizer_notes'] ?? '')),
			'billing_regon'            => sanitize_text_field(wp_unslash($_POST['billing_regon'] ?? '')),
			'billing_krs'              => sanitize_text_field(wp_unslash($_POST['billing_krs'] ?? '')),
			'billing_street'           => sanitize_text_field(wp_unslash($_POST['billing_street'] ?? '')),
			'billing_city'             => sanitize_text_field(wp_unslash($_POST['billing_city'] ?? '')),
			'billing_zip'              => sanitize_text_field(wp_unslash($_POST['billing_zip'] ?? '')),
			'bank_name'                => sanitize_text_field(wp_unslash($_POST['bank_name'] ?? '')),
			'bank_account'             => sanitize_text_field(wp_unslash($_POST['bank_account'] ?? '')),
		];
	}

	/**
	 * @return array<string,string>
	 */
	private function collect_prearrival_data(): array {
		return [
			'arrival_date'          => sanitize_text_field(wp_unslash($_POST['arrival_date'] ?? '')),
			'arrival_time'          => sanitize_text_field(wp_unslash($_POST['arrival_time'] ?? '')),
			'departure_date'        => sanitize_text_field(wp_unslash($_POST['departure_date'] ?? '')),
			'departure_time'        => sanitize_text_field(wp_unslash($_POST['departure_time'] ?? '')),
			'declared_participants' => (string) max(0, (int) wp_unslash($_POST['declared_participants'] ?? '0')),
			'declared_staff'        => (string) max(0, (int) wp_unslash($_POST['declared_staff'] ?? '0')),
			'declared_support'      => (string) max(0, (int) wp_unslash($_POST['declared_support'] ?? '0')),
			'dietary_requirements'  => sanitize_textarea_field(wp_unslash($_POST['dietary_requirements'] ?? '')),
			'allergens'             => sanitize_textarea_field(wp_unslash($_POST['allergens'] ?? '')),
			'infrastructure_plan'   => sanitize_textarea_field(wp_unslash($_POST['infrastructure_plan'] ?? '')),
			'additional_needs'      => sanitize_textarea_field(wp_unslash($_POST['additional_needs'] ?? '')),
			'invoice_details'       => sanitize_textarea_field(wp_unslash($_POST['invoice_details'] ?? '')),
			'authorized_contacts'   => sanitize_textarea_field(wp_unslash($_POST['authorized_contacts'] ?? '')),
		];
	}

	public function handle_add_task_from_template(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_add_task_from_template');
		$camp_id      = (int) ($_POST['camp_id'] ?? 0);
		$template_ids = array_map('intval', (array) ($_POST['template_ids'] ?? []));
		if ($camp_id <= 0 || empty($template_ids)) {
			AdminMenu::set_notice(__('Nie wybrano szablonów.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-checklist");
			return;
		}
		$templates = \BaseMgmt\Admin\Pages\OrgTasksPage::get_all();
		$added = 0;
		foreach ($templates as $tpl) {
			if (!in_array((int)$tpl->id, $template_ids, true)) continue;
			CampCaseRepository::insert_checklist_item($camp_id, [
				'label'       => $tpl->title,
				'description' => $tpl->description ?? '',
				'priority'    => $tpl->priority,
				'status'      => 'pending',
				'party'       => 'shared',
				'assigned_to' => '',
				'due_date'    => null,
				'comment'     => '',
			]);
			$added++;
		}
		AdminMenu::set_notice(sprintf(__('Dodano %d zadań z szablonów.', 'basemgmt'), $added));

		if (get_option('bm_notify_task_added') === '1' && $added > 0) {
			$notify_email = get_option('bm_notify_task_email') ?: get_option('admin_email');
			if (!empty($notify_email)) {
				$camp = CampRepository::get($camp_id);
				wp_mail(
					$notify_email,
					sprintf(__('[CampLink] Dodano %d zadań do obozu %s', 'basemgmt'), $added, $camp->name ?? ''),
					sprintf(__("Dodano %d zadań z szablonów do obozu \"%s\".\n\nPodgląd: %s", 'basemgmt'), $added, $camp->name ?? '', admin_url("admin.php?page=basemgmt-camps&action=edit&id={$camp_id}#bm-section-checklist"))
				);
			}
		}

		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-checklist");
	}

	public function handle_save_camp_declaration(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp_declaration');
		$camp_id = (int) ($_POST['camp_id'] ?? 0);
		if ($camp_id <= 0) {
			$this->redirect_back('basemgmt-camps');
			return;
		}
		global $wpdb;
		$tbl = Schema::table('camp_declarations');
		$data = [
			'camp_id'          => $camp_id,
			'declared_persons' => (int) ($_POST['declared_persons'] ?? 0),
			'declared_diets'   => (int) ($_POST['declared_diets'] ?? 0),
			'arrival_time'     => sanitize_text_field($_POST['decl_arrival_time'] ?? ''),
			'departure_time'   => sanitize_text_field($_POST['decl_departure_time'] ?? ''),
			'is_active'        => ! empty($_POST['decl_is_active']) ? 1 : 0,
			'notes'            => sanitize_textarea_field($_POST['decl_notes'] ?? ''),
		];
		$existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$tbl} WHERE camp_id = %d", $camp_id));
		if ($existing) {
			$wpdb->update($tbl, $data, ['camp_id' => $camp_id]);
		} else {
			$wpdb->insert($tbl, $data);
		}
		AdminMenu::set_notice(__('Deklaracja zapisana.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
	}

	public function handle_add_camp_damage(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_add_camp_damage');
		$camp_id = (int) ($_POST['camp_id'] ?? 0);
		if ($camp_id <= 0) {
			$this->redirect_back('basemgmt-camps');
			return;
		}
		global $wpdb;
		$wpdb->insert(Schema::table('camp_damages'), [
			'camp_id'     => $camp_id,
			'name'        => sanitize_text_field(wp_unslash($_POST['damage_name'] ?? '')),
			'description' => sanitize_textarea_field(wp_unslash($_POST['damage_description'] ?? '')),
			'cost'        => (float) str_replace(',', '.', $_POST['damage_cost'] ?? '0'),
			'status'      => sanitize_key($_POST['damage_status'] ?? 'reported'),
		]);
		AdminMenu::set_notice(__('Szkoda dodana.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-finance");
	}

	public function handle_delete_camp_damage(): void {
		Capabilities::require_admin();
		$camp_id   = (int) ($_GET['id'] ?? 0);
		$damage_id = (int) ($_GET['damage_id'] ?? 0);
		check_admin_referer("bm_delete_camp_damage_{$damage_id}");
		global $wpdb;
		$wpdb->delete(Schema::table('camp_damages'), ['id' => $damage_id, 'camp_id' => $camp_id]);
		AdminMenu::set_notice(__('Szkoda usunięta.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-finance");
	}
}
