<?php
namespace Core\Helpers;

use JsonException;

/**
 * Immutable snapshot of the current HTTP request.
 */
class Request
{
    public static array $REQUEST_DATA = [];
    private static ?self $current = null;

    public function __construct(
        private readonly array $inputData = [],
        private readonly array $files = [],
        private readonly array $server = [],
        private readonly array $headers = [],
    ) {
    }

    public static function capture(): self
    {
        $server = $_SERVER;
        $headers = function_exists('getallheaders') ? getallheaders() : self::headersFromServer($server);
        $request = new self($_POST, $_FILES, $server, $headers);
        self::$current = $request;

        self::$REQUEST_DATA = [
            'link' => $request->uri(),
            'method' => $request->method(),
            'post' => $request->all(),
            'files' => $request->files,
            'headers' => $request->headers,
            'referrer' => $request->header('Referer'),
        ];

        return $request;
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    /** @deprecated Use capture(). */
    public static function CaptureRequest(): self
    {
        return self::capture();
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->inputData) ? $this->inputData[$key] : $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) ? $file : null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->inputData);
    }

    public function all(): array
    {
        return $this->inputData;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function json(): array
    {
        $body = file_get_contents('php://input');
        if ($body === false || trim($body) === '') {
            return [];
        }

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function uri(): string
    {
        return (string) ($this->server['REQUEST_URI'] ?? '/');
    }

    public function header(string $name, mixed $default = null): mixed
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }
        return $default;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    private static function headersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', ucwords(strtolower(substr($key, 5)), '_'));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}