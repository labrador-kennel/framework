<?php declare(strict_types=1);

namespace Labrador\DummyApp\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Autowire\HttpController;
use Labrador\Web\Controller\SelfDescribingController;
use Labrador\Web\RequestAttribute;
use Labrador\Web\Router\Mapping\GetMapping;

#[HttpController(new GetMapping('/controller-request-attribute'))]
final class ControllerAttributeController extends SelfDescribingController {

    public function handleRequest(Request $request) : Response {
        return new Response(body: $request->getAttribute(RequestAttribute::Controller->value));
    }

}
