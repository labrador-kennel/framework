<?php

declare(strict_types=1);

/**
 * Thrown if a handler set for a specific route is invalid; the validity of a handler
 * is determined based on domain logic implemented on top of Labrador.
 * 
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Exception;

class InvalidHandlerException extends Exception {}
