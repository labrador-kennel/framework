# Labrador HTTP

[![Travis](https://img.shields.io/travis/labrador-kennel/http.svg?style=flat-square)](https://travis-ci.org/labrador-kennel/http)
[![GitHub license](https://img.shields.io/github/license/labrador-kennel/http.svg?style=flat-square)](http://opensource.org/licenses/MIT)
[![GitHub release](https://img.shields.io/github/release/labrador-kennel/http.svg?style=flat-square)](https://github.com/labrador-kennel/http/releases/latest)

A microframework for HTTP application built on top of [Labrador](https://github.com/cspray/labrador). We wire 
together the awesome [FastRoute](https://github.com/nikic/FastRoute) lib and [Symfony's HTTP Foundation](https://github.com/symfony/HttpFoundation) 
to allow you to easily respond to a given request.

This library requires PHP7+.

## Install

We recommend you use [Composer](https://getcomposer.org) to install the library.

```
composer require cspray/labrador-http
```

## Quick Start

The quickest way to get started is to use the `Cspray\Labrador\Http\bootstrap()` function. This registers error 
and exception handlers, creates an `Auryn\Injector` and wires the container for the library's required services.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cspray\Labrador\Http\Engine;
use Symfony\Component\HttpFoundation\Response;
use function Cspray\Labrador\Http\bootstrap;

$injector = bootstrap();

$engine = $injector->make(Engine::class);

$engine->get('/', new Response('hello world'));
$engine->get('/closure', function() {
    return new Response('Because of course you can do this');
});

// You'd really want to put this in its own file
class MyController {
    
    public function someMethodThatYouName() {
        return new Response('And, yea, from the controller object too');
    }
    
}

$engine->get('/controller-object', MyController::class . '#someMethodThatYouName');

$engine->run();
```

If you're looking for more information check out [http://labrador.cspray.net/libs/labrador-http](http://labrador.cspray.net/libs/labrador-http).