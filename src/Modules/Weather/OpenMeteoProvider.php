<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Weather;

defined('ABSPATH') || exit;

/**
 * Open-Meteo provider (open-meteo.com).
 * Free, no API key, GDPR-friendly, returns current + 3-day forecast.
 */
final class OpenMeteoProvider implements WeatherProviderInterface {

	private const API_URL = 'https://api.open-meteo.com/v1/forecast';

	/** WMO weather interpretation code → Polish label + icon. */
	private const WMO_CODES = [
		0  => ['label' => 'Bezchmurnie',              'icon' => '☀️'],
		1  => ['label' => 'Głównie bezchmurnie',       'icon' => '🌤️'],
		2  => ['label' => 'Częściowe zachmurzenie',    'icon' => '⛅'],
		3  => ['label' => 'Zachmurzenie całkowite',    'icon' => '☁️'],
		45 => ['label' => 'Mgła',                      'icon' => '🌫️'],
		48 => ['label' => 'Szron',                     'icon' => '🌫️'],
		51 => ['label' => 'Mżawka lekka',              'icon' => '🌦️'],
		53 => ['label' => 'Mżawka umiarkowana',        'icon' => '🌦️'],
		55 => ['label' => 'Mżawka intensywna',         'icon' => '🌧️'],
		61 => ['label' => 'Deszcz lekki',              'icon' => '🌧️'],
		63 => ['label' => 'Deszcz umiarkowany',        'icon' => '🌧️'],
		65 => ['label' => 'Deszcz intensywny',         'icon' => '🌧️'],
		71 => ['label' => 'Śnieg lekki',               'icon' => '❄️'],
		73 => ['label' => 'Śnieg umiarkowany',         'icon' => '❄️'],
		75 => ['label' => 'Śnieg intensywny',          'icon' => '❄️'],
		77 => ['label' => 'Ziarnisty śnieg',           'icon' => '🌨️'],
		80 => ['label' => 'Przelotne deszcze lekkie',  'icon' => '🌦️'],
		81 => ['label' => 'Przelotne deszcze',         'icon' => '🌧️'],
		82 => ['label' => 'Gwałtowne przelotne deszcze','icon' => '🌧️'],
		85 => ['label' => 'Opady śniegu lekkie',       'icon' => '🌨️'],
		86 => ['label' => 'Opady śniegu intensywne',   'icon' => '🌨️'],
		95 => ['label' => 'Burza',                     'icon' => '⛈️'],
		96 => ['label' => 'Burza z gradem',            'icon' => '⛈️'],
		99 => ['label' => 'Burza z silnym gradem',     'icon' => '⛈️'],
	];

	public function fetch(float $latitude, float $longitude, string $timezone = 'Europe/Warsaw'): ?array {
		$url = add_query_arg(
			[
				'latitude'          => $latitude,
				'longitude'         => $longitude,
				'timezone'          => $timezone,
				'current'           => 'temperature_2m,weathercode,windspeed_10m,precipitation,relativehumidity_2m',
				'daily'             => 'weathercode,temperature_2m_max,temperature_2m_min,precipitation_sum',
				'forecast_days'     => 3,
			],
			self::API_URL
		);

		$response = wp_remote_get($url, ['timeout' => 10, 'sslverify' => true]);

		if ( is_wp_error($response) ) {
			return null;
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		if ( ! is_array($data) || ! isset($data['current'], $data['daily']) ) {
			return null;
		}

		$current = $data['current'];
		$code    = (int) ($current['weathercode'] ?? 0);
		$wmo     = self::WMO_CODES[$code] ?? ['label' => 'Nieznane', 'icon' => '❓'];

		$forecast = [];
		$days     = $data['daily']['time'] ?? [];
		foreach ( $days as $i => $date ) {
			$dc       = (int) ($data['daily']['weathercode'][$i] ?? 0);
			$day_wmo  = self::WMO_CODES[$dc] ?? ['label' => 'Nieznane', 'icon' => '❓'];
			$forecast[] = [
				'date'          => $date,
				'weathercode'   => $dc,
				'label'         => $day_wmo['label'],
				'icon'          => $day_wmo['icon'],
				'temp_max'      => $data['daily']['temperature_2m_max'][$i] ?? null,
				'temp_min'      => $data['daily']['temperature_2m_min'][$i] ?? null,
				'precipitation' => $data['daily']['precipitation_sum'][$i] ?? null,
			];
		}

		return [
			'current' => [
				'temperature'  => $current['temperature_2m'] ?? null,
				'weathercode'  => $code,
				'label'        => $wmo['label'],
				'icon'         => $wmo['icon'],
				'windspeed'    => $current['windspeed_10m'] ?? null,
				'precipitation'=> $current['precipitation'] ?? null,
				'humidity'     => $current['relativehumidity_2m'] ?? null,
				'fetched_at'   => gmdate('Y-m-d H:i:s'),
			],
			'forecast' => $forecast,
		];
	}
}
