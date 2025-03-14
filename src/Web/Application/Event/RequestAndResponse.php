<?php declare(strict_types=1);

namespace Labrador\Web\Application\Event;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

interface RequestAndResponse {

    public function request() : Request;

    public function response() : Response;
}
