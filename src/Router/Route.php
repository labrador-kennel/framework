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
    private $handler;

    /**
     * @param string $pattern
     * @param string $method
     * @param mixed $handler
     */
    public function __construct(string $pattern, string $method, $handler) {
        $this->pattern = $pattern;
        $this->method = $method;
        $this->handler = $handler;
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
    public function getHandler() {
        return $this->handler;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        $format = "%s\t%s\t\t%s";
        $handler = $this->getNormalizedHandler($this->handler);
        return sprintf($format, $this->method, $this->pattern, $handler);
    }

    private function getNormalizedHandler($handler) : string {
        if ($handler instanceof \Closure) {
            return 'closure{}';
        }

        if (is_object($handler)) {
            return get_class($handler);
        }

        if (is_array($handler)) {
            if (is_callable($handler)) {
                return get_class($handler[0]) . '::' . $handler[1];
            }

            return 'Array(' . count($handler) . ')';
        }

        return $handler;
    }

}
