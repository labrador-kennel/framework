<?php declare(strict_types=1);

namespace Labrador\Web\Session;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
final class LockAndAutoCommitSessionMiddleware implements Middleware {

    public function __construct(
        private readonly SessionHelper $sessionHelper
    ) {
    }

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
        $this->sessionHelper->lock($request);

        $response = $requestHandler->handleRequest($request);

        if ($this->sessionHelper->isLocked($request)) {
            $this->sessionHelper->commit($request);
        }

        return $response;
    }
}
