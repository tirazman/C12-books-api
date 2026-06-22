<?php 
use Dotenv\Dotenv; 
use Slim\Factory\AppFactory; 
  
require __DIR__ . '/../vendor/autoload.php'; 
  
Dotenv::createImmutable(__DIR__ . '/..')->safeLoad(); 
  
$app = AppFactory::create(); 

$app->addRoutingMiddleware(); 

// --- UPDATED MIDDLEWARE STACK ORDER FOR STEP 6 ---
$app->add(new App\Middleware\SecurityHeaders()); // Added FIRST so it runs LAST (wraps everything)
$app->add(new App\Middleware\JsonBodyParser()); 
$app->add(new App\Middleware\Cors()); 
// -------------------------------------------------

$app->addErrorMiddleware(true, true, true); 

(require __DIR__ . '/../src/routes.php')($app); 

$app->run();