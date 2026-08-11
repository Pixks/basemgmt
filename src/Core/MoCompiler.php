<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

defined('ABSPATH') || exit;

/**
 * Compiles PO translation files into binary MO files.
 *
 * The MO binary format is described at:
 * https://www.gnu.org/software/gettext/manual/html_node/MO-Files.html
 */
final class MoCompiler {

	/**
	 * Compile a .po file to a .mo binary file.
	 *
	 * @param string $po_path  Absolute path to the source .po file.
	 * @param string $mo_path  Absolute path to write the .mo output.
	 * @return bool
	 */
	public static function compile( string $po_path, string $mo_path ): bool {
		if ( ! is_readable( $po_path ) ) {
			return false;
		}

		$entries = self::parse_po( file_get_contents( $po_path ) ?: '' );
		if ( $entries === null ) {
			return false;
		}

		$binary = self::build_mo( $entries );
		return file_put_contents( $mo_path, $binary ) !== false;
	}

	/**
	 * Compile all .po files in the plugin languages directory.
	 *
	 * @return array<string, bool> [ filename => success ]
	 */
	public static function compile_all(): array {
		$dir     = BASEMGMT_DIR . 'languages/';
		$results = [];

		foreach ( glob( $dir . '*.po' ) ?: [] as $po ) {
			$mo      = substr( $po, 0, -3 ) . '.mo';
			$results[ basename( $po ) ] = self::compile( $po, $mo );
		}

		return $results;
	}

	// ── Private ───────────────────────────────────────────────────────────────

	/**
	 * Parse a PO file content into [ msgid => msgstr ] pairs.
	 *
	 * @return array<string,string>|null  null on parse failure
	 */
	private static function parse_po( string $content ): ?array {
		$entries  = [];
		$msgid    = null;
		$msgstr   = null;
		$in_msgid = false;
		$in_msgstr = false;

		$lines = explode( "\n", $content );
		foreach ( $lines as $line ) {
			$line = rtrim( $line );

			if ( str_starts_with( $line, '#' ) || $line === '' ) {
				// Blank line or comment: commit any pending entry.
				if ( $msgid !== null && $msgstr !== null ) {
					$entries[ self::unescape( $msgid ) ] = self::unescape( $msgstr );
				}
				$msgid    = null;
				$msgstr   = null;
				$in_msgid = false;
				$in_msgstr = false;
				continue;
			}

			if ( str_starts_with( $line, 'msgid ' ) ) {
				// Commit any previous pair first.
				if ( $msgid !== null && $msgstr !== null ) {
					$entries[ self::unescape( $msgid ) ] = self::unescape( $msgstr );
				}
				$msgid    = trim( substr( $line, 6 ), '"' );
				$msgstr   = null;
				$in_msgid = true;
				$in_msgstr = false;
				continue;
			}

			if ( str_starts_with( $line, 'msgstr ' ) ) {
				$msgstr   = trim( substr( $line, 7 ), '"' );
				$in_msgid = false;
				$in_msgstr = true;
				continue;
			}

			// Continuation line: starts with '"'.
			if ( $line[0] === '"' ) {
				$part = trim( $line, '"' );
				if ( $in_msgid && $msgid !== null ) {
					$msgid .= $part;
				} elseif ( $in_msgstr && $msgstr !== null ) {
					$msgstr .= $part;
				}
				continue;
			}
		}

		// Commit the last pair.
		if ( $msgid !== null && $msgstr !== null ) {
			$entries[ self::unescape( $msgid ) ] = self::unescape( $msgstr );
		}

		return $entries;
	}

	/**
	 * Convert PO escape sequences (\n, \t, \\, \") to their real characters.
	 */
	private static function unescape( string $s ): string {
		return str_replace(
			[ '\\n', '\\t', '\\"', '\\\\' ],
			[ "\n",  "\t",  '"',   '\\' ],
			$s
		);
	}

	/**
	 * Build the binary MO data from [ msgid => msgstr ] pairs.
	 */
	private static function build_mo( array $entries ): string {
		// Filter out empty translations and the fuzzy header marker.
		$pairs = [];
		foreach ( $entries as $orig => $trans ) {
			if ( $trans !== '' ) {
				$pairs[ $orig ] = $trans;
			}
		}

		// Sort by original string (MO spec requires lexicographic order for binary search).
		ksort( $pairs );

		$n = count( $pairs );

		// Offsets:
		// 0:  magic (4)
		// 4:  revision (4) = 0
		// 8:  N (4)
		// 12: orig_tab offset (4) = 28
		// 16: trans_tab offset (4) = 28 + N*8
		// 20: hash_tab_size (4) = 0
		// 24: hash_tab_offset (4) = 28 + N*16 (irrelevant, size=0)
		// 28: orig table  — N entries of (len, offset)
		// 28+N*8: trans table — N entries of (len, offset)
		// 28+N*16: string data

		$orig_tab_offset  = 28;
		$trans_tab_offset = 28 + $n * 8;
		$data_offset      = 28 + $n * 16;

		$orig_table  = '';
		$trans_table = '';
		$orig_data   = '';
		$trans_data  = '';
		$o_offset    = $data_offset;

		foreach ( $pairs as $orig => $trans ) {
			$orig_len     = strlen( $orig );
			$orig_table  .= pack( 'VV', $orig_len, $o_offset );
			$orig_data   .= $orig . "\0";
			$o_offset    += $orig_len + 1;
		}

		$t_offset = $o_offset;
		foreach ( $pairs as $orig => $trans ) {
			$trans_len    = strlen( $trans );
			$trans_table .= pack( 'VV', $trans_len, $t_offset );
			$trans_data  .= $trans . "\0";
			$t_offset    += $trans_len + 1;
		}

		$header = pack( 'V7',
			0x950412DE,       // magic (little-endian)
			0,                // revision
			$n,               // number of strings
			$orig_tab_offset,
			$trans_tab_offset,
			0,                // hash table size (none)
			$data_offset      // hash table offset (irrelevant)
		);

		return $header . $orig_table . $trans_table . $orig_data . $trans_data;
	}
}
