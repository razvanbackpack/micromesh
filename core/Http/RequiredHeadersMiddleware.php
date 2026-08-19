<?php

namespace Core\Http;

use Core\Helpers\Request;

/**
 * Middleware to validate required headers in requests
 * 
 * Example:
 * ```php
 * Route::addGlobalMiddleware(
 *     new RequiredHeadersMiddleware([
 *         'X-Request-ID' => null,  // Required, no validation
 *         'Authorization' => function($value) {
 *             return str_starts_with($value, 'Bearer ');
 *         }
 *     ])
 * );
 * ```
 */
class RequiredHeadersMiddleware extends Middleware
{
    public function __construct(
        private array $rules,
        private int $missingStatus = 400,
        private int $invalidStatus = 403
    ) {
    }

    public function handle(...$params): string|bool|null
    {
        $headers = array_change_key_case(
            Request::$REQUEST_DATA['headers'] ?? [],
            CASE_LOWER
        );

        foreach ($this->rules as $name => $validator) {
            $value = $headers[strtolower($name)] ?? null;

            if ($value === null || $value === '') {
                return Route::error($this->missingStatus, "Missing header: {$name}");
            }

            if ($validator !== null && !$validator($value)) {
                return Route::error($this->invalidStatus, "Invalid header: {$name}");
            }
        }

        return null;
    }
}

