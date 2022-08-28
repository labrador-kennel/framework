<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Unit\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Cspray\Labrador\Http\Controller\HookableController;

class OnlyHandlerHookableControllerStub extends HookableController {

    public function toString(): string {
        return self::class;
    }

    protected function handle(Request $request): Response {
        return new Response(200, [], 'From Only Handler');
    }
}
