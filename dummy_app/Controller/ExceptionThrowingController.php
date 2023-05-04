<?php declare(strict_types=1);

namespace Labrador\HttpDummyApp\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Http\Controller\SelfDescribingController;
use RuntimeException;

class ExceptionThrowingController extends SelfDescribingController {

    public function handleRequest(Request $request) : Response {
        throw new RuntimeException('A message detailing what went wrong.');
    }

}
