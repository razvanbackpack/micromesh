<?php

namespace Core\Helpers {
    class Csrf
    {
        /** Return the current session CSRF token, optionally rotating it. */
        public static function token(bool $refresh = false): string
        {
            self::startSession();

            if ($refresh || !self::hasToken()) {
                self::storeToken(self::generateToken());
            }

            return self::storedToken();
        }

        public static function field(): string
        {
            return self::renderField(self::token());
        }

        private static function startSession(): void
        {
            if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
                session_start();
            }
        }

        private static function hasToken(): bool
        {
            return isset($_SESSION['_csrf_token']) && is_string($_SESSION['_csrf_token']);
        }

        private static function generateToken(): string
        {
            return bin2hex(random_bytes(32));
        }

        private static function storeToken(string $token): void
        {
            $_SESSION['_csrf_token'] = $token;
        }

        private static function storedToken(): string
        {
            return $_SESSION['_csrf_token'];
        }

        private static function renderField(string $token): string
        {
            $escapedToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
            return '<input type="hidden" name="_token" value="'.$escapedToken.'">';
        }
    }
}
