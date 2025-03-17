<?php declare(strict_types=1);

namespace Labrador\Web\Autowire;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Session\SessionMiddleware;
use Labrador\Web\Router\Mapping\RequestMapping;
use Labrador\Web\Session\CsrfAwareSessionMiddleware;
use Labrador\Web\Session\LockAndAutoCommitSessionMiddleware;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class SessionAwareController implements AutowireableController {

    /**
     * @param RequestMapping $requestMapping
     * @param list<class-string<Middleware>> $middleware
     * @param list<non-empty-string> $profiles
     */
    public function __construct(
        private readonly RequestMapping $requestMapping,
        private readonly array $middleware = [],
        private readonly array $profiles = []
    ) {
    }

    public function requestMapping() : RequestMapping {
        return $this->requestMapping;
    }

    public function middleware() : array {
        return [
            ...[
                SessionMiddleware::class,
                CsrfAwareSessionMiddleware::class,
                LockAndAutoCommitSessionMiddleware::class
            ],
            ...$this->middleware
        ];
    }

    public function profiles() : array {
        return $this->profiles;
    }

    public function isPrimary() : bool {
        return false;
    }

    public function name() : ?string {
        return null;
    }
}
