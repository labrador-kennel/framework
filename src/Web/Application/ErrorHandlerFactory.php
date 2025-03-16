<?php

namespace Labrador\Web\Application;

use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;

#[Service]
interface ErrorHandlerFactory {

    #[ServiceDelegate]
    public function createErrorHandler() : ErrorHandler;
}
