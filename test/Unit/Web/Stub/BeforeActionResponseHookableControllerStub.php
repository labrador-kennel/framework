<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Controller\HookableController;

class BeforeActionResponseHookableControllerStub extends HookableController {

    public function beforeAction(Request $request) : ?Response {
        return new Response(200, [], 'From beforeAction');
    }

    public function handle(Request $request): Response {
        throw new \Exception('handle() should not be called');
    }

    public function afterAction(Request $request, Response $response) : ?Response {
        throw new \Exception('afterAction() hould not be called');
    }

    public function toString(): string {
        // TODO: Implement toString() method.
    }
}
