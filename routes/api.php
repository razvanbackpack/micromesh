<?php
use Core\Http\Route;
use App\Controllers\ApiHelloController;
use Core\Http\RequiredHeadersMiddleware;

Route::get(
    'test',
    [ApiHelloController::class, 'ApiHello'],
    [
        new RequiredHeadersMiddleware([
            'X-API-Key' => function ($value) {
                $expectedKey = $_ENV['API_KEY'] ?? '';

                return $expectedKey !== '' && hash_equals($expectedKey, $value);
            },
        ], 401, 401),
    ]
);
