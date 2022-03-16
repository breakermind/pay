<?php

return [
	'version' => 1.0,

	// Load routes
	'routes' => true,

	// Load db migrations
	'migrations' => true,

	// Enable payments
	'enable' => [
		'payu' => true,
	],

	// Payu api credentials
	'payu' => [
		// Environment
		'env' => 'sandbox',

		// Keys
		'pos_id' => '',
		'pos_md5' => '',

		// Oauth
		'client_id' => '',
		'client_secret' => '',

		// Currency
		'currency' => 'PLN',
	],
];
