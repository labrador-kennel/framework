<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Stub;

use Amp\Http\Server\Request;
use Amp\Promise;
use Amp\Success;
use Cspray\Labrador\Http\Controller\HookableController;

class NonResponseReturningHandleHookableControllerStub extends HookableController {

    public function toString(): string {
        return self::class;
    }

    protected function handle(Request $request): Promise {
        return new Success('not a response object');
    }
}
