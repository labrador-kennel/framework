<?php

declare(strict_types=1);

/**
 * An object that represents what HTTP Request data should be mapped to which handler.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Router;

class Route {

    private $pattern;
    private $method;
    private $controllerClass;

    /**
     * @param string $pattern
     * @param string $method
     * @param string $controllerClass
     */
    public function __construct(string $pattern, string $method, string $controllerClass) {
        $this->pattern = $pattern;
        $this->method = $method;
        $this->controllerClass = $controllerClass;
    }

    /**
     * @return string
     */
    public function getPattern() : string {
        return $this->pattern;
    }

    /**
     * @return string
     */
    public function getMethod() : string {
        return $this->method;
    }

    /**
     * @return mixed
     */
    public function getControllerDescription() : string {
        return $this->controllerClass;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        $format = "%s\t%s\t\t%s";
        return sprintf($format, $this->method, $this->pattern, $this->controllerClass);
    }

}
