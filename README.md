# Labrador HTTP

[![Travis](https://img.shields.io/travis/labrador-kennel/http.svg?style=flat-square)](https://travis-ci.org/labrador-kennel/http)
[![GitHub license](https://img.shields.io/github/license/labrador-kennel/http.svg?style=flat-square)](http://opensource.org/licenses/MIT)
[![GitHub release](https://img.shields.io/github/release/labrador-kennel/http.svg?style=flat-square)](https://github.com/labrador-kennel/http/releases/latest)

A PSR-7 compliant microframework built on top of [Labrador](//github.com/labrador-kennel/core) and [FastRoute](//github.com/nikic/fast-route).
Take advantage of a highly extensible, spec-compliant library and easily integrate with existing [PSR-7 Middleware](https://github.com/oscarotero/psr7-middlewares) 
and a multitude of open source libraries.

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
```