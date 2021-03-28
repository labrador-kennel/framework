<?php declare(strict_types=1);

namespace Cspray\Labrador\Http;

use Amp\Http\Server\Middleware;
use Cspray\Labrador\Application;

interface HttpApplication extends Application {

    public function addMiddleware(Middleware $middleware) : void;

    public function setExceptionToResponseHandler(callable $callable) : void;
}
