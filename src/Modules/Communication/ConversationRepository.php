<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Communication;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Data access for Komunikacja (Communication) module.
 *
 * Tables:
 *   bm_conv_threads  – one thread per conversation topic
 *   bm_conv_messages – messages inside a thread
 *
 * Read tracking is done via unread_admin / unread_camp counters on the thread
 * row.  Simple, atomic, no extra table.  Sufficient for an async inbox model.
 */
final class ConversationRepository {

	// ── Status constants ──────────────────────────────────────────────────────

	public const STATUS_OPEN        = 'open';
	public const STATUS_IN_PROGRESS = 'in_progress';
	public const STATUS_CLOSED      = 'closed';
	public const STATUS_ARCHIVED    = 'archived';

	public const STATUSES = [
		self::STATUS_OPEN        => 'Otwarty',
		self::STATUS_IN_PROGRESS => 'W toku',
		self::STATUS_CLOSED      => 'Zamknięty',
		self::STATUS_ARCHIVED    => 'Zarchiwizowany',
	];

	// ── Priority constants ────────────────────────────────────────────────────

	public const PRIORITY_NORMAL = 'normal';
	public const PRIORITY_HIGH   = 'high';
	public const PRIORITY_URGENT = 'urgent';

	public const PRIORITIES = [
		self::PRIORITY_NORMAL => 'Normalny',
		self::PRIORITY_HIGH   => 'Wysoki',
		self::PRIORITY_URGENT => 'Pilny',
	];

	// ── Threads ───────────────────────────────────────────────────────────────

	public static function get_all_threads(array $filters = []): array {
		global $wpdb;
		$t     = Schema::table('conv_threads');
		$where = ['1=1'];
		$vals  = [];

		if ( ! empty($filters['camp_id']) ) {
			$where[] = 'camp_id = %d';
			$vals[]  = (int) $filters['camp_id'];
		}
		if ( ! empty($filters['status']) && $filters['status'] !== 'all' ) {
			$where[] = 'status = %s';
			$vals[]  = $filters['status'];
		}
		if ( ! empty($filters['priority']) ) {
			$where[] = 'priority = %s';
			$vals[]  = $filters['priority'];
		}
		if ( ! empty($filters['is_urgent']) ) {
			$where[] = 'is_urgent = 1';
		}
		if ( ! empty($filters['unread_admin']) ) {
			$where[] = 'unread_admin > 0';
		}
		if ( ! empty($filters['assigned_to']) ) {
			$where[] = 'assigned_to = %d';
			$vals[]  = (int) $filters['assigned_to'];
		}

		$sql = "SELECT * FROM {$t} WHERE " . implode(' AND ', $where)
			. ' ORDER BY is_urgent DESC, last_message_at DESC';

		if ( ! empty($vals) ) {
			$sql = $wpdb->prepare($sql, ...$vals);
		}

		return $wpdb->get_results($sql) ?: [];
	}

	public static function get_thread(int $id): ?object {
		global $wpdb;
		$t = Schema::table('conv_threads');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id)) ?: null;
	}

	/** Get thread only if it belongs to the given camp (security boundary). */
	public static function get_thread_for_camp(int $id, int $camp_id): ?object {
		global $wpdb;
		$t = Schema::table('conv_threads');
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$t} WHERE id = %d AND camp_id = %d", $id, $camp_id)
		) ?: null;
	}

	public static function create_thread(array $data): int {
		global $wpdb;
		$t = Schema::table('conv_threads');

		$wpdb->insert($t, [
			'camp_id'             => (int) $data['camp_id'],
			'subject'             => sanitize_text_field($data['subject'] ?? ''),
			'status'              => self::STATUS_OPEN,
			'priority'            => sanitize_key($data['priority'] ?? self::PRIORITY_NORMAL),
			'is_urgent'           => (int) ($data['is_urgent'] ?? 0),
			'assigned_to'         => ! empty($data['assigned_to']) ? (int) $data['assigned_to'] : null,
			'last_message_at'     => current_time('mysql'),
			'unread_admin'        => 1,
			'unread_camp'         => 0,
			'created_by_staff_id' => (int) ($data['created_by_staff_id'] ?? 0),
		]);

		return (int) $wpdb->insert_id;
	}

	public static function update_thread(int $id, array $data): void {
		global $wpdb;
		$t       = Schema::table('conv_threads');
		$payload = [];

		if ( isset($data['status']) )      $payload['status']      = sanitize_key($data['status']);
		if ( isset($data['priority']) )    $payload['priority']    = sanitize_key($data['priority']);
		if ( isset($data['is_urgent']) )   $payload['is_urgent']   = (int) $data['is_urgent'];
		if ( isset($data['subject']) )     $payload['subject']     = sanitize_text_field($data['subject']);
		if ( array_key_exists('assigned_to', $data) ) {
			$payload['assigned_to'] = ! empty($data['assigned_to']) ? (int) $data['assigned_to'] : null;
		}

		if ( ! empty($payload) ) {
			$wpdb->update($t, $payload, ['id' => $id]);
		}
	}

	public static function count_open_threads(): int {
		global $wpdb;
		$t = Schema::table('conv_threads');
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$t} WHERE status IN ('open','in_progress')"
		);
	}

	public static function count_unread_admin(): int {
		global $wpdb;
		$t = Schema::table('conv_threads');
		return (int) $wpdb->get_var(
			"SELECT SUM(unread_admin) FROM {$t} WHERE status NOT IN ('closed','archived')"
		);
	}

	// ── Messages ──────────────────────────────────────────────────────────────

	public static function get_messages(int $thread_id): array {
		global $wpdb;
		$t = Schema::table('conv_messages');
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$t} WHERE thread_id = %d ORDER BY created_at ASC",
				$thread_id
			)
		) ?: [];
	}

	/**
	 * Add a message and atomically update the thread's unread counters and
	 * last_message_at timestamp.
	 *
	 * author_type: 'admin' | 'staff'
	 */
	public static function add_message(array $data): int {
		global $wpdb;
		$tm  = Schema::table('conv_messages');
		$tt  = Schema::table('conv_threads');

		$wpdb->insert($tm, [
			'thread_id'      => (int) $data['thread_id'],
			'author_type'    => sanitize_key($data['author_type']),
			'author_id'      => (int) $data['author_id'],
			'content'        => wp_kses_post($data['content'] ?? ''),
			'is_system'      => (int) ($data['is_system'] ?? 0),
			'attachment_url' => esc_url_raw($data['attachment_url'] ?? ''),
		]);
		$msg_id = (int) $wpdb->insert_id;

		if ( $data['author_type'] === 'admin' ) {
			// Admin replied → clear admin unread, increment camp unread, advance to in_progress.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$tt}
					 SET last_message_at = %s,
					     unread_camp    = unread_camp + 1,
					     unread_admin   = 0,
					     status         = IF(status = 'open', 'in_progress', status)
					 WHERE id = %d",
					current_time('mysql'),
					(int) $data['thread_id']
				)
			);
		} else {
			// Camp replied → clear camp unread, increment admin unread.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$tt}
					 SET last_message_at = %s,
					     unread_admin   = unread_admin + 1,
					     unread_camp    = 0
					 WHERE id = %d",
					current_time('mysql'),
					(int) $data['thread_id']
				)
			);
		}

		return $msg_id;
	}

	public static function mark_read_admin(int $thread_id): void {
		global $wpdb;
		$wpdb->update(Schema::table('conv_threads'), ['unread_admin' => 0], ['id' => $thread_id]);
	}

	public static function mark_read_camp(int $thread_id): void {
		global $wpdb;
		$wpdb->update(Schema::table('conv_threads'), ['unread_camp' => 0], ['id' => $thread_id]);
	}
}
