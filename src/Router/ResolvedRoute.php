<?php

declare(strict_types=1);

/**
 * The result of a call to Router::match that stores the Request being matched
 * against and the result for whether the Router found a matching and resolved
 * controller.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Router;

use Psr\Http\Message\ServerRequestInterface;

class ResolvedRoute {

    private $httpStatus;
    private $request;
    private $controller;
    private $availableMethods;

    /**
     * @param ServerRequestInterface $request
     * @param callable $controller
     * @param $httpStatus
     * @param array $availableMethods
     */
    public function __construct(ServerRequestInterface $request, callable $controller, $httpStatus, array $availableMethods = []) {
        $this->request = $request;
        $this->controller = $controller;
        $this->httpStatus = $httpStatus;
        $this->availableMethods = $availableMethods;
    }

    public function getRequest() : ServerRequestInterface {
        return $this->request;
    }

    /**
     * @return callable
     */
    public function getController() : callable {
        return $this->controller;
    }

    /**
     * @return bool
     */
    public function isOk() : bool {
        return $this->httpStatus === 200;
    }

    /**
     * @return bool
     */
    public function isNotFound() : bool {
        return $this->httpStatus === 404;
    }

    /**
     * @return bool
     */
    public function isMethodNotAllowed() : bool {
        return $this->httpStatus === 405;
    }

    /**
     * @return array
     */
    public function getAvailableMethods() : array {
        return $this->availableMethods;
    }

}
