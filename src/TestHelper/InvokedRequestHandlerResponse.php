<?php declare(strict_types=1);

namespace Labrador\TestHelper;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

interface InvokedRequestHandlerResponse {

    public function requestHandler() : RequestHandler;

    /**
     * @return list<Middleware>
     */
    public function middleware() : array;

    public function request() : Request;

    public function response() : Response;

    public function readSession() : array;
}
