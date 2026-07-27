<?php

declare(strict_types=1);

namespace BaseMgmt\Auth;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

/**
 * Creates, validates and destroys frontend camp sessions.
 *
 * Token stored in HttpOnly + SameSite=Strict cookie.
 * Server-side record in bm_sessions table.
 */
final class SessionManager {

	private const COOKIE = BASEMGMT_SESSION_COOKIE;
	private const TTL    = BASEMGMT_SESSION_TTL;

	// ── Create ────────────────────────────────────────────────────────────────

	/**
	 * Persists a new session for the given staff/camp and sets the cookie.
	 */
	public static function create(int $staff_id, int $camp_id): string {
		global $wpdb;

		$token      = bin2hex(random_bytes(32)); // 64-char hex token
		$expires_at = gmdate('Y-m-d H:i:s', time() + self::TTL);
		$ip         = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));

		$wpdb->insert(
			Schema::table('sessions'),
			[
				'token'      => $token,
				'staff_id'   => $staff_id,
				'camp_id'    => $camp_id,
				'ip_address' => $ip,
				'expires_at' => $expires_at,
			],
			['%s', '%d', '%d', '%s', '%s']
		);

		self::set_cookie($token);

		return $token;
	}

	// ── Validate ─────────────────────────────────────────────────────────────

	/**
	 * Returns the session row if the cookie is valid and not expired.
	 * Returns null otherwise (do not reveal WHY it failed).
	 *
	 * @return object{id:int,token:string,staff_id:int,camp_id:int,expires_at:string}|null
	 */
	public static function current(): ?object {
		$token = self::read_cookie();
		if ( '' === $token ) {
			return null;
		}

		global $wpdb;

		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `" . Schema::table('sessions') . "` WHERE token = %s AND expires_at > %s LIMIT 1",
				$token,
				gmdate('Y-m-d H:i:s')
			)
		);

		return $session ?: null;
	}

	// ── Destroy ───────────────────────────────────────────────────────────────

	/**
	 * Invalidates the current session (logout).
	 */
	public static function destroy(): void {
		$token = self::read_cookie();
		if ( '' !== $token ) {
			global $wpdb;
			$wpdb->delete(Schema::table('sessions'), ['token' => $token], ['%s']);
		}

		self::clear_cookie();
	}

	// ── Cleanup ───────────────────────────────────────────────────────────────

	/** Called by cron to remove expired sessions. */
	public static function cleanup_expired(): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `" . Schema::table('sessions') . "` WHERE expires_at < %s",
				gmdate('Y-m-d H:i:s')
			)
		);
	}

	// ── Cookie helpers ────────────────────────────────────────────────────────

	private static function set_cookie(string $token): void {
		setcookie(
			self::COOKIE,
			$token,
			[
				'expires'  => time() + self::TTL,
				'path'     => COOKIEPATH ?: '/',
				'domain'   => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Strict',
			]
		);
		// Make available for current request without reload.
		$_COOKIE[self::COOKIE] = $token;
	}

	private static function clear_cookie(): void {
		setcookie(
			self::COOKIE,
			'',
			[
				'expires'  => time() - HOUR_IN_SECONDS,
				'path'     => COOKIEPATH ?: '/',
				'domain'   => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Strict',
			]
		);
		unset($_COOKIE[self::COOKIE]);
	}

	private static function read_cookie(): string {
		return sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE] ?? ''));
	}
}
