<?php

require_once __DIR__ . '/vendor/autoload.php';

$injector = (new \Labrador\Http\Services())->createInjector();

/** @var \Labrador\Http\Engine $engine */
$engine = $injector->make(\Labrador\Http\Engine::class);

$engine->get('/', \Labrador\Http\Controller\WelcomeController::class . '#index');

$engine->run();



