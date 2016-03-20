--TEST--
Ensures that the AfterControllerEvent can decorate responses
--FILE--
<?php

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Cspray\Labrador\Http\Event\AfterControllerEvent;
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

$engine->onAfterController(function(AfterControllerEvent $event) {
    /** @var \Psr\Http\Message\ResponseInterface $response */
    $response = $event->getResponse();
    $contents = $response->getBody()->getContents();
    $contents .= ' and decorated';

    $response = new TextResponse($contents);

    $event->setResponse($response);
});

$req = (new Request())->withMethod('GET')->withUri(new Uri('http://test.example.com'));
$engine->run($req);
--EXPECT--
From the controller and decorated