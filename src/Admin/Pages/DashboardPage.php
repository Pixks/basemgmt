<?php

declare(strict_types=1);

namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\Announcements\AnnouncementRepository;
use BaseMgmt\Modules\Camps\CampRepository;

defined('ABSPATH') || exit;

/**
 * Dashboard summary page for the plugin.
 */
final class DashboardPage {

	public function render(): void {
		Capabilities::require_admin();

		$summary = CampRepository::active_summary();
		$pending = AnnouncementRepository::count_pending();

		include BASEMGMT_DIR . 'templates/admin/dashboard.php';
	}
}
