--TEST--
Ensures that the BeforeControllerEvent can short circuit controller execution
--FILE--
<?php

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Cspray\Labrador\Http\Event\BeforeControllerEvent;
use Cspray\Labrador\Http\Engine as HttpEngine;
use Zend\Diactoros\{
    ServerRequest as Request,
    Uri,
    Response\TextResponse
};
use function Cspray\Labrador\Http\bootstrap;

$injector = bootstrap();
/** @var HttpEngine $engine */
$engine = $injector->make(HttpEngine::class);

$engine->get('/', function() {
    return new TextResponse('From the controller');
});

$engine->onBeforeController(function(BeforeControllerEvent $event) {
    $event->setResponse(new TextResponse('From the event'));
});

$req = (new Request())->withMethod('GET')->withUri(new Uri('http://test.example.com'));
$engine->run($req);
--EXPECTF--
From the event