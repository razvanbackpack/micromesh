<?php
use Core\Http\Route;
use Core\Http\RequiredHeadersMiddleware;
use App\Controllers\HomeController;

Route::addGlobalMiddleware(
	new RequiredHeadersMiddleware([
        'X-Request-ID' => null,
    ])
);

Route::get("", [HomeController::class, "Home"]);
Route::get("/test/{id}/comments/{commentId?}", 
	function($id, $commentId = 0) {
		if($commentId) {
			dd('$ID: ' . $id, '$commentId: '.$commentId);
		} else {
			dd('$ID: ' . $id);
		}
	}, 
	[
		// middleware
		function($id) {
			if($id > 1) {
				dd('sorry not allowed on route. $id must be <1');
			}
		}
	]
);