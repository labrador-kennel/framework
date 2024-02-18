<?php declare(strict_types=1);

namespace Labrador\Web\TestHelper;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Controller\Controller;

interface InvokedControllerResponse {

    public function getInvokedController() : Controller;

    public function getRequest() : Request;

    public function getResponse() : Response;

    public function readSession() : array;

}
