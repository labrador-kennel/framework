<?php declare(strict_types=1);

namespace Labrador\DummyApp\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Controller\SelfDescribingController;
use RuntimeException;

class ExceptionThrowingController extends SelfDescribingController {

    public function handleRequest(Request $request) : Response {
        throw new RuntimeException('A message detailing what went wrong.');
    }

}
