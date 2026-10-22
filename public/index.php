<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// PHP 8.5 : le fichier de configuration de base du framework (vendor/laravel/framework/config/database.php, v12.21)
// utilise encore une constante PDO dépréciée. L'avis de dépréciation ne doit jamais s'afficher dans la réponse
// (il casserait les en-têtes HTTP) : on l'écarte jusqu'à la mise à jour de Laravel. Les autres erreurs restent signalées.
if (PHP_VERSION_ID >= 80500) {
    error_reporting(E_ALL & ~E_DEPRECATED);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
