<?php
namespace Core\Http;

class ResponseObject
{
    protected $content;
    protected $status;
    protected $headers;

    public function __construct($content = '', $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function header($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function json($data)
    {
        $this->header('Content-Type', 'application/json');
        $this->content = json_encode($data);
        return $this;
    }

    public function send()
    {
        http_response_code($this->status);
        
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $this->content;
        exit;
    }
}

class Response {
    public static function make($content = '', $status = 200, array $headers = [])
    {
        return new ResponseObject($content, $status, $headers);
    }
}
