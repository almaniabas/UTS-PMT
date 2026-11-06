<?php

return [
	'name' => env('APP_NAME', 'SIM-DTY Maintenance'),
	'env' => env('APP_ENV', 'local'),
	'debug' => (bool) env('APP_DEBUG', true),
	'url' => env('APP_URL', 'http://localhost'),
	'timezone' => 'Asia/Jakarta',
	'locale' => 'id',
	'fallback_locale' => 'en',
	'faker_locale' => 'id_ID',
	'cipher' => 'AES-256-CBC',
	'key' => env('APP_KEY'),
	'previous_keys' => [],
	'maintenance' => [
		'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
		'store' => env('APP_MAINTENANCE_STORE', 'database'),
	],
];
