<?php
namespace App;
require __DIR__.'/vendor/autoload.php';
use App\Core\Application\Application;

// ===================== DOTENV SETUP START ======================
use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');
// ====================== DOTENV SETUP END =======================

define('BASEDIR', __DIR__);

$App = new Application();
$App->Run();
$App->Terminate();
?>