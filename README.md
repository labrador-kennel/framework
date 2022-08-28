# Labrador HTTP

[![Travis](https://img.shields.io/travis/labrador-kennel/http.svg?style=flat-square)](https://travis-ci.org/labrador-kennel/http)
[![GitHub license](https://img.shields.io/github/license/labrador-kennel/http.svg?style=flat-square)](http://opensource.org/licenses/MIT)
[![GitHub release](https://img.shields.io/github/release/labrador-kennel/http.svg?style=flat-square)](https://github.com/labrador-kennel/http/releases/latest)

Labrador HTTP is a framework for creating feature-rich, SOLID web applications. It is primarily a set of opinions on integrating the following libraries:

- [amphp/http-server](https://github.com/amphp/http-server) Provides the underlying server and HTTP-stack.
- [cspray/annotated-container](https://github.com/cspray/annotated-container) Provides a powerful dependency injection framework using PHP 8 Attributes.
- [nikic/fast-route](https://github.com/nikic/fast-route) Provides the actual routing functionality that maps a Request to a Controller.
- [cuyz/valinor](https://github.com/cuzy/valinor) Provides the ability to create DTO objects from a Request body.

> This is not meant to be an exhaustive list of all dependencies, simply those involving major integration points.

Please check out the Quick Start below for more details on Labrador HTTP's novel features.

## Install

Use [Composer](https://getcomposer.org) to install the library.

```
composer require cspray/labrador-http:dev-main
```

## Quick Start

Labrador's most novel feature is its integration with Annotated Container to allow the following features:

- Inject specific parts of a Request into a controller method
- Inject custom DTO objects based on a JSON encoded body
- Specify route mapping using FastRoute and Attributes.

It's best to show a Controller implementing this functionality.

```php
<?php declare(strict_types=1);

namespace Cspray\Labrador\HttpDemo;

use Amp\Http\Server\Response;
use Cspray\Labrador\Http\Controller\Dto\Dto;
use Cspray\Labrador\Http\Controller\Dto\DtoController;
use Cspray\Labrador\Http\Controller\Dto\Get;
use Cspray\Labrador\Http\Controller\Dto\Post;
use Cspray\Labrador\Http\Controller\Dto\RouteParam;
use League\Uri\Components\Query;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

#[DtoController]
final class WidgetController {

    // The $logger will be a Monolog logger that sends output to stdout using amphp/log
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    // The $filter will be injected from the query parameters sent in the request
    // The $widgetGatherer is an injected service and will be the same instance, unlike $filter
    #[Get('/widgets')]
    public function list(Query $filter, WidgetGatherer $widgetGatherer) : Response {
        // do some stuff to generate a response 
    }
    
    // The $widgetId will be injected from the value sent in the route
    // The $widgetGatherer is an injected service and will be the same instance, unlike $widgetId
    #[Get('/widgets/{id}')]
    public function fetch(#[RouteParam('id')] UuidInterface $widgetId, WidgetGatherer $widgetGatherer) : Response {
        // do some stuff to generate a response 
    }
    
    // The $widget will be created using cuyz/valinor from the JSON decoded Request body
    // The $creator is an injected service and will be the same instance, unlike $widget
    #[Post('/widgets')]
    public function create(#[Dto] Widget $widget, WidgetCreator $creator) : Response {
        // do some stuff to generate a response 
    }

}
```

## Request Injections

Labrador HTTP also provides the ability to get specific parts of the Request using a set of Attributes, or specific type mappings.

```php
<?php declare(strict_types=1);

namespace Cspray\Labrador\HttpDemo;

use Amp\Http\Server\RequestBody;use Amp\Http\Server\Response;
use Cspray\Labrador\Http\Controller\Dto\Body;use Cspray\Labrador\Http\Controller\Dto\DtoController;
use Cspray\Labrador\Http\Controller\Dto\Get;
use Cspray\Labrador\Http\Controller\Dto\Header;
use Cspray\Labrador\Http\Controller\Dto\Headers;
use Cspray\Labrador\Http\Controller\Dto\Method;
use Cspray\Labrador\Http\Controller\Dto\Post;
use Cspray\Labrador\Http\Controller\Dto\QueryParams;
use Cspray\Labrador\Http\Controller\Dto\RouteParam;
use Cspray\Labrador\Http\Controller\Dto\Url;
use League\Uri\Components\Query;
use League\Uri\Contracts\QueryInterface;
use Psr\Http\Message\UriInterface;

#[DtoController]
class RequestInjectionController {

    #[Get('/headers')]
    public function headers(#[Headers] array $headers) : Response {
        // ...  
    }

    #[Get('/header-array')]
    public function headerAsArray(#[Header('Accept')] array $accept) : Response {
        // ... 
    }
    
    #[Get('/header-string')]
    public function headerAsString(#[Header('Authorization')] string $token) : Response {
        // ... 
    }
    
    #[Get('/url/attr')]
    public function url(#[Url] UriInterface $uri) : Response {
        // ...
    }
    
    #[Get('/method')]
    public function method(#[Method] string $method) : Response {
        // ...
    }
    
    #[Get('/body/buffered')]
    public function body(#[Body] string $body) : Response {
        // ... 
    }
    
    #[Post(('/body/stream'))]
    public function bodyStream(#[Body] RequestBody $body) : Response {
        // ...
    }
    
    #[Get('/query/string')]
    public function queryAsString(#[QueryParams] string $query) : Response {
        // ...
    }
    
    #[Get('/query/interface')]
    public function queryAsInterface(#[QueryParams] QueryInterface $query) : Response {
        // ...
    }
    
    #[Get('/query/object')]
    public function queryAsObject(#[QueryParams] Query $query) : Response {
        // ...
    }
    
    #[Get('/route/{foo}/param/{bar}')]
    public function routeParam(
        #[RouteParam('foo')] string $fooParam,
        #[RouteParam('bar')] string $barParam
    ) : Response {
        // ... 
    }
    
    // Some parameters you can simply provide a type and not have to attribute it
    
    #[Get('/uri/implied')]
    public function uriImplied(UriInterface $uri) : Response {
        // ...
    }
    
    #[Get('/query/implied')]
    public function queryImplied(Query $query) : Response {
        // ...
    }
    
    #[Get('/body/implied')]
    public function bodyImplied(RequestBody $body) : Response {
        // ...
    }
    
}
```
