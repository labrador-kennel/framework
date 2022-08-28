<?php

namespace Cspray\Labrador\HttpDummyApp\Controller;

use Amp\Http\Server\RequestBody;
use Amp\Http\Server\Response;
use Cspray\Labrador\Http\Controller\Dto\Body;
use Cspray\Labrador\Http\Controller\Dto\Dto;
use Cspray\Labrador\Http\Controller\Dto\DtoController;
use Cspray\Labrador\Http\Controller\Dto\Get;
use Cspray\Labrador\Http\Controller\Dto\Header;
use Cspray\Labrador\Http\Controller\Dto\Headers;
use Cspray\Labrador\Http\Controller\Dto\Method;
use Cspray\Labrador\Http\Controller\Dto\Post;
use Cspray\Labrador\Http\Controller\Dto\Put;
use Cspray\Labrador\Http\Controller\Dto\QueryParams;
use Cspray\Labrador\Http\Controller\Dto\RouteParam;
use Cspray\Labrador\Http\Controller\Dto\Url;
use Cspray\Labrador\HttpDummyApp\Model\Widget;
use League\Uri\Components\Query;
use League\Uri\Contracts\QueryInterface;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\UuidInterface;

#[DtoController]
class CheckDtoController {

    #[Get('/dto/headers')]
    public function checkHeaders(#[Headers] array $requestHeaders) : Response {
        return new Response(body: 'Received headers ' . json_encode($requestHeaders));
    }

    #[Get('/dto/headers-param-name')]
    public function checkHeadersParamName(#[Headers] array $ripuafkdl) : Response {
        return new Response(body: 'Received headers ' . json_encode($ripuafkdl));
    }

    #[Post('/dto/method')]
    public function checkMethod(#[Method] string $method) : Response {
        return new Response(body: 'Received method ' . $method);
    }

    #[Get('/dto/header-array')]
    public function checkSingleHeaderArray(#[Header('Custom-Header')] array $headers) : Response {
        return new Response(body: 'Received header for Custom-Header ' . json_encode($headers));
    }

    #[Get('/dto/header-string')]
    public function checkSingleHeaderString(#[Header('Authorization')] string $authHeader) : Response {
        return new Response(body: 'Received Authorization header ' . $authHeader);
    }

    #[Put('/dto/url')]
    public function checkUrl(#[Url] UriInterface $url) : Response {
        return new Response(body: 'Received UriInterface ' . $url);
    }

    #[Get('/dto/method-and-url')]
    public function getMethodAndUrl(#[Method] string $method, #[Url] UriInterface $uri) : Response {
        return new Response(body: 'Received ' . $method . ' and ' . $uri);
    }

    #[Get('/dto/query')]
    public function getQueryAsString(#[QueryParams] string $query) : Response {
        return new Response(body: 'Received query as string ' . $query);
    }

    #[Get('/dto/query-interface')]
    public function checkQueryInterface(#[QueryParams] QueryInterface $query) : Response {
        return new Response(body: 'Received query as QueryInterface ' . $query);
    }

    #[Get('/dto/query-component')]
    public function checkQueryComponent(#[QueryParams] Query $query) : Response {
        return new Response(body: 'Received query as Query ' . $query);
    }

    #[Get('/dto/widget/{id}')]
    public function checkRouteParam(#[RouteParam('id')] string $widgetId) : Response {
        return new Response(body: 'Received widget id as string ' . $widgetId);
    }

    #[Post('/dto/widget/{uuid}')]
    public function checkRouteParamUuid(#[RouteParam('uuid')] UuidInterface $id) : Response {
        return new Response(body: 'Received widget id as UuidInterface ' . $id);
    }

    #[Get('/dto/uri-by-type')]
    public function checkUriInjectedByType(UriInterface $uri) : Response {
        return new Response(body: 'Received request URL as type ' . $uri);
    }

    #[Get('/dto/query-interface-by-type')]
    public function checkQueryInterfaceByType(QueryInterface $query) : Response {
        return new Response(body: 'Received QueryInterface as type ' . $query->toRFC1738());
    }

    #[Get('/dto/query-by-type')]
    public function checkQueryByType(Query $query) : Response {
        return new Response(body: 'Received Query as type ' . $query->toRFC1738());
    }

    #[Put('/dto/request-body')]
    public function checkBodyByType(RequestBody $requestBody) : Response {
        return new Response(body: 'Received body as type ' . $requestBody->buffer());
    }

    #[Post('/dto/request-body-string')]
    public function checkBodyAsString(#[Body] string $body) : Response {
        return new Response(body: 'Received body as string ' . $body);
    }

    #[Get('/dto/request-body-attr')]
    public function checkBodyAsTypeAttribute(#[Body] RequestBody $requestBody) : Response {
        return new Response(body: 'Received body as type from attribute ' . $requestBody->buffer());
    }

    #[Post('/dto/widget')]
    public function checkWidgetDto(#[Dto] Widget $widget) : Response {
        return new Response(body: 'Received widget as Dto ' . json_encode($widget));
    }

}