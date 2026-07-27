<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Reservations;

use BaseMgmt\Core\EmailService;
use BaseMgmt\Modules\Camps\CampRepository;

defined('ABSPATH') || exit;

/**
 * Sends email notifications on reservation lifecycle events.
 *
 * Hooks registered in Bootstrap:
 *   bm_reservation_created        → notify_created()
 *   bm_reservation_status_changed → notify_status_changed()
 *
 * The camp's staff email is retrieved from the staff member who created the reservation.
 * The admin notification email comes from EmailService settings (admin_notify_email).
 */
final class ReservationNotifier {

	public function register(): void {
		add_action('bm_reservation_created',        [$this, 'notify_created'],        10, 2);
		add_action('bm_reservation_status_changed', [$this, 'notify_status_changed'], 10, 3);
	}

	public function notify_created(int $reservation_id, array $original_data): void {
		$reservation = ReservationRepository::get($reservation_id);
		if ( ! $reservation ) return;

		[$resource_name, $camp_name, $camp_email] = $this->get_context($reservation);

		$data = [
			'reservation'   => (array) $reservation,
			'resource_name' => $resource_name,
			'camp_name'     => $camp_name,
		];

		// Notify admin.
		$settings     = EmailService::get_settings();
		$admin_email  = $settings['admin_notify_email'];
		if ( $admin_email ) {
			EmailService::send(
				$admin_email,
				EmailService::subject(sprintf(
					/* translators: %1$s resource, %2$s camp */
					__('Nowa rezerwacja: %1$s – %2$s', 'basemgmt'),
					$resource_name,
					$camp_name
				)),
				'reservation_created',
				$data + ['is_admin' => true, 'subject' => '']
			);
		}

		// Notify camp staff (if email available).
		if ( $camp_email ) {
			EmailService::send(
				$camp_email,
				EmailService::subject(__('Potwierdzenie rezerwacji', 'basemgmt')),
				'reservation_created',
				$data + ['is_admin' => false, 'subject' => '']
			);
		}
	}

	public function notify_status_changed(int $reservation_id, string $new_status, int $user_id): void {
		$reservation = ReservationRepository::get($reservation_id);
		if ( ! $reservation ) return;

		[$resource_name, $camp_name, $camp_email] = $this->get_context($reservation);

		if ( ! $camp_email ) return;

		$data = [
			'reservation'        => (array) $reservation,
			'resource_name'      => $resource_name,
			'camp_name'          => $camp_name,
			'admin_comment'      => $reservation->admin_comment ?? '',
			'cancelled_by_admin' => $user_id > 0,
			'subject'            => '',
		];

		match ($new_status) {
			ReservationRepository::STATUS_APPROVED => EmailService::send(
				$camp_email,
				EmailService::subject(sprintf(__('Rezerwacja zatwierdzona: %s', 'basemgmt'), $resource_name)),
				'reservation_approved',
				$data
			),
			ReservationRepository::STATUS_REJECTED => EmailService::send(
				$camp_email,
				EmailService::subject(sprintf(__('Rezerwacja odrzucona: %s', 'basemgmt'), $resource_name)),
				'reservation_rejected',
				$data
			),
			ReservationRepository::STATUS_CANCELLED => EmailService::send(
				$camp_email,
				EmailService::subject(sprintf(__('Rezerwacja anulowana: %s', 'basemgmt'), $resource_name)),
				'reservation_cancelled',
				$data
			),
			default => null,
		};
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Returns [resource_name, camp_name, camp_contact_email].
	 * Camp email: uses the submitting staff member's email, or first active staff.
	 */
	private function get_context(object $reservation): array {
		global $wpdb;

		$resource = ResourceRepository::get((int) $reservation->resource_id);
		$resource_name = $resource ? $resource->name : (string) $reservation->resource_id;

		$camp = CampRepository::get((int) $reservation->camp_id);
		$camp_name = $camp ? $camp->name : (string) $reservation->camp_id;

		// Get staff email.
		$camp_email = '';
		if ( (int) $reservation->staff_id > 0 ) {
			$t = $wpdb->prefix . 'bm_staff';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$email = $wpdb->get_var($wpdb->prepare("SELECT email FROM $t WHERE id = %d", $reservation->staff_id));
			if ( $email ) $camp_email = $email;
		}

		if ( ! $camp_email ) {
			// Fall back to first active staff member with email.
			$t = $wpdb->prefix . 'bm_staff';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$email = $wpdb->get_var($wpdb->prepare(
				"SELECT email FROM $t WHERE camp_id = %d AND is_active = 1 AND email != '' LIMIT 1",
				$reservation->camp_id
			));
			if ( $email ) $camp_email = $email;
		}

		return [$resource_name, $camp_name, $camp_email];
	}
}
