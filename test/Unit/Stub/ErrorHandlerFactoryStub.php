<?php

namespace Labrador\Http\Test\Unit\Stub;

use Amp\Http\Server\ErrorHandler;
use Labrador\Http\ErrorHandlerFactory;

final class ErrorHandlerFactoryStub implements ErrorHandlerFactory {

    public function __construct(
        private readonly ErrorHandler $errorHandler
    ) {}

    public function createErrorHandler() : ErrorHandler {
        return $this->errorHandler;
    }
}