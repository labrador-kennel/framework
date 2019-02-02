<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Stub;

use Amp\Failure;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Cspray\Labrador\Http\Controller\HookableController;

class BeforeActionResponseHookableControllerStub extends HookableController {

    public function beforeAction(Request $request) : Promise {
        $response = new Response(200, [], 'From beforeAction');
        return new Success($response);
    }

    public function handle(Request $request): Promise {
        $exception = new \Exception('handle() should not be called');
        return new Failure($exception);
    }

    public function afterAction(Request $request, Response $response): Promise {
        $exception = new \Exception('afterAction() hould not be called');
        return new Failure($exception);
    }

    public function toString(): string {
        // TODO: Implement toString() method.
    }

}