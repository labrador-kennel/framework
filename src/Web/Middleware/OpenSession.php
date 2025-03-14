<?php declare(strict_types=1);

namespace Labrador\Web\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Web\Exception\SessionNotEnabled;

#[Service]
final class OpenSession implements Middleware {

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
        if (!$request->hasAttribute(Session::class)) {
            throw SessionNotEnabled::fromOpenSessionMiddlewareFoundNoSession();
        }

        $session = $request->getAttribute(Session::class);
        assert($session instanceof Session);
        $session->lock();

        $response = $requestHandler->handleRequest($request);

        if ($session->isLocked()) {
            $session->commit();
        }

        return $response;
    }
}
