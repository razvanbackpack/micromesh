<?php
return [
	'allowed_methods' => [
		'GET', 'POST', 'OPTIONS'
	],
	
	'allowed_headers' => [
		'Content-Type',
		'Authorization'
	],

	'cors' => [
		'enabled' => true,
		'allowed_origins' => ['*'],
	]
];
