<?php
use App\Core\Routing\Route;
use App\Controllers\HomeController;

Route::GET("", HomeController::class, "Home");
