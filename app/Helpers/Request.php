<?php
namespace App\Helpers;

class Request
{
    public static array $REQUEST_DATA;

    public static function CaptureRequest()
    {
        self::$REQUEST_DATA = [
            'link' => ltrim($_SERVER['REQUEST_URI'], '/'),
            'type' => $_SERVER['REQUEST_METHOD'],
            'post' => $_POST,
        ];
    }
}