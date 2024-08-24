<?php
namespace App\Controllers;
use App\Services\Welcome\Welcome;
use Core\Helpers\Log;

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
        dd('hey home');
        return $this->view('welcome',
            ['message' => $welcomeMessage
        ]);
    }
}