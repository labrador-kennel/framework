<?php

namespace Labrador\Web;

use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface ErrorHandlerFactory {

    public function createErrorHandler() : ErrorHandler;

}