<?php declare(strict_types=1);

namespace Labrador\TestHelper;

use Amp\Http\Cookie\RequestCookie;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionMiddleware;
use Amp\Http\Server\Session\SessionStorage;
use Labrador\Security\TokenGenerator;
use Labrador\Web\RequestAttribute;
use Labrador\Web\Session\CsrfAwareSessionMiddleware;
use Labrador\Web\Session\LockAndAutoCommitSessionMiddleware;
use Labrador\Web\Session\SessionHelper;

class RequestHandlerInvoker {

    public const TEST_TOKEN = 'known-token';

    /**
     * @var Middleware[]
     */
    private readonly array $applicationMiddleware;


    private function __construct(
        private readonly SessionStorage $sessionStorage,
        private readonly KnownSessionIdGenerator $sessionIdGenerator,
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
        $knownSessionIdGenerator = new KnownSessionIdGenerator();
        $knownTokenGenerator = new class(self::TEST_TOKEN) implements TokenGenerator {
            public function __construct(
                private readonly string $token,
            ) {
            }

            public function generateToken() : string {
                return $this->token;
            }
        };
        $sessionStorage->write($knownSessionIdGenerator->currentId(), $initialSessionData);
        $sessionHelper = new SessionHelper();

        return new self(
            $sessionStorage,
            $knownSessionIdGenerator,
            new SessionMiddleware(
                new SessionFactory(
                    storage: $sessionStorage,
                    idGenerator: $knownSessionIdGenerator
                )
            ),
            new CsrfAwareSessionMiddleware($knownTokenGenerator, $sessionHelper),
            new LockAndAutoCommitSessionMiddleware($sessionHelper),
            ...$middleware
        );
    }

    public function invokeRequestHandler(
        Request $request,
        RequestHandler $requestHandler,
        Middleware ...$middleware,
    ) : InvokedRequestHandlerResponse {
        $request->setCookie(
            new RequestCookie('session', $this->sessionIdGenerator->currentId())
        );
        $request->setAttribute(RequestAttribute::RequestHandler->value, $requestHandler);
        return new class(
            $requestHandler,
            array_values([...$this->applicationMiddleware, ...$middleware]),
            $request,
            Middleware\stackMiddleware($requestHandler, ...$this->applicationMiddleware, ...$middleware)->handleRequest($request),
            $this->sessionStorage,
            $this->sessionIdGenerator->currentId(),
        ) implements InvokedRequestHandlerResponse {

            /**
             * @param RequestHandler $requestHandler
             * @param list<Middleware> $middleware
             * @param Request $request
             * @param Response $response
             * @param SessionStorage $sessionStorage
             * @param string $sessionKey
             */
            public function __construct(
                private readonly RequestHandler $requestHandler,
                private readonly array $middleware,
                private readonly Request $request,
                private readonly Response $response,
                private readonly SessionStorage $sessionStorage,
                private readonly string $sessionKey
            ) {
            }

            public function requestHandler() : RequestHandler {
                return $this->requestHandler;
            }

            public function middleware() : array {
                return $this->middleware;
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
