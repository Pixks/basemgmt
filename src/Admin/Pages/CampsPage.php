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

		// Per-day declaration data
		$camp_declaration_days = $camp_id > 0 ? $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_declaration_days') . " WHERE camp_id = %d ORDER BY declaration_date ASC",
			$camp_id
		)) : [];

		$decl_days_by_date = [];
		foreach ( $camp_declaration_days as $day ) {
			$decl_days_by_date[$day->declaration_date] = $day;
		}

		$decl_diet_lines_by_day_id = [];
		$decl_accom_lines_by_day_id = [];
		if ( $camp_declaration_days ) {
			$day_ids = array_map(static fn( $d ) => (int) $d->id, $camp_declaration_days);
			$ids_placeholder = implode(',', $day_ids);

			$diet_lines = $wpdb->get_results(
				"SELECT * FROM " . Schema::table('camp_declaration_diet_lines') . " WHERE day_id IN ({$ids_placeholder})" // phpcs:ignore
			) ?: [];
			foreach ( $diet_lines as $dl ) {
				$decl_diet_lines_by_day_id[$dl->day_id][$dl->diet_id] = (int) $dl->count;
			}

			$accom_lines = $wpdb->get_results(
				"SELECT * FROM " . Schema::table('camp_declaration_accommodation_lines') . " WHERE day_id IN ({$ids_placeholder})" // phpcs:ignore
			) ?: [];
			foreach ( $accom_lines as $al ) {
				$decl_accom_lines_by_day_id[$al->day_id][$al->accommodation_type_id] = (int) $al->count;
			}
		}

		// Diet types and accommodation types for declaration columns
		$decl_diet_types = $wpdb->get_results(
			"SELECT * FROM " . Schema::table('meal_diets') . " ORDER BY sort_order ASC, id ASC"
		) ?: [];
		$decl_accommodation_types = $wpdb->get_results(
			"SELECT * FROM " . Schema::table('accommodation_types') . " ORDER BY sort_order ASC, id ASC"
		) ?: [];

		// ── Damages tab data ──────────────────────────────────────────────────
		$camp_damages = $camp_id > 0 ? $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_damages') . " WHERE camp_id = %d ORDER BY created_at DESC",
			$camp_id
		)) : [];

		// ── Equipment tab data ────────────────────────────────────────────────
		$camp_equipment = $camp_id > 0 ? $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_equipment') . " WHERE camp_id = %d ORDER BY created_at DESC",
			$camp_id
		)) : [];

		// ── Declaration docs tab data ─────────────────────────────────────────
		$camp_decl_docs   = $camp_id > 0 ? $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_decl_docs') . " WHERE camp_id = %d ORDER BY created_at DESC",
			$camp_id
		)) : [];
		$decl_tpl_options = \BaseMgmt\Admin\Pages\OrgDeclarationsPage::get_all();

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
			// Check declaration exists and is active
			$declaration = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM " . Schema::table('camp_declarations') . " WHERE camp_id = %d AND is_active = 1",
				$camp_id
			));
			if ( ! $declaration ) {
				AdminMenu::set_notice(__('Nie można zastosować pakietu — brak aktywnej deklaracji obozu. Uzupełnij deklarację najpierw.', 'basemgmt'), 'error');
				$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-finance");
				return;
			}

			$keep_existing = ! empty($_POST['keep_existing_schedules']);
			if ( ! $keep_existing ) {
				$wpdb->delete($tbl, ['camp_id' => $camp_id]);
			}

			$camp    = CampRepository::get($camp_id);
			$pre     = CampCaseRepository::get_prearrival($camp_id);
			$arrival = $pre->arrival_date ?? $camp->start_date ?? '';

			$calc_due = static function( int $days_before, string $arrival_date ): ?string {
				if ( ! $arrival_date || $days_before < 0 ) return null;
				$dt = new \DateTime($arrival_date);
				$dt->modify("-{$days_before} days");
				return $dt->format('Y-m-d');
			};

			// 1. Standard package lines (pozycje kosztowe)
			$pkg_lines = OrgFinancePage::get_package_lines($apply_package);
			foreach ( $pkg_lines as $line ) {
				$wpdb->insert($tbl, [
					'camp_id'      => $camp_id,
					'payment_type' => $line->line_type,
					'label'        => $line->label,
					'amount'       => $line->unit_price,
					'amount_type'  => 'fixed',
					'due_date'     => $calc_due((int) $line->days_before, $arrival),
					'status'       => 'expected',
				]);
			}

			// 2. Accommodation lines — from declaration
			// Sum up person-days per accommodation type across all declaration days
			$decl_days = $wpdb->get_results($wpdb->prepare(
				"SELECT dd.id, dd.declared_persons FROM " . Schema::table('camp_declaration_days') . " dd WHERE dd.camp_id = %d",
				$camp_id
			));
			$day_ids = $decl_days ? array_column($decl_days, 'id') : [];

			$accom_totals = []; // [accom_type_id => total person-nights]
			if ( $day_ids ) {
				$ph = implode(',', array_fill(0, count($day_ids), '%d'));
				$accom_lines = $wpdb->get_results($wpdb->prepare(
					"SELECT accommodation_type_id, SUM(count) as total_count FROM " . Schema::table('camp_declaration_accommodation_lines') . " WHERE day_id IN ({$ph}) GROUP BY accommodation_type_id", // phpcs:ignore
					...$day_ids
				));
				foreach ( $accom_lines as $al ) {
					$accom_totals[(int)$al->accommodation_type_id] = (int)$al->total_count;
				}
			}

			$pkg_accom = OrgFinancePage::get_package_accom($apply_package);
			foreach ( $pkg_accom as $pa ) {
				$type_id     = (int) $pa->accommodation_type_id;
				$person_nights = $accom_totals[$type_id] ?? 0;
				if ( $person_nights <= 0 ) continue;
				$price_brutto = round((float)$pa->price_netto * (1 + (float)$pa->vat_rate / 100), 2);
				$total        = round($price_brutto * $person_nights, 2);
				$label        = sprintf(
					/* translators: 1: accommodation name, 2: person-nights, 3: unit price */
					__('%1$s — %2$d os./noc × %3$s zł', 'basemgmt'),
					$pa->accom_name,
					$person_nights,
					number_format($price_brutto, 2, ',', ' ')
				);
				$wpdb->insert($tbl, [
					'camp_id'      => $camp_id,
					'payment_type' => 'accommodation',
					'label'        => $label,
					'amount'       => $total,
					'amount_type'  => 'fixed',
					'due_date'     => $calc_due((int) $pa->days_before, $arrival),
					'status'       => 'expected',
				]);
			}

			// 3. Diet lines — from declaration
			// Sum person-days per diet_id across all declaration days
			$diet_totals = []; // [diet_id => total person-days]
			if ( $day_ids ) {
				$ph = implode(',', array_fill(0, count($day_ids), '%d'));
				$diet_lines_raw = $wpdb->get_results($wpdb->prepare(
					"SELECT diet_id, SUM(count) as total_count FROM " . Schema::table('camp_declaration_diet_lines') . " WHERE day_id IN ({$ph}) GROUP BY diet_id", // phpcs:ignore
					...$day_ids
				));
				foreach ( $diet_lines_raw as $dl ) {
					$diet_totals[(int)$dl->diet_id] = (int)$dl->total_count;
				}
			}

			// Get package diet slots grouped by diet_id — sum daily cost across enabled slots
			$pkg_diets_raw = OrgFinancePage::get_package_diets($apply_package);
			$diet_daily_cost = []; // [diet_id => ['name'=>, 'cost_brutto'=>, 'days_before'=>]]
			foreach ( $pkg_diets_raw as $ds ) {
				$did = (int)$ds->diet_id;
				if ( ! isset($diet_daily_cost[$did]) ) {
					$diet_daily_cost[$did] = ['name' => $ds->diet_name, 'cost_brutto' => 0.0, 'days_before' => (int)$ds->days_before];
				}
				$diet_daily_cost[$did]['cost_brutto'] += round((float)$ds->cost_netto * (1 + (float)$ds->vat_rate / 100), 4);
			}

			foreach ( $diet_daily_cost as $diet_id => $dc ) {
				$person_days = $diet_totals[$diet_id] ?? 0;
				if ( $person_days <= 0 ) continue;
				$cost_per_day = round($dc['cost_brutto'], 2);
				$total        = round($cost_per_day * $person_days, 2);
				$label        = sprintf(
					/* translators: 1: diet name, 2: person-days, 3: daily cost */
					__('%1$s — %2$d os./dzień × %3$s zł', 'basemgmt'),
					$dc['name'],
					$person_days,
					number_format($cost_per_day, 2, ',', ' ')
				);
				$wpdb->insert($tbl, [
					'camp_id'      => $camp_id,
					'payment_type' => 'food',
					'label'        => $label,
					'amount'       => $total,
					'amount_type'  => 'fixed',
					'due_date'     => $calc_due($dc['days_before'], $arrival),
					'status'       => 'expected',
				]);
			}

			update_post_meta($camp_id, '_bm_finance_package_id', $apply_package);
			AdminMenu::set_notice(__('Pakiet finansowy zastosowany — harmonogram uzupełniony na podstawie deklaracji.', 'basemgmt'));
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-finance");
			return;
		}

		// Manual save.
		$ids         = (array) ($_POST['sched_id']           ?? []);
		$labels      = (array) ($_POST['sched_label']        ?? []);
		$types       = (array) ($_POST['sched_type']         ?? []);
		$amounts     = (array) ($_POST['sched_amount']       ?? []);
		$amount_types = (array) ($_POST['sched_amount_type'] ?? []);
		$discounts   = (array) ($_POST['sched_discount']     ?? []);
		$discount_types = (array) ($_POST['sched_discount_type'] ?? []);
		$due_dates   = (array) ($_POST['sched_due_date']     ?? []);
		$statuses    = (array) ($_POST['sched_status']       ?? []);

		// Save global discount.
		$global_discount      = (float) str_replace(',', '.', sanitize_text_field($_POST['global_discount'] ?? '0'));
		$global_discount_type = in_array(sanitize_key($_POST['global_discount_type'] ?? 'fixed'), ['fixed', 'percent'], true)
			? sanitize_key($_POST['global_discount_type'])
			: 'fixed';
		update_post_meta($camp_id, '_bm_finance_global_discount', $global_discount);
		update_post_meta($camp_id, '_bm_finance_global_discount_type', $global_discount_type);

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
				'camp_id'       => $camp_id,
				'payment_type'  => sanitize_key($types[$i] ?? 'other'),
				'label'         => $label,
				'amount'        => (float) str_replace(',', '.', $amounts[$i] ?? '0'),
				'amount_type'   => 'fixed',
				'discount'      => max(0.0, (float) str_replace(',', '.', $discounts[$i] ?? '0')),
				'discount_type' => in_array(sanitize_key($discount_types[$i] ?? 'fixed'), ['fixed', 'percent'], true) ? sanitize_key($discount_types[$i] ?? 'fixed') : 'fixed',
				'due_date'      => sanitize_text_field($due_dates[$i] ?? '') ?: null,
				'status'        => sanitize_key($statuses[$i] ?? 'expected'),
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
	 * Auto-add documents and declaration docs marked as auto-add to a newly created camp.
	 * Note: task templates are NOT auto-added (must be added manually via "Dodaj z szablonu").
	 */
	private function auto_add_templates_to_camp( int $camp_id ): void {
		global $wpdb;

		// Auto-add doc templates
		$doc_templates = $wpdb->get_results(
			"SELECT * FROM " . Schema::table('doc_templates') . " WHERE auto_add = 1 ORDER BY sort_order ASC, id ASC"
		) ?: [];
		$camp = CampRepository::get($camp_id);
		$org  = CampCaseRepository::get_organizer($camp_id);
		$pre  = CampCaseRepository::get_prearrival($camp_id);
		foreach ( $doc_templates as $tpl ) {
			$vars = [
				'{{camp_name}}'       => $camp->name ?? '',
				'{{organizer_name}}'  => $org->organization_name ?? '',
				'{{organizer_email}}' => $org->contact_email ?? '',
				'{{start_date}}'      => $camp->start_date ?? '',
				'{{end_date}}'        => $camp->end_date ?? '',
				'{{participants}}'    => (string) ($pre->declared_participants ?? 0),
			];
			$html = str_replace(array_keys($vars), array_values($vars), $tpl->html_content ?? '');
			$wpdb->insert(Schema::table('camp_documents'), [
				'camp_id'       => $camp_id,
				'document_type' => $tpl->doc_type,
				'doc_category'  => 'template',
				'title'         => $tpl->title,
				'status'        => 'draft',
				'template_id'   => $tpl->id,
				'html_content'  => $html,
			]);
		}

		// Auto-add doc library items
		$lib_items = $wpdb->get_results(
			"SELECT * FROM " . Schema::table('doc_library') . " WHERE auto_add = 1 ORDER BY sort_order ASC, id ASC"
		) ?: [];
		foreach ( $lib_items as $lib_doc ) {
			$wpdb->insert(Schema::table('camp_documents'), [
				'camp_id'       => $camp_id,
				'document_type' => $lib_doc->doc_type,
				'doc_category'  => 'library',
				'title'         => $lib_doc->title,
				'status'        => 'ready',
				'file_id'       => $lib_doc->file_id,
				'file_url'      => $lib_doc->file_url,
			]);
		}

		// Auto-add declaration templates
		$decl_templates = \BaseMgmt\Admin\Pages\OrgDeclarationsPage::get_all(true);
		foreach ( $decl_templates as $dtpl ) {
			$vars = [
				'{{camp_name}}'       => $camp->name ?? '',
				'{{organizer_name}}'  => $org->organization_name ?? '',
				'{{start_date}}'      => $camp->start_date ?? '',
				'{{end_date}}'        => $camp->end_date ?? '',
				'{{participants}}'    => (string) ($pre->declared_participants ?? 0),
			];
			$html = str_replace(array_keys($vars), array_values($vars), $dtpl->html_content ?? '');
			$wpdb->insert(Schema::table('camp_decl_docs'), [
				'camp_id'     => $camp_id,
				'template_id' => $dtpl->id,
				'title'       => $dtpl->title,
				'status'      => 'draft',
				'html_content'=> $html,
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
		if ( $camp_id <= 0 ) {
			$this->redirect_back('basemgmt-camps');
			return;
		}
		global $wpdb;
		$tbl = Schema::table('camp_declarations');

		// Save global declaration header (is_active, notes)
		$header = [
			'camp_id'   => $camp_id,
			'is_active' => ! empty($_POST['decl_is_active']) ? 1 : 0,
			'notes'     => sanitize_textarea_field($_POST['decl_notes'] ?? ''),
		];
		$existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$tbl} WHERE camp_id = %d", $camp_id));
		if ( $existing ) {
			$wpdb->update($tbl, $header, ['camp_id' => $camp_id]);
		} else {
			$wpdb->insert($tbl, $header);
		}

		// Save per-day data
		$days_input = $_POST['days'] ?? []; // phpcs:ignore WordPress.Security.NonceVerification
		if ( is_array($days_input) ) {
			$tbl_days  = Schema::table('camp_declaration_days');
			$tbl_diets = Schema::table('camp_declaration_diet_lines');
			$tbl_accom = Schema::table('camp_declaration_accommodation_lines');

			foreach ( $days_input as $date_raw => $day_data ) {
				$date = sanitize_text_field($date_raw);
				if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ) {
					continue;
				}

				$day_row = [
					'camp_id'          => $camp_id,
					'declaration_date' => $date,
					'declared_persons' => (int) ($day_data['persons'] ?? 0),
					'arrival_time'     => sanitize_text_field($day_data['arrival_time'] ?? ''),
					'departure_time'   => sanitize_text_field($day_data['departure_time'] ?? ''),
				];

				$existing_day = $wpdb->get_row($wpdb->prepare(
					"SELECT id FROM {$tbl_days} WHERE camp_id = %d AND declaration_date = %s",
					$camp_id,
					$date
				));

				if ( $existing_day ) {
					$wpdb->update($tbl_days, $day_row, ['id' => (int) $existing_day->id]);
					$day_id = (int) $existing_day->id;
				} else {
					$wpdb->insert($tbl_days, $day_row);
					$day_id = (int) $wpdb->insert_id;
				}

				if ( $day_id <= 0 ) {
					continue;
				}

				// Diet lines: delete then re-insert
				$wpdb->delete($tbl_diets, ['day_id' => $day_id]);
				$diets_input = $day_data['diets'] ?? [];
				if ( is_array($diets_input) ) {
					foreach ( $diets_input as $diet_id => $cnt ) {
						$cnt = (int) $cnt;
						if ( $cnt > 0 ) {
							$wpdb->insert($tbl_diets, [
								'day_id'  => $day_id,
								'diet_id' => (int) $diet_id,
								'count'   => $cnt,
							]);
						}
					}
				}

				// Accommodation lines: delete then re-insert
				$wpdb->delete($tbl_accom, ['day_id' => $day_id]);
				$accom_input = $day_data['accommodations'] ?? [];
				if ( is_array($accom_input) ) {
					foreach ( $accom_input as $type_id => $cnt ) {
						$cnt = (int) $cnt;
						if ( $cnt > 0 ) {
							$wpdb->insert($tbl_accom, [
								'day_id'                => $day_id,
								'accommodation_type_id' => (int) $type_id,
								'count'                 => $cnt,
							]);
						}
					}
				}
			}
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

	// ── Equipment handlers ────────────────────────────────────────────────────

	public function handle_add_camp_equipment(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_add_camp_equipment');
		$camp_id = (int) ($_POST['camp_id'] ?? 0);
		if ( $camp_id <= 0 ) {
			$this->redirect_back('basemgmt-camps');
			return;
		}
		global $wpdb;
		$name = sanitize_text_field(wp_unslash($_POST['equipment_name'] ?? ''));
		if ( empty($name) ) {
			AdminMenu::set_notice(__('Nazwa sprzętu jest wymagana.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-equipment");
			return;
		}
		$wpdb->insert(Schema::table('camp_equipment'), [
			'camp_id'        => $camp_id,
			'equipment_type' => sanitize_text_field(wp_unslash($_POST['equipment_type'] ?? '')),
			'name'           => $name,
			'issued_qty'     => max(0, (int) ($_POST['issued_qty'] ?? 0)),
			'returned_qty'   => 0,
			'notes'          => sanitize_textarea_field(wp_unslash($_POST['equipment_notes'] ?? '')),
		]);
		AdminMenu::set_notice(__('Sprzęt dodany.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-equipment");
	}

	public function handle_return_camp_equipment(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_return_camp_equipment');
		$camp_id  = (int) ($_POST['camp_id'] ?? 0);
		$equip_id = (int) ($_POST['equip_id'] ?? 0);
		$qty      = max(0, (int) ($_POST['qty'] ?? 0));
		if ( $equip_id <= 0 || $camp_id <= 0 ) {
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-equipment");
			return;
		}
		global $wpdb;
		$row = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . Schema::table('camp_equipment') . " WHERE id = %d AND camp_id = %d",
			$equip_id, $camp_id
		));
		if ( ! $row ) {
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-equipment");
			return;
		}
		$new_returned = min((int) $row->issued_qty, (int) $row->returned_qty + $qty);
		$wpdb->update(Schema::table('camp_equipment'), ['returned_qty' => $new_returned], ['id' => $equip_id]);
		AdminMenu::set_notice(sprintf(__('Zarejestrowano zwrot %d szt.', 'basemgmt'), $qty));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-equipment");
	}

	public function handle_delete_camp_equipment(): void {
		Capabilities::require_admin();
		$camp_id  = (int) ($_GET['id'] ?? 0);
		$equip_id = (int) ($_GET['equip_id'] ?? 0);
		check_admin_referer("bm_delete_equipment_{$equip_id}");
		global $wpdb;
		$wpdb->delete(Schema::table('camp_equipment'), ['id' => $equip_id, 'camp_id' => $camp_id]);
		AdminMenu::set_notice(__('Sprzęt usunięty.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-equipment");
	}

	// ── Declaration doc handlers ───────────────────────────────────────────────

	public function handle_add_camp_decl_doc(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_add_camp_decl_doc');
		$camp_id     = (int) ($_POST['camp_id'] ?? 0);
		$template_id = (int) ($_POST['decl_template_id'] ?? 0);
		if ( $camp_id <= 0 || $template_id <= 0 ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}
		global $wpdb;
		$tpl  = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Schema::table('decl_templates') . " WHERE id = %d", $template_id));
		$camp = CampRepository::get($camp_id);
		$org  = CampCaseRepository::get_organizer($camp_id);
		$pre  = CampCaseRepository::get_prearrival($camp_id);
		if ( ! $tpl || ! $camp ) {
			AdminMenu::set_notice(__('Nie znaleziono szablonu lub obozu.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}
		$vars = [
			'{{camp_name}}'       => $camp->name,
			'{{organizer_name}}'  => $org->organization_name ?? '',
			'{{start_date}}'      => $camp->start_date,
			'{{end_date}}'        => $camp->end_date,
			'{{participants}}'    => (string) ($pre->declared_participants ?? 0),
		];
		$html = str_replace(array_keys($vars), array_values($vars), $tpl->html_content ?? '');
		$wpdb->insert(Schema::table('camp_decl_docs'), [
			'camp_id'      => $camp_id,
			'template_id'  => $template_id,
			'title'        => $tpl->title . ' — ' . $camp->name,
			'status'       => 'draft',
			'html_content' => $html,
		]);
		AdminMenu::set_notice(__('Deklaracja dodana do obozu.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
	}

	public function handle_delete_camp_decl_doc(): void {
		Capabilities::require_admin();
		$camp_id = (int) ($_GET['id'] ?? 0);
		$doc_id  = (int) ($_GET['decl_doc_id'] ?? 0);
		check_admin_referer("bm_delete_camp_decl_doc_{$doc_id}");
		global $wpdb;
		$wpdb->delete(Schema::table('camp_decl_docs'), ['id' => $doc_id, 'camp_id' => $camp_id]);
		AdminMenu::set_notice(__('Deklaracja usunięta.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
	}

	public function handle_approve_camp_decl_doc(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_approve_camp_decl_doc');
		$camp_id = (int) ($_POST['camp_id'] ?? 0);
		$doc_id  = (int) ($_POST['decl_doc_id'] ?? 0);
		if ( $camp_id <= 0 || $doc_id <= 0 ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}
		global $wpdb;
		$wpdb->update(Schema::table('camp_decl_docs'), [
			'status'      => 'approved',
			'approved_by' => get_current_user_id(),
			'approved_at' => current_time('mysql'),
		], ['id' => $doc_id, 'camp_id' => $camp_id]);
		AdminMenu::set_notice(__('Deklaracja zatwierdzona.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
	}

	// ── Damage handlers ───────────────────────────────────────────────────────

	public function handle_edit_camp_damage(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_edit_camp_damage');
		$camp_id   = (int) ($_POST['camp_id'] ?? 0);
		$damage_id = (int) ($_POST['damage_id'] ?? 0);
		if ( $camp_id <= 0 || $damage_id <= 0 ) {
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-finance");
			return;
		}
		global $wpdb;
		$wpdb->update(Schema::table('camp_damages'), [
			'name'        => sanitize_text_field(wp_unslash($_POST['damage_name'] ?? '')),
			'description' => sanitize_textarea_field(wp_unslash($_POST['damage_description'] ?? '')),
			'cost'        => (float) str_replace(',', '.', $_POST['damage_cost'] ?? '0'),
			'status'      => sanitize_key($_POST['damage_status'] ?? 'reported'),
		], ['id' => $damage_id, 'camp_id' => $camp_id]);
		AdminMenu::set_notice(__('Szkoda zaktualizowana.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-finance");
	}

	// ── Document signing handler ──────────────────────────────────────────────

	public function handle_sign_camp_doc(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_sign_camp_doc');
		$camp_id = (int) ($_POST['camp_id'] ?? 0);
		$doc_id  = (int) ($_POST['doc_id'] ?? 0);
		$method  = sanitize_key($_POST['sign_method'] ?? '');

		if ( $camp_id <= 0 || $doc_id <= 0 || ! in_array($method, ['qualified', 'scan'], true) ) {
			AdminMenu::set_notice(__('Nieprawidłowe dane.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}

		if ( empty($_FILES['signed_file']['name']) ) {
			AdminMenu::set_notice(__('Nie przesłano pliku.', 'basemgmt'), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}

		$allowed_types = $method === 'qualified' ? ['application/pdf'] : ['application/pdf', 'image/jpeg', 'image/png'];
		$file_type     = $_FILES['signed_file']['type'] ?? '';
		if ( ! in_array($file_type, $allowed_types, true) ) {
			AdminMenu::set_notice(
				$method === 'qualified'
					? __('Podpis kwalifikowany musi być plikiem PDF.', 'basemgmt')
					: __('Dozwolone formaty: PDF, JPG, PNG.', 'basemgmt'),
				'error'
			);
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}

		if ( $method === 'qualified' ) {
			$tmp_path = $_FILES['signed_file']['tmp_name'] ?? '';
			$content  = file_get_contents($tmp_path, false, null, 0, 65536);
			if ( $content === false || (
				strpos($content, '/ByteRange') === false &&
				strpos($content, '/Sig')       === false &&
				strpos($content, '/SigRef')    === false
			) ) {
				AdminMenu::set_notice(__('Plik PDF nie zawiera wykrywalnego podpisu cyfrowego. Upewnij się, że dokument jest podpisany podpisem kwalifikowanym.', 'basemgmt'), 'error');
				$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
				return;
			}
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$upload = wp_handle_upload($_FILES['signed_file'], ['test_form' => false]);
		if ( isset($upload['error']) ) {
			AdminMenu::set_notice(sprintf(__('Błąd uploadu: %s', 'basemgmt'), $upload['error']), 'error');
			$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
			return;
		}

		global $wpdb;
		$wpdb->update(Schema::table('camp_documents'), [
			'status'          => 'signed',
			'signed_method'   => $method,
			'signed_at'       => current_time('mysql'),
			'signed_by'       => get_current_user_id(),
			'signed_file_url' => $upload['url'],
		], ['id' => $doc_id, 'camp_id' => $camp_id]);

		AdminMenu::set_notice(__('Dokument oznaczony jako podpisany.', 'basemgmt'));
		$this->redirect_back("basemgmt-camps&action=edit&id={$camp_id}#bm-section-documents");
	}
}
