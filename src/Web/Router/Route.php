<?php

declare(strict_types=1);

/**
 * An object that represents what HTTP Request data should be mapped to which handler.
 *
 * @license See LICENSE in source root
 */

namespace Labrador\Web\Router;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use Labrador\Web\Router\Mapping\RequestMapping;

final class Route {

    /**
     * @param RequestMapping $requestMapping
     * @param RequestHandler $requestHandler
     * @param list<Middleware> $middleware
     */
    public function __construct(
        public readonly RequestMapping $requestMapping,
        public readonly RequestHandler $requestHandler,
        public readonly array $middleware
    ) {
    }

    public function toString() : string {
        return sprintf(
            "%s\t%s\t\t%s",
            $this->requestMapping->getHttpMethod()->value,
            $this->requestMapping->getPath(),
            $this->requestHandler::class
        );
    }
}
