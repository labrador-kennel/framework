<?php

namespace Cspray\Labrador\Http;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;

#[Service]
class ErrorHandlerFactory {

    private ?ErrorHandler $errorHandler;

    #[ServiceDelegate]
    public function createErrorHandler() : ErrorHandler {
        return $this->errorHandler ?? new DefaultErrorHandler();
    }

    public function setErrorHandler(ErrorHandler $errorHandler) : void {
        $this->errorHandler = $errorHandler;
    }


}