<?php declare(strict_types=1);

namespace Labrador\TestHelper;

use Amp\Http\Cookie\RequestCookie;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionMiddleware;
use Amp\Http\Server\Session\SessionStorage;
use Labrador\Security\TokenGenerator;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Controller\MiddlewareController;
use Labrador\Web\Session\CsrfAwareSessionMiddleware;
use Labrador\Web\Session\LockAndAutoCommitSessionMiddleware;

class ControllerInvoker {

    public const TEST_SESSION_ID = 'known-session-id-controller-invoker';
    public const TEST_TOKEN = 'known-token';

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

    /**
     * @param array<string, string> $initialSessionData
     * @param Middleware ...$middleware
     * @return self
     * @throws \Amp\Http\Server\Session\SessionException
     */
    public static function withTestSessionMiddleware(
        array $initialSessionData = [],
        Middleware ...$middleware
    ) : self {
        $sessionStorage = new LocalSessionStorage();
        $knownSessionIdGenerator = new KnownSessionIdGenerator(self::TEST_SESSION_ID);
        $knownTokenGenerator = new class(self::TEST_TOKEN) implements TokenGenerator {
            public function __construct(
                private readonly string $token,
            ) {
            }

            public function generateToken() : string {
                return $this->token;
            }
        };
        $sessionStorage->write(self::TEST_SESSION_ID, $initialSessionData);

        return new self(
            $sessionStorage,
            new SessionMiddleware(
                new SessionFactory(
                    storage: $sessionStorage,
                    idGenerator: $knownSessionIdGenerator
                )
            ),
            new CsrfAwareSessionMiddleware($knownTokenGenerator),
            new LockAndAutoCommitSessionMiddleware(),
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
        $request->setCookie(
            new RequestCookie('session', self::TEST_SESSION_ID)
        );
        return new class(
            $invokedController,
            $request,
            $invokedController->handleRequest($request),
            $this->sessionStorage,
            self::TEST_SESSION_ID
        ) implements InvokedControllerResponse {

            public function __construct(
                private readonly Controller $controller,
                private readonly Request $request,
                private readonly Response $response,
                private readonly SessionStorage $sessionStorage,
                private readonly string $sessionKey
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
                return $this->sessionStorage->read($this->sessionKey);
            }
        };
    }
}
