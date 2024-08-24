<?php
namespace Core\Helpers;

class Request
{
    public static array $REQUEST_DATA;

    public static function CaptureRequest()
    {
        self::$REQUEST_DATA = [
            'link' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'post' => $_POST,
        ];

        // TODO: referrers and headers for ref & header check on route
    }
}