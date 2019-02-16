<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Cspray\Labrador\Http\Controller\HookableController;

class SequenceHookableControllerStub extends HookableController {

    private $requests = [];

    public function beforeAction(Request $request): Promise {
        $this->requests[] = ['beforeAction', $request];
        return new Success();
    }

    protected function handle(Request $request) : Promise {
        $this->requests[] = ['handle', $request];
        return new Success(new Response());
    }

    public function afterAction(Request $request, Response $response): Promise {
        $this->requests[] = ['afterAction', $request];
        return new Success();
    }

    public function getReceivedRequests() : array {
        return $this->requests;
    }

    public function toString() : string {
        return self::class;
    }
}
