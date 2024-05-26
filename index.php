<?php
namespace App;
// error_reporting(0);
require __DIR__.'/vendor/autoload.php';

use Core\Application\Application;
use Symfony\Component\Dotenv\Dotenv;


// ===================== DOTENV SETUP START ======================

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// ====================== DOTENV SETUP END =======================



// ==================== CORE DEFINES START =======================

define('BASEDIR', __DIR__);

// ====================== CORE DEFINES END =======================



// ========================= APP START ===========================

$App = new Application();
$App->Run();
$App->Exit();

// ========================== APP END ============================
?>