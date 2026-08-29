<?php

declare(strict_types=1);

namespace BaseMgmt\License;

defined('ABSPATH') || exit;

final class DeveloperOverride {

	public const CONFIG_CONSTANT = 'BASEMGMT_DEV_LICENSE_OVERRIDE';
	private const ACCESS_KEY     = 'CAMPLINK-DEVELOPER-OVERRIDE-2026';

	public static function is_active(): bool {
		if ( ! defined(self::CONFIG_CONSTANT) ) {
			return false;
		}

		$configured_key = trim((string) constant(self::CONFIG_CONSTANT));
		if ( '' === $configured_key ) {
			return false;
		}

		return hash_equals(self::ACCESS_KEY, $configured_key);
	}

	public static function build_status(): array {
		return [
			'success' => true,
			'data'    => [
				'plan_name'           => __('developer', 'basemgmt'),
				'channel'             => 'stable',
				'allowed_channels'    => 'stable,beta',
				'updates_allowed'     => true,
				'support_active'      => true,
				'grace_period_days'   => 0,
				'developer_override'  => true,
				'developer_message'   => __('Aktywowano na potrzeby rozwoju wtyczki poprzez kod deweloperski.', 'basemgmt'),
			],
		];
	}
}
