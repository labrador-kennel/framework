<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cspray\Labrador\Http\Engine;
use Zend\Diactoros\Response\TextResponse;
use function Cspray\Labrador\Http\bootstrap;

$injector = bootstrap();

$engine = $injector->make(Engine::class);

$engine->get('/', new TextResponse('hello world'));
$engine->get('/closure', function() {
    return new TextResponse('Because of course you can do this');
});

// You'd really want to put this in its own file
class MyController {

    public function someMethodThatYouName() {
        return new TextResponse('And, yea, from the controller object too');
    }

}

$engine->get('/controller-object', MyController::class . '#someMethodThatYouName');

$engine->run();