<?php
return [
    [
        'file' => 'web.php',
        'prefix' => '',
    ],
    [
        'file' => 'api.php',
        'prefix' => '/api',
        'check_referrer' => true, // allow requests only from APP_URL
        'allowed_headers' => [ // custom headers / values to be checked for
			'HTTP_MICROMESH_APIKEY' => $_SERVER['API_KEY'] ?? ''
		],
    ],
];
