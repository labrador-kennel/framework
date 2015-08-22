<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cspray\Labrador\Http\Controller\WelcomeController;
use Cspray\Labrador\Http\ControllerServicePlugin;
use Cspray\Labrador\Http\Engine;
use function Cspray\Labrador\Http\bootstrap;

$injector = bootstrap();

/** @var Cspray\Labrador\Http\Engine $engine */
$engine = $injector->make(Engine::class);

$engine->get('/', WelcomeController::class . '#index');

$controllerPlugin = new ControllerServicePlugin([WelcomeController::class]);
$engine->registerPlugin($controllerPlugin);

$engine->run();



