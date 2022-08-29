<?php

namespace Cspray\Labrador\Http;

use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface ErrorHandlerFactory {

    public function createErrorHandler() : ErrorHandler;

}