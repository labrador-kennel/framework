<?php declare(strict_types=1);

namespace Labrador\TestHelper;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Controller\Controller;

interface InvokedControllerResponse {

    public function invokedController() : Controller;

    public function request() : Request;

    public function response() : Response;

    public function readSession() : array;
}
