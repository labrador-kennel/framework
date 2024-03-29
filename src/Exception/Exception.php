<?php

declare(strict_types=1);

/**
 *
 * @license See LICENSE in source root
 */

namespace Labrador\Exception;

use Exception as PhpException;
use Throwable;

class Exception extends PhpException {

    final protected function __construct(string $message = "", int $code = 0, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
