<?php

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\ErrorHandler;
use Labrador\Web\ErrorHandlerFactory;

final class ErrorHandlerFactoryStub implements ErrorHandlerFactory {

    public function __construct(
        private readonly ErrorHandler $errorHandler
    ) {}

    public function createErrorHandler() : ErrorHandler {
        return $this->errorHandler;
    }
}