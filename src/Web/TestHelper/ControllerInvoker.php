<?php declare(strict_types=1);

namespace Labrador\Web\TestHelper;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionIdGenerator;
use Amp\Http\Server\Session\SessionMiddleware;
use Amp\Http\Server\Session\SessionStorage;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Controller\MiddlewareController;
use Labrador\Web\Middleware\OpenSession;

class ControllerInvoker {

    /**
     * @var Middleware[]
     */
    private readonly array $applicationMiddleware;

    private function __construct(
        private readonly SessionStorage $sessionStorage,
        Middleware ...$applicationMiddleware
    ) {
        $this->applicationMiddleware = $applicationMiddleware;
    }

    public static function withTestSessionMiddleware(Middleware ...$middleware) : self {
        $sessionStorage = new LocalSessionStorage();
        $knownSessionIdGenerator = new class implements SessionIdGenerator {
            public function generate() : string {
                return 'known-session-id';
            }

            public function validate(string $id) : bool {
                return $id === 'known-session-id';
            }
        };

        return new self(
            $sessionStorage,
            new SessionMiddleware(
                new SessionFactory(
                    storage: $sessionStorage,
                    idGenerator: $knownSessionIdGenerator
                )
            ),
            new OpenSession(),
            ...$middleware
        );
    }

    public function invokeController(
        Request $request,
        Controller $controller,
        Middleware ...$middleware,
    ) : InvokedControllerResponse {
        $invokedController = new MiddlewareController(
            $controller,
            ...$this->applicationMiddleware,
            ...$middleware
        );
        return new class(
            $invokedController,
            $request,
            $invokedController->handleRequest($request),
            $this->sessionStorage,
        ) implements InvokedControllerResponse {

            public function __construct(
                private readonly Controller $controller,
                private readonly Request $request,
                private readonly Response $response,
                private readonly SessionStorage $sessionStorage,
            ) {
            }

            public function invokedController() : Controller {
                return $this->controller;
            }

            public function request() : Request {
                return $this->request;
            }

            public function response() : Response {
                return $this->response;
            }

            public function readSession() : array {
                return $this->sessionStorage->read('known-session-id');
            }
        };
    }
}
