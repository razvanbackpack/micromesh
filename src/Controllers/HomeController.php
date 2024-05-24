<?php
namespace App\Controllers;
use App\Services\Welcome\Welcome;

class HomeController extends Controller
{
    public function __construct()
    {

    }

    public function Home()
    {
        $Welcome = new Welcome();
        $welcomeMessage = $Welcome->GetWelcomeMessage();
        
        return $this->view('welcome',
            ['message' => $welcomeMessage
        ]);
    }
}