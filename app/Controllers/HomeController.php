<?php
namespace App\Controllers;
use App\Services\Welcome\Welcome;
use Core\Helpers\Log;
use Core\Helpers\Config;
use Core\Http\Response;

class HomeController extends Controller
{
    public function __construct()
    {

    }

    public function Home()
    {
        $Welcome = new Welcome();
        $welcomeMessage = $Welcome->GetWelcomeMessage();

        Log::debug('this work???');

        Response::make()->json(Config::get('app'))->send();
        return $this->view('welcome',
            ['message' => $welcomeMessage
        ]);
    }
}