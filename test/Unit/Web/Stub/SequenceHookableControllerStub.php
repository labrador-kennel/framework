<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Controller\HookableController;

class SequenceHookableControllerStub extends HookableController {

    private $requests = [];

    public function beforeAction(Request $request) : ?Response {
        $this->requests[] = ['beforeAction', $request];
        return null;
    }

    protected function handle(Request $request) : Response {
        $this->requests[] = ['handle', $request];
        return new Response();
    }

    public function afterAction(Request $request, Response $response) : ?Response {
        $this->requests[] = ['afterAction', $request];
        return null;
    }

    public function getReceivedRequests() : array {
        return $this->requests;
    }

    public function toString() : string {
        return self::class;
    }
}
