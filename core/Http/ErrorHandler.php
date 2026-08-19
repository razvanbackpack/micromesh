<?php

namespace Core\Http;

use Core\Helpers\Config;
use Core\Helpers\Log;
use Throwable;

/** Converts uncaught PHP failures into logged HTTP responses. */
class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler(static function (Throwable $exception): void {
            self::handle($exception);
        });

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            self::handle(new \ErrorException($message, 0, $severity, $file, $line));
            return true;
        });
    }

    public static function handle(Throwable $exception, int $status = 500): void
    {
        Log::error($exception->getMessage(), [
            'exception' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo self::page($status, 'Internal Server Error', $exception->getMessage());
        exit;
    }

    public static function response(int $status, string $message, string $details = ''): string
    {
        $debug = (bool) Config::get('app.debug');
        $visibleDetails = $debug ? $details : '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json')) {
            return (string) json_encode([
                'error' => [
                    'code' => $status,
                    'message' => $message,
                    'details' => $visibleDetails,
                ],
            ], JSON_THROW_ON_ERROR);
        }
        return self::page($status, $message, $visibleDetails);
    }

    private static function page(int $status, string $message, string $details): string
    {
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $safeDetails = htmlspecialchars($details, ENT_QUOTES, 'UTF-8');
        return '<!doctype html><html><head><meta charset="utf-8"><title>'.$status.' '.$safeMessage.
            '</title></head><body><h1>'.$status.' '.$safeMessage.'</h1>'.
            ($details === '' ? '' : '<pre>'.$safeDetails.'</pre>').'</body></html>';
    }
}