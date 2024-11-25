<?php

declare(strict_types=1);

use Nette\Bootstrap\Configurator;

ini_set('display_errors', '1');
error_reporting(E_ALL);

// ignore deprecated errors in third-party packages:
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if ($errno === E_DEPRECATED) {
        if (strpos($errfile, '/vendor/') !== false) {
            return true;
        }
    }
    return false;
});

include_once('../vendor/autoload.php');

$configurator = new Configurator();
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->setDebugMode(true);
$configurator->addConfig(__DIR__ . '/../config/config.neon');

$container = $configurator->createContainer();
$app = $container->getByType(Nette\Application\Application::class);
$app->run();
