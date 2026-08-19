<?php

namespace Core\Helpers;

use RuntimeException;

/** Lightweight structured file logger. */
class Log
{
    public const DEBUG = 'debug';
    public const INFO = 'info';
    public const NOTICE = 'notice';
    public const WARNING = 'warning';
    public const ERROR = 'error';
    public const CRITICAL = 'critical';

    /** @param array<string, mixed> $context */
    public static function write(string $message, string $level = self::INFO, array $context = [], string $channel = 'app'): void
    {
        $config = Config::get('app.log');
        if (!is_array($config) || !($config['enable'] ?? true)) {
            return;
        }
        $levels = [self::DEBUG, self::INFO, self::NOTICE, self::WARNING, self::ERROR, self::CRITICAL];
        if (!in_array($level, $levels, true)) {
            throw new \InvalidArgumentException("Unsupported log level: {$level}");
        }

        $path = BASEDIR . DIRECTORY_SEPARATOR . trim((string) ($config['path'] ?? 'app/Logs'), '/\\');
        $file = $path . DIRECTORY_SEPARATOR . $channel . '.log';
        self::ensureDirectory($path);
        self::rotate($file, (int) ($config['max_size'] ?? 5242880), (int) ($config['max_files'] ?? 5));
        $entry = json_encode([
            'timestamp' => date(DATE_ATOM),
            'level' => $level,
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
        ], JSON_THROW_ON_ERROR) . PHP_EOL;

        if (file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException("Failed to write to log file: {$file}");
        }
    }

    /** @param array<string, mixed> $context */
    public static function debug(string $message, array $context = [], string $channel = 'app'): void { self::write($message, self::DEBUG, $context, $channel); }
    /** @param array<string, mixed> $context */
    public static function info(string $message, array $context = [], string $channel = 'app'): void { self::write($message, self::INFO, $context, $channel); }
    /** @param array<string, mixed> $context */
    public static function notice(string $message, array $context = [], string $channel = 'app'): void { self::write($message, self::NOTICE, $context, $channel); }
    /** @param array<string, mixed> $context */
    public static function warning(string $message, array $context = [], string $channel = 'app'): void { self::write($message, self::WARNING, $context, $channel); }
    /** @param array<string, mixed> $context */
    public static function error(string $message, array $context = [], string $channel = 'errors'): void { self::write($message, self::ERROR, $context, $channel); }
    /** @param array<string, mixed> $context */
    public static function critical(string $message, array $context = [], string $channel = 'errors'): void { self::write($message, self::CRITICAL, $context, $channel); }

    /** Backward-compatible alias for the previous numeric logger API. */
    public static function Log(string $message = '', int $level = 0, string $date = ''): void
    {
        $names = [self::DEBUG, self::INFO, self::WARNING, self::ERROR, self::CRITICAL];
        self::write($message, $names[$level] ?? self::DEBUG, $date === '' ? [] : ['timestamp' => $date]);
    }

    private static function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException("Failed to create log directory: {$path}");
        }
    }

    private static function rotate(string $file, int $maxSize, int $maxFiles): void
    {
        if (!is_file($file) || $maxSize <= 0 || filesize($file) < $maxSize) {
            return;
        }
        for ($index = $maxFiles - 1; $index >= 1; $index--) {
            $source = "{$file}.{$index}";
            $target = "{$file}." . ($index + 1);
            if (is_file($source)) {
                rename($source, $target);
            }
        }
        rename($file, $file . '.1');
    }
}
