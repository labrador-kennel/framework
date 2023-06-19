<?php declare(strict_types=1);

namespace Labrador\Web\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Web\Autowire\RouteMiddleware;
use Labrador\Web\Exception\SessionNotEnabled;

#[RouteMiddleware]
final class OpenSession implements Middleware {

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
        if (!$request->hasAttribute(Session::class)) {
            throw SessionNotEnabled::fromOpenSessionMiddlewareFoundNoSession();
        }

        $session = $request->getAttribute(Session::class);
        assert($session instanceof Session);
        $session->open();

        $response = $requestHandler->handleRequest($request);

        $session->save();

        return $response;
    }
}