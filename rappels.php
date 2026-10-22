<?php

/**
 * Point d'entrée pour la tâche planifiée (cron) de l'hébergeur : crée les rappels du jour.
 * Équivaut à « php artisan notifications:rappels », sans avoir à passer d'argument au cron.
 * Refuse de s'exécuter depuis le web.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// PHP 8.5 : le fichier de configuration de base du framework (vendor/laravel/framework/config/database.php, v12.21)
// utilise encore une constante PDO dépréciée. L'avis de dépréciation ne doit jamais s'afficher dans la réponse
// (il casserait les en-têtes HTTP) : on l'écarte jusqu'à la mise à jour de Laravel. Les autres erreurs restent signalées.
if (PHP_VERSION_ID >= 80500) {
    error_reporting(E_ALL & ~E_DEPRECATED);
}

use Illuminate\Contracts\Console\Kernel;

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$status = $kernel->call('notifications:rappels');
echo $kernel->output();

exit($status);
