<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Core\OperationLogger;
use BaseMgmt\Database\Schema;
use BaseMgmt\Modules\Camps\CampCaseRepository;
use BaseMgmt\Modules\Camps\CampRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for managing camps (Obozy).
 */
final class CampsPage {

	public function render(): void {
		Capabilities::require_admin();
		if ( ! CampCaseRepository::tables_ready() ) {
			Schema::create_tables();
			CampCaseRepository::invalidate_tables_ready_cache();
		}

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
			'new'   => $this->render_edit_form(null),
			'edit'  => $this->render_edit_form(CampRepository::get($id)),
			default => $this->render_list(),
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
		$case          = null;
		$organizer     = null;
		$prearrival    = null;
		$checklist     = CampCaseRepository::default_checklist_rows();
		$history       = [];
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
			$case          = CampCaseRepository::get_case((int) $camp->id);
			$organizer     = CampCaseRepository::get_organizer((int) $camp->id);
			$prearrival    = CampCaseRepository::get_prearrival((int) $camp->id);
			$history       = CampCaseRepository::get_history((int) $camp->id);
			$readiness     = CampCaseRepository::get_readiness_summary((int) $camp->id);
			$future_counts = CampCaseRepository::get_future_module_counts((int) $camp->id);
			$checklist_db  = CampCaseRepository::get_checklist((int) $camp->id);
			if ( ! empty($checklist_db) ) {
				$checklist = array_map(
					static fn(object $item): array => [
						'label'       => (string) $item->label,
						'id'          => (string) $item->id,
						'party'       => (string) $item->party,
						'status'      => (string) $item->status,
						'assigned_to' => (string) $item->assigned_to,
						'due_date'    => (string) ($item->due_date ?? ''),
						'comment'     => (string) ($item->comment ?? ''),
					],
					$checklist_db
				);
				$checklist = CampCaseRepository::pad_checklist_rows($checklist);
			}
		}

		$process_stages    = CampCaseRepository::process_stages();
		$risk_levels       = CampCaseRepository::risk_levels();
		$checklist_parties = CampCaseRepository::checklist_parties();
		$checklist_statuses= CampCaseRepository::checklist_statuses();
		$users             = get_users(['fields' => ['ID', 'display_name']]);

		include BASEMGMT_DIR . 'templates/admin/camps/edit.php';
	}

	public function handle_save(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_camp');
		if ( ! CampCaseRepository::tables_ready() ) {
			Schema::create_tables();
			CampCaseRepository::invalidate_tables_ready_cache();
		}

		$id       = (int) ($_POST['camp_id'] ?? 0);
		$campData = [
			'name'       => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
			'start_date' => sanitize_text_field(wp_unslash($_POST['start_date'] ?? '')),
			'end_date'   => sanitize_text_field(wp_unslash($_POST['end_date'] ?? '')),
			'status'     => sanitize_key(wp_unslash($_POST['status'] ?? 'active')),
		];

		if ( $campData['name'] === '' ) {
			AdminMenu::set_notice(__('Nazwa obozu jest wymagana.', 'basemgmt'), 'error');
			$this->redirect_back($id ? "basemgmt-camps&action=edit&id={$id}" : 'basemgmt-camps&action=new');
			return;
		}

		if ( $id ) {
			CampRepository::update($id, $campData);
			$camp_id = $id;
			$action  = OperationLogger::ACTION_CAMP_UPDATED;
		} else {
			$camp_id = (int) CampRepository::insert($campData);
			$action  = OperationLogger::ACTION_CAMP_CREATED;
		}

		if ( ! $camp_id ) {
			AdminMenu::set_notice(__('Błąd zapisu.', 'basemgmt'), 'error');
			$this->redirect_back('basemgmt-camps');
			return;
		}

		$caseData = [
			'process_stage'        => sanitize_key(wp_unslash($_POST['process_stage'] ?? '')),
			'needs_attention'      => ! empty($_POST['needs_attention']) ? '1' : '',
			'risk_level'           => sanitize_key(wp_unslash($_POST['risk_level'] ?? '')),
			'owner_user_id'        => (string) (int) wp_unslash($_POST['owner_user_id'] ?? '0'),
			'next_action_due_date' => sanitize_text_field(wp_unslash($_POST['next_action_due_date'] ?? '')),
			'notes'                => sanitize_textarea_field(wp_unslash($_POST['case_notes'] ?? '')),
			'readiness_notes'      => sanitize_textarea_field(wp_unslash($_POST['readiness_notes'] ?? '')),
			'stage_change_note'    => sanitize_textarea_field(wp_unslash($_POST['stage_change_note'] ?? '')),
		];
		$organizerData = [
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
		];
		$prearrivalData = [
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

		CampCaseRepository::save_case($camp_id, $caseData);
		CampCaseRepository::save_organizer($camp_id, $organizerData);
		CampCaseRepository::save_prearrival($camp_id, $prearrivalData);

		CampCaseRepository::replace_checklist($camp_id, $this->parse_checklist($_POST['checklist'] ?? []));

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

		OperationLogger::log(
			$action,
			'camp',
			$camp_id,
			[
				'name'          => $campData['name'],
				'status'        => $campData['status'],
				'process_stage' => $caseData['process_stage'] ?: CampCaseRepository::STAGE_INQUIRY,
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
		$ids         = array_values((array) ($raw['id'] ?? []));
		$assigned    = array_values((array) ($raw['assigned_to'] ?? []));
		$due_dates   = array_values((array) ($raw['due_date'] ?? []));
		$comments    = array_values((array) ($raw['comment'] ?? []));
		$total_rows  = max(count($labels), count($parties), count($statuses), count($ids), count($assigned), count($due_dates), count($comments));
		$items       = [];

		for ( $i = 0; $i < $total_rows; $i++ ) {
			$items[] = [
				'label'       => wp_unslash((string) ($labels[$i] ?? '')),
				'id'          => (string) (int) wp_unslash((string) ($ids[$i] ?? '0')),
				'party'       => wp_unslash((string) ($parties[$i] ?? CampCaseRepository::CHECKLIST_PARTY_SHARED)),
				'status'      => wp_unslash((string) ($statuses[$i] ?? CampCaseRepository::CHECKLIST_STATUS_PENDING)),
				'assigned_to' => wp_unslash((string) ($assigned[$i] ?? '')),
				'due_date'    => wp_unslash((string) ($due_dates[$i] ?? '')),
				'comment'     => wp_unslash((string) ($comments[$i] ?? '')),
			];
		}

		return $items;
	}

	private function redirect_back(string $page): void {
		wp_safe_redirect(admin_url("admin.php?page={$page}"));
		exit;
	}
}
