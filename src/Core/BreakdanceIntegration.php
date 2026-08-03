<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

defined('ABSPATH') || exit;

/**
 * Optional integration with Breakdance Element Studio.
 *
 * Registers three save locations inside this plugin's directory so that
 * elements, macros and presets created in Breakdance Studio are stored
 * alongside the plugin code (version-controllable, shareable).
 *
 * Directories created on first use (or plugin activation):
 *   breakdance/elements/
 *   breakdance/macros/
 *   breakdance/presets/
 *
 * Only activates when Breakdance fires 'breakdance_loaded'.
 * If Breakdance is not installed the hook never runs and nothing breaks.
 *
 * Reference: https://github.com/soflyy/breakdance-custom-elements
 */
final class BreakdanceIntegration {

	/** Subdirectory inside the plugin folder where BD assets are stored. */
	private const BASE_DIR = 'breakdance';

	/** Namespace prefix shown in Element Studio's save dialog. */
	private const NS = 'BasemgmtElements';

	public function register(): void {
		// Priority 9 – register before Breakdance loads the elements (priority 10+).
		add_action('breakdance_loaded', [$this, 'register_save_locations'], 9);
	}

	public function register_save_locations(): void {
		// Guard: ensure Breakdance's registration function exists.
		if ( ! function_exists('\Breakdance\ElementStudio\registerSaveLocation') ) {
			return;
		}
		if ( ! function_exists('\Breakdance\Util\getDirectoryPathRelativeToPluginFolder') ) {
			return;
		}

		// BASEMGMT_DIR has a trailing slash; trim it for getDirectoryPathRelativeToPluginFolder.
		$plugin_dir = rtrim(BASEMGMT_DIR, '/\\');

		// Convert absolute plugin path to a path relative to the WP plugins directory.
		// Breakdance requires this format to work across different server setups.
		$rel_base = \Breakdance\Util\getDirectoryPathRelativeToPluginFolder($plugin_dir);

		$locations = [
			[
				'path'  => $rel_base . '/' . self::BASE_DIR . '/elements',
				'type'  => 'element',
				'label' => 'Baza Obozowa – Elements',
			],
			[
				'path'  => $rel_base . '/' . self::BASE_DIR . '/macros',
				'type'  => 'macro',
				'label' => 'Baza Obozowa – Macros',
			],
			[
				'path'  => $rel_base . '/' . self::BASE_DIR . '/presets',
				'type'  => 'preset',
				'label' => 'Baza Obozowa – Presets',
			],
		];

		foreach ( $locations as $loc ) {
			// Ensure the physical directory exists before registering.
			$abs_path = $plugin_dir . '/' . self::BASE_DIR . '/' . $loc['type'] . 's';
			self::ensure_dir($abs_path);

			\Breakdance\ElementStudio\registerSaveLocation(
				$loc['path'],
				self::NS,
				$loc['type'],
				$loc['label'],
				false   // not built-in (user-editable)
			);
		}
	}

	// ── Static helpers ────────────────────────────────────────────────────────

	/**
	 * Creates all Breakdance storage directories.
	 * Called on plugin activation so directories exist before first use.
	 */
	public static function create_directories(): void {
		$base = rtrim(BASEMGMT_DIR, '/\\') . '/' . self::BASE_DIR;
		foreach ( ['elements', 'macros', 'presets'] as $sub ) {
			self::ensure_dir($base . '/' . $sub);
		}
	}

	private static function ensure_dir(string $path): void {
		if ( ! is_dir($path) ) {
			wp_mkdir_p($path);
		}
	}
}
