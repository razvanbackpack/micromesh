<?php
return [
	'allowed_methods' => [
		'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'
	],
	
	'allowed_headers' => [
		'Content-Type',
		'Authorization',
		'X-API-Key',
		'X-Request-ID'
	],

	'cors' => [
		'enabled' => true,
		'allowed_origins' => ['*'],
		'allow_credentials' => false,
	]
];
