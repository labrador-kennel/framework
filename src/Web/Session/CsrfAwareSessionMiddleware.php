<?php declare(strict_types=1);

namespace Labrador\Web\Session;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Security\TokenGenerator;
use Labrador\Web\Session\Exception\SessionNotAttachedToRequest;

final class CsrfAwareSessionMiddleware implements Middleware {

    public function __construct(
        private readonly TokenGenerator $tokenGenerator,
    ) {
    }

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
        if (!$request->hasAttribute(Session::class)) {
            throw SessionNotAttachedToRequest::fromSessionNotAttachedToRequest();
        }

        $session = $request->getAttribute(Session::class);
        assert($session instanceof Session);
        $session->lock();
        if (!$session->has('labrador.csrfToken')) {
            $session->set('labrador.csrfToken', $this->tokenGenerator->generateToken());
        }
        $session->commit();

        return $requestHandler->handleRequest($request);
    }
}
