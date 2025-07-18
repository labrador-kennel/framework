<?php declare(strict_types=1);

namespace Labrador\Web\Session;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Security\TokenGenerator;
use Labrador\Web\Session\Exception\SessionNotAttachedToRequest;

#[Service]
final class CsrfAwareSessionMiddleware implements Middleware {

    public function __construct(
        private readonly TokenGenerator $tokenGenerator,
        private readonly SessionHelper $sessionHelper,
    ) {
    }

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
        $this->sessionHelper->lock($request);
        $csrfTokenAttribute = new CsrfTokenAttribute();
        if (!$this->sessionHelper->has($request, $csrfTokenAttribute)) {
            $this->sessionHelper->set($request, $csrfTokenAttribute, $this->tokenGenerator->generateToken());
        }
        $this->sessionHelper->commit($request);

        return $requestHandler->handleRequest($request);
    }
}
