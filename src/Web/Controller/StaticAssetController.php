<?php declare(strict_types=1);

namespace Labrador\Web\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\StaticContent\DocumentRoot;

final class StaticAssetController extends SelfDescribingController {

    public function __construct(
        private readonly DocumentRoot $root,
        private readonly ErrorHandler $errorHandler
    ) {
    }


    public function handleRequest(Request $request) : Response {
        if (!$request->hasAttribute('path')) {
            return $this->errorHandler->handleError(HttpStatus::NOT_FOUND);
        }

        $request->setUri($request->getUri()->withPath('/' . $request->getAttribute('path')));

        return $this->root->handleRequest($request);
    }
}
