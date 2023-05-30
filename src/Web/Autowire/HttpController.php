<?php

namespace Labrador\Web\Autowire;

use Amp\Http\Server\Middleware;
use Attribute;
use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;
use Labrador\Web\Controller\RouteMappingAttribute;
use Labrador\Web\Router\RequestMapping;

#[Attribute(Attribute::TARGET_CLASS)]
final class HttpController implements ServiceAttribute, RouteMappingAttribute {

    public function __construct(
        private readonly RequestMapping $requestMapping,
        /**
         * @var list<class-string<Middleware>> $middleware
         */
        private readonly array $middleware = [],
        /**
         * @var list<non-empty-string> $profiles
         */
        private readonly array $profiles = []
    ) {}

    public function getRequestMapping() : RequestMapping {
        return $this->requestMapping;
    }

    /**
     * @return list<class-string<Middleware>>
     */
    public function getMiddleware() : array {
        return $this->middleware;
    }

    /**
     * @return list<non-empty-string>
     */
    public function getProfiles() : array {
        return $this->profiles;
    }

    public function isPrimary() : bool {
        return false;
    }

    public function getName() : ?string {
        return null;
    }
}