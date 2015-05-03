<?php

require_once __DIR__ . '/vendor/autoload.php';

$injector = new \Auryn\Provider();

(new \Labrador\Http\Services())->register($injector);

/** @var \Labrador\Http\Engine $engine */
$engine = $injector->make(\Labrador\Http\Engine::class);

$engine->onExceptionThrown(function(\Labrador\Event\ExceptionThrownEvent $event) {
    $exc = $event->getException();
    $excType = get_class($exc);
    $msg = $exc->getMessage();
    $file = $exc->getFile();
    $line = $exc->getLine();
    $trace = $exc->getTraceAsString();
    echo <<<TEXT
<pre>
{$excType} was thrown!

{$msg}

In {$file} on line #{$line}.

Stack Trace:

{$trace}
</pre>
TEXT;
    exit;
});

$engine->get('/', \Labrador\Http\Controller\WelcomeController::class . '#index');

$engine->run();



