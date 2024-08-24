<?php
return [
    [
        'file' => 'web.php',
        'prefix' => '',
    ],
    [
        'file' => 'api.php',
        'prefix' => '/api',
        'check_referrer' => true,
        'allowed_headers' => [
			'HTTP_MICROMESH_APIKEY' => $_SERVER['API_KEY'] ?? ''
		],
    ],
];
