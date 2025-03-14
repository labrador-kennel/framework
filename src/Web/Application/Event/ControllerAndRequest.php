<?php declare(strict_types=1);

namespace Labrador\Web\Application\Event;

use Amp\Http\Server\Request;
use Labrador\Web\Controller\Controller;

interface ControllerAndRequest {

    public function controller() : Controller;

    public function request() : Request;
}
