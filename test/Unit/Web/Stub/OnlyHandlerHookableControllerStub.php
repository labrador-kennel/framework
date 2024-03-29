<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Controller\HookableController;

class OnlyHandlerHookableControllerStub extends HookableController {

    public function toString(): string {
        return self::class;
    }

    protected function handle(Request $request): Response {
        return new Response(200, [], 'From Only Handler');
    }
}
