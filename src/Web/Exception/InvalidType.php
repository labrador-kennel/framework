<?php

declare(strict_types=1);

/**
 *
 * @license See LICENSE in source root
 */

namespace Labrador\Web\Exception;

use Labrador\Exception\Exception;

class InvalidType extends Exception {

    public static function fromDispatcherCallbackInvalidReturn() : self {
        $msg = 'A FastRoute\\Dispatcher must be returned from dispatcher callback injected in constructor';
        return new self($msg);
    }

}
