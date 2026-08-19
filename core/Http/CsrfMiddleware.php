<?php

namespace Core\Http;

use Core\Helpers\Request;
use Core\Helpers\Csrf;

/** Verify the session CSRF token for state-changing requests. */
class CsrfMiddleware extends Middleware
{
    public function handle(...$params): string|bool|null
    {
        $request = Request::current();
        if ($request === null || in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }

        $submitted = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
        if (!is_string($submitted) || !hash_equals(Csrf::token(), $submitted)) {
            return Route::error(419, 'CSRF token mismatch');
        }

        Csrf::token(true);
        return null;
    }
}