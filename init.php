<?php

require_once __DIR__ . '/vendor/autoload.php';

$injector = (new Cspray\Labrador\Http\Services())->createInjector();

/** @var Cspray\Labrador\Http\Engine $engine */
$engine = $injector->make(Cspray\Labrador\Http\Engine::class);

$engine->get('/', Cspray\Labrador\Http\Controller\WelcomeController::class . '#index');

$engine->run();



