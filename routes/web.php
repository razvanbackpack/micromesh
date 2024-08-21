<?php
use Core\Routing\Route;
use App\Controllers\HomeController;
use Core\Application\DB;

Route::get("", HomeController::class, "Home");
Route::get("test", function () {
	dd(

		DB::query(
			"SELECT * FROM contact_log"
		)
	);
});