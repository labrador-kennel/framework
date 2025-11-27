<?php declare(strict_types=1);

namespace Labrador\Web\Application\Event;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;

interface RequestHandlerAndRequest {

    public function requestHandler() : RequestHandler;

    public function request() : Request;
}
