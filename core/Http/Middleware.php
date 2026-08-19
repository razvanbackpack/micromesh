<?php
namespace Core\Http;

/**
 * Abstract base class for creating middleware
 * 
 * Middleware is executed before route handlers and can:
 * - Pass requests to next middleware (return null)
 * - Reject requests with 403 Forbidden (return false)
 * - Reject requests with custom responses (return string/array)
 * - Reject requests by throwing exceptions (converted to 500 error)
 * 
 * Example:
 * ```php
 * class AuthMiddleware extends Middleware {
 *     public function handle($userId): string|bool|null {
 *         if (!$this->isAuthenticated($userId)) {
 *             return false; // Returns 403 Forbidden
 *         }
 *         return null; // Continue to next middleware
 *     }
 * }
 * ```
 */
abstract class Middleware
{
    /**
     * Handle the middleware logic
     * 
     * @param mixed ...$params Route parameters extracted from URL
     * @return string|bool|null
     *   - null: Pass to next middleware
     *   - false: Reject with 403 Forbidden
     *   - string/array: Reject with custom response (JSON encoded if array)
     */
    abstract public function handle(...$params): string|bool|null;

    /**
     * Magic method to allow middleware to be called as a function
     * This enables middleware to be passed to routes without wrapping
     */
    public function __invoke(...$params): string|bool|null
    {
        return $this->handle(...$params);
    }
}
