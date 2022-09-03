<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Http\Controller\HookableController;

class AfterActionResponseDecoratorHookableControllerStub extends HookableController {

    public function toString(): string {
        return self::class;
    }

    protected function handle(Request $request) : Response {
        return new Response(200, [], 'OK');
    }

    protected function afterAction(Request $request, Response $response) : ?Response {
        $responseBody = $response->getBody()->read();
        return new Response(200, [], 'A-' . $responseBody);
    }
}
