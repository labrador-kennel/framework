<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Stub;

use function Amp\call;
use Amp\Http\Server\Request;
use Amp\Promise;
use Amp\Success;
use Cspray\Labrador\Http\Controller\HookableController;
use Amp\Http\Server\Response;

class AfterActionResponseDecoratorHookableControllerStub extends HookableController {

    public function toString(): string {
        return self::class;
    }

    protected function handle(Request $request): Promise {
        return new Success(new Response(200, [], 'OK'));
    }

    protected function afterAction(Request $request, Response $response) : Promise {
        return call(function() use($response) {
            $responseBody = yield $response->getBody()->read();
            return new Success(new Response(200, [], 'A-' . $responseBody));
        });
    }
}