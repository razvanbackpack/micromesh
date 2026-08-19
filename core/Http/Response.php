<?php
namespace Core\Http;

class ResponseObject
{
    protected mixed $content;
    protected int $status;
    protected array $headers;

    public function __construct(mixed $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    public function setContent(mixed $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function status(int $status): static
    {
        return $this->setStatus($status);
    }

    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function json(mixed $data): static
    {
        $this->header('Content-Type', 'application/json');
        $this->content = json_encode($data, JSON_THROW_ON_ERROR);
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);
        
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $this->content;
        exit;
    }

    public function __toString(): string
    {
        return is_string($this->content) ? $this->content : (string) json_encode($this->content);
    }
}

class Response {
    public static function make(mixed $content = '', int $status = 200, array $headers = []): ResponseObject
    {
        return new ResponseObject($content, $status, $headers);
    }
}
