<?php

declare(strict_types=1);

/**
 * An object that represents what HTTP Request data should be mapped to which handler.
 *
 * @license See LICENSE in source root
 */

namespace Labrador\Http\Router;

use Labrador\Http\Controller\Controller;

final class Route {

    public function __construct(
        public readonly RequestMapping $requestMapping,
        public readonly Controller     $controller
    ) {}

    public function toString() : string {
        return sprintf(
            "%s\t%s\t\t%s",
            $this->requestMapping->method->value,
            $this->requestMapping->pathPattern,
            $this->controller->toString()
        );
    }

}
