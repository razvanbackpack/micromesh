<?php
namespace App\Controllers;
use App\Services\Welcome\Welcome;
use Core\Helpers\Log;
use Core\Helpers\Config;
use Core\Http\Response;

class ApiHelloController extends Controller
{
    public function __construct()
    {
    }

    public function ApiHello()
    {
        Response::make()->json(Config::get('app'))->send();
    }
}
