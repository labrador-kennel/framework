<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cspray\Labrador\Http\Controller\WelcomeController;
use Cspray\Labrador\Http\ControllerServicePlugin;
use Cspray\Labrador\Http\Engine;
use function Cspray\Labrador\Http\bootstrap;
use Whoops\Run;

(new Run())->register();

$injector = bootstrap();

/** @var Cspray\Labrador\Http\Engine $engine */
$engine = $injector->make(Engine::class);

$engine->get('/', WelcomeController::class . '#index');
$engine->get('/echo/{param}', WelcomeController::class . '#echo');
$engine->get('/info', function() {
    ob_start();
    phpinfo();
    $response = ob_get_clean();

    return new \Symfony\Component\HttpFoundation\Response($response);
});

$engine->run();
