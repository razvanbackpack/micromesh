<?php
use Core\Routing\Route;
use App\Controllers\HomeController;

Route::get("", HomeController::class, "Home");
