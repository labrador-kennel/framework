<?php

namespace Labrador\Web\Application;

use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface ErrorHandlerFactory {

    public function createErrorHandler() : ErrorHandler;
}
