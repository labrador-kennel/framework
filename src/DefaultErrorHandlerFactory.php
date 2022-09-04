<?php

namespace Labrador\Http;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;

#[Service]
class DefaultErrorHandlerFactory implements ErrorHandlerFactory {

    public function createErrorHandler() : ErrorHandler {
        return new DefaultErrorHandler();
    }

}