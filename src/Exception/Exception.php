<?php

declare(strict_types=1);

/**
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Exception;

use Cspray\Labrador\Exception\Exception as LabradorException;
use Throwable;

class Exception extends LabradorException {

    final protected function __construct(string $message = "", int $code = 0, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
