<?php declare(strict_types=1);

namespace Labrador\Web\Application\Event;

use Labrador\Web\Middleware\GlobalMiddlewareCollection;
use Labrador\Web\Router\Router;

interface RouterAndGlobalMiddlewareCollection {

    public function router() : Router;

    public function globalMiddleware() : GlobalMiddlewareCollection;
}
