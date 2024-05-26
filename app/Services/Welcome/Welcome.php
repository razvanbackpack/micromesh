<?php

namespace App\Services\Welcome;
use App\Core\Config\Config;

class Welcome
{
    function __construct() { }

    public function GetWelcomeMessage(): string
    {
        return "Hello User! Welcome to " . $_ENV['APP_NAME'];
    }
}