<?php

namespace Labrador\Web\Application;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
final class DefaultErrorHandlerFactory implements ErrorHandlerFactory {

    public function createErrorHandler() : ErrorHandler {
        return new DefaultErrorHandler();
    }

}