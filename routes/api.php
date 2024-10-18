<?php
use Core\Http\Route;
use App\Controllers\ApiHelloController;

Route::get('test', [ApiHelloController::class, 'ApiHello']);
