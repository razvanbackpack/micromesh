<?php
namespace Core\Helpers;

class Request
{
    public static array $REQUEST_DATA;

    public static function CaptureRequest()
    {
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        $headers = getallheaders();
        $headers_arr = [];
        $request_method = $_SERVER['REQUEST_METHOD'];
        $request_uri = $_SERVER['REQUEST_URI'];

        foreach ($headers as $name => $value) {
            $headers_arr[$name] = $value;
        }

        self::$REQUEST_DATA = [
            'link' => $request_uri,
            'method' => $request_method,
            'post' => $_POST,
            'headers' => $headers_arr,
            'referrer' => $referrer
        ];
    }
}