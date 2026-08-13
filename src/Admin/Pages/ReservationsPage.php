<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Camps\CampRepository;
use BaseMgmt\Modules\Reservations\ReservationRepository;
use BaseMgmt\Modules\Reservations\ResourceRepository;

defined('ABSPATH') || exit;

/**
 * Admin page for Rezerwacje (Reservations) module.
 */
final class ReservationsPage {

	public function render(): void {
		Capabilities::require_admin();

		$action = sanitize_key($_GET['bm_action'] ?? '');
		$id     = (int) ($_GET['id'] ?? 0);

		match ($action) {
			'resources'   => $this->render_resources(),
			'edit_resource' => $this->render_resource_edit($id),
			'new_resource'  => $this->render_resource_edit(0),
			default       => $this->render_list(),
		};
	}

	private function render_list(): void {
		$filter_resource = (int) ($_GET['filter_resource'] ?? 0);
		$filter_camp     = (int) ($_GET['filter_camp']     ?? 0);
		$filter_status   = sanitize_key($_GET['filter_status'] ?? '');
		$filter_date     = sanitize_text_field($_GET['filter_date'] ?? '');

		$filters = array_filter([
			'resource_id' => $filter_resource ?: null,
			'camp_id'     => $filter_camp     ?: null,
			'status'      => $filter_status   ?: null,
			'date_from'   => $filter_date     ?: null,
			'date_to'     => $filter_date     ?: null,
		]);

		$reservations = ReservationRepository::get_all($filters);
		$resources    = ResourceRepository::get_all();
		$camps        = CampRepository::get_all(['status' => 'active']);
		$statuses     = ReservationRepository::STATUSES;

		include BASEMGMT_DIR . 'templates/admin/reservations/list.php';
	}

	private function render_resources(): void {
		$resources = ResourceRepository::get_all();
		include BASEMGMT_DIR . 'templates/admin/reservations/resources.php';
	}

	private function render_resource_edit(int $id): void {
		$resource = $id ? ResourceRepository::get($id) : null;
		$blocks   = $id ? ResourceRepository::get_blocks($id) : [];
		$types    = ResourceRepository::TYPES;
		include BASEMGMT_DIR . 'templates/admin/reservations/resource_edit.php';
	}

	// ── Form handlers ─────────────────────────────────────────────────────────

	public function handle_save_resource(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_resource');

		$id = (int) ($_POST['resource_id'] ?? 0);
		$data = [
			'name'                      => sanitize_text_field($_POST['name']                 ?? ''),
			'type'                      => sanitize_key($_POST['type']                        ?? ResourceRepository::TYPE_OTHER),
			'description'               => sanitize_textarea_field($_POST['description']      ?? ''),
			'status'                    => sanitize_key($_POST['status']                      ?? 'active'),
			'rules'                     => sanitize_textarea_field($_POST['rules']            ?? ''),
			'available_from'            => sanitize_text_field($_POST['available_from']       ?? '06:00'),
			'available_to'              => sanitize_text_field($_POST['available_to']         ?? '22:00'),
			'min_duration_minutes'      => (int) ($_POST['min_duration_minutes']              ?? 0),
			'max_duration_minutes'      => (int) ($_POST['max_duration_minutes']              ?? 0),
			'min_advance_hours'         => (int) ($_POST['min_advance_hours']                 ?? 0),
			'max_advance_days'          => (int) ($_POST['max_advance_days']                  ?? 30),
			'cancel_advance_hours'      => (int) ($_POST['cancel_advance_hours']              ?? 0),
			'max_reservations_per_camp' => (int) ($_POST['max_reservations_per_camp']         ?? 0),
			'is_blocked'                => (int) ($_POST['is_blocked']                        ?? 0),
			'block_reason'              => sanitize_text_field($_POST['block_reason']         ?? ''),
			'block_from'                => sanitize_text_field($_POST['block_from']           ?? ''),
			'block_to'                  => sanitize_text_field($_POST['block_to']             ?? ''),
			'cost_per_reservation'      => (float) str_replace(',', '.', wp_unslash($_POST['cost_per_reservation'] ?? '0')),
			'pricing_mode'              => in_array($_POST['pricing_mode'] ?? '', ['flat', 'per_unit'], true) ? $_POST['pricing_mode'] : 'flat',
			'total_units'               => max(0, (int) ($_POST['total_units'] ?? 0)),
		];

		if ( $id ) {
			ResourceRepository::update($id, $data);
			AdminMenu::set_notice(__('Zasób zaktualizowany.', 'basemgmt'));
		} else {
			$id = ResourceRepository::create($data);
			AdminMenu::set_notice(__('Zasób dodany.', 'basemgmt'));
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-reservations&bm_action=resources'));
		exit;
	}

	public function handle_delete_resource(): void {
		Capabilities::require_admin();
		$id = (int) ($_GET['id'] ?? 0);
		check_admin_referer('bm_delete_resource_' . $id);
		if ( $id ) ResourceRepository::delete($id);
		AdminMenu::set_notice(__('Zasób usunięty.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-reservations&bm_action=resources'));
		exit;
	}

	public function handle_save_block(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_save_resource_block');
		$resource_id = (int) ($_POST['resource_id'] ?? 0);
		ResourceRepository::create_block([
			'resource_id' => $resource_id,
			'reason'      => sanitize_text_field($_POST['reason']     ?? ''),
			'block_from'  => sanitize_text_field($_POST['block_from'] ?? ''),
			'block_to'    => sanitize_text_field($_POST['block_to']   ?? ''),
			'created_by'  => get_current_user_id(),
		]);
		AdminMenu::set_notice(__('Blokada techniczna dodana.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-reservations&bm_action=edit_resource&id=' . $resource_id));
		exit;
	}

	public function handle_delete_block(): void {
		Capabilities::require_admin();
		$id          = (int) ($_GET['id']          ?? 0);
		$resource_id = (int) ($_GET['resource_id'] ?? 0);
		check_admin_referer('bm_delete_block_' . $id);
		if ( $id ) ResourceRepository::delete_block($id);
		AdminMenu::set_notice(__('Blokada usunięta.', 'basemgmt'));
		wp_safe_redirect(admin_url('admin.php?page=basemgmt-reservations&bm_action=edit_resource&id=' . $resource_id));
		exit;
	}

	public function handle_reservation_action(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_reservation_action');

		$id      = (int) ($_POST['reservation_id'] ?? 0);
		$action  = sanitize_key($_POST['res_action'] ?? '');
		$comment = sanitize_textarea_field($_POST['comment'] ?? '');

		$status_map = [
			'approve' => ReservationRepository::STATUS_APPROVED,
			'reject'  => ReservationRepository::STATUS_REJECTED,
			'cancel'  => ReservationRepository::STATUS_CANCELLED,
		];

		if ( $id && isset($status_map[$action]) ) {
			$new_status = $status_map[$action];
			ReservationRepository::update_status($id, $new_status, $comment, get_current_user_id());
			if ( $new_status === ReservationRepository::STATUS_APPROVED ) {
				$this->maybe_add_reservation_finance_line($id);
			}
			AdminMenu::set_notice(__('Status rezerwacji zaktualizowany.', 'basemgmt'));
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-reservations'));
		exit;
	}

	public function handle_admin_create_reservation(): void {
		Capabilities::require_admin();
		check_admin_referer('bm_admin_create_reservation');

		$result = ReservationRepository::admin_create([
			'resource_id'    => (int) ($_POST['resource_id'] ?? 0),
			'camp_id'        => (int) ($_POST['camp_id']     ?? 0),
			'staff_id'       => 0,
			'res_date'       => sanitize_text_field($_POST['res_date']       ?? ''),
			'start_time'     => sanitize_text_field($_POST['start_time']     ?? ''),
			'end_time'       => sanitize_text_field($_POST['end_time']       ?? ''),
			'purpose'        => sanitize_textarea_field($_POST['purpose']    ?? ''),
			'reserved_units' => max(0, (int) ($_POST['reserved_units']       ?? 0)),
		]);

		if ( isset($result['error']) ) {
			$msgs = [
				'conflict'         => __('Termin jest już zajęty.', 'basemgmt'),
				'blocked'          => __('Zasób ma aktywną blokadę techniczną.', 'basemgmt'),
				'unavailable'      => __('Zasób niedostępny lub poza godzinami dostępności.', 'basemgmt'),
				'too_short'        => __('Czas rezerwacji jest za krótki.', 'basemgmt'),
				'too_long'         => __('Czas rezerwacji jest za długi.', 'basemgmt'),
				'units_required'   => __('Podaj liczbę sztuk do wypożyczenia.', 'basemgmt'),
				'units_unavailable'=> __('Niewystarczająca liczba dostępnych sztuk.', 'basemgmt'),
			];
			AdminMenu::set_notice($msgs[$result['error']] ?? __('Błąd tworzenia rezerwacji.', 'basemgmt'), 'error');
		} else {
			// Auto-approve admin-created reservations.
			ReservationRepository::update_status($result['id'], ReservationRepository::STATUS_APPROVED, '', get_current_user_id());
			$this->maybe_add_reservation_finance_line($result['id']);
			AdminMenu::set_notice(__('Rezerwacja dodana i zatwierdzona.', 'basemgmt'));
		}

		wp_safe_redirect(admin_url('admin.php?page=basemgmt-reservations'));
		exit;
	}

	/**
	 * If the reserved resource has a cost_per_reservation > 0, automatically
	 * insert a payment-schedule line in the camp's finance tab.
	 * For per_unit resources: amount = reserved_units × cost_per_reservation.
	 */
	private function maybe_add_reservation_finance_line(int $reservation_id): void {
		global $wpdb;
		$res = ReservationRepository::get($reservation_id);
		if ( ! $res ) {
			return;
		}
		$resource = ResourceRepository::get((int) $res->resource_id);
		if ( ! $resource || (float) $resource->cost_per_reservation <= 0 ) {
			return;
		}
		$camp_id     = (int) $res->camp_id;
		$pricing_mode = $resource->pricing_mode ?? 'flat';
		$cost         = (float) $resource->cost_per_reservation;

		if ( $pricing_mode === 'per_unit' ) {
			$units  = max(1, (int) ($res->reserved_units ?? 1));
			$amount = $cost * $units;
			$label  = sprintf(
				// translators: %1$s = resource name, %2$s = units, %3$s = date
				__('Rezerwacja: %1$s × %2$d szt. (%3$s)', 'basemgmt'),
				$resource->name, $units, $res->res_date
			);
		} else {
			$amount = $cost;
			$label  = sprintf(
				// translators: %1$s = resource name, %2$s = reservation date
				__('Rezerwacja: %1$s (%2$s)', 'basemgmt'),
				$resource->name, $res->res_date
			);
		}

		$tbl = \BaseMgmt\Database\Schema::table('camp_payment_schedules');
		$wpdb->insert($tbl, [
			'camp_id'      => $camp_id,
			'payment_type' => 'extra_fee',
			'label'        => $label,
			'amount'       => $amount,
			'due_date'     => $res->res_date,
			'status'       => 'expected',
			'description'  => sprintf('%s %s–%s', $res->res_date, $res->start_time, $res->end_time),
		]);
	}
}
