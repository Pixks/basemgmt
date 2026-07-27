<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Weather;

defined('ABSPATH') || exit;

/**
 * Contract for weather data providers.
 * Implement this interface to swap the weather API source without changing the rest of the system.
 */
interface WeatherProviderInterface {

	/**
	 * Fetch current weather + short forecast.
	 *
	 * @param float  $latitude
	 * @param float  $longitude
	 * @param string $timezone  IANA timezone, e.g. 'Europe/Warsaw'
	 * @return array{current: array<string,mixed>, forecast: list<array<string,mixed>>}|null
	 */
	public function fetch(float $latitude, float $longitude, string $timezone = 'Europe/Warsaw'): ?array;
}
