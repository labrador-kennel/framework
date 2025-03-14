<?php

declare(strict_types=1);

/**
 * An object that represents what HTTP Request data should be mapped to which handler.
 *
 * @license See LICENSE in source root
 */

namespace Labrador\Web\Router;

use Labrador\Web\Controller\Controller;
use Labrador\Web\Router\Mapping\RequestMapping;

final class Route {

    public function __construct(
        public readonly RequestMapping $requestMapping,
        public readonly Controller                  $controller
    ) {
    }

    public function toString() : string {
        return sprintf(
            "%s\t%s\t\t%s",
            $this->requestMapping->getHttpMethod()->value,
            $this->requestMapping->getPath(),
            $this->controller->toString()
        );
    }
}
