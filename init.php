<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cspray\Labrador\Http\{
    Engine as HttpEngine,
    Controller\WelcomeController
};
use function Cspray\Labrador\Http\bootstrap;

$injector = bootstrap();

$engine = $injector->make(HttpEngine::class);

$engine->get('/', WelcomeController::class . '#index');

$engine->run();