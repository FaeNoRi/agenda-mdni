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

use Illuminate\Contracts\Console\Kernel;

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$status = $kernel->call('notifications:rappels');
echo $kernel->output();

exit($status);
