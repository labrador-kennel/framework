<?php declare(strict_types=1);

namespace Labrador\Http\Controller;

use Amp\Http\Server\RequestHandler;
use Cspray\AnnotatedContainer\Attribute\Service;

/**
 * An interface that adds to Amp's RequestHandler to attach a descriptor for improved logging and debugging.
 */
interface Controller extends RequestHandler {

    /**
     * Return a description of the Controller that would be suitable for use in logs and other debugging output.
     *
     * @return non-empty-string
     */
    public function toString() : string;
}
