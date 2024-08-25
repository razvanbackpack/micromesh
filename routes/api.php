<?php
use Core\Routing\Route;
use App\Controllers\ApiHelloController;

Route::get('test', [ApiHelloController::class, 'ApiHello']);
