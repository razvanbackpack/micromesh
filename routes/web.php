<?php
use Core\Routing\Route;
use App\Controllers\HomeController;

Route::get("", HomeController::class, "Home");
Route::get("test", function() {
  dd('hey');
});