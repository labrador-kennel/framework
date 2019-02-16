<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Controller;

use Cspray\Labrador\Http\Exception\InvalidTypeException;

use Amp\Promise;
use Amp\Success;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

use function Amp\call;

/**
 * A Controller implementation that allows you to hook into the processing so that you may short circuit normal
 * processing with beforeAction or decorate a Response from normal processing with afterAction.
 *
 * @license See LICENSE in source root.
 */
abstract class HookableController implements Controller {

    /**
     * The entry point for Amp\Http\Server to start the execution of a Request; this method delegates processing to the
     * handle() method and ensures the beforeAction and afterAction methods are called appropriately.
     *
     * @param Request $request
     * @return Promise(Response)
     */
    final public function handleRequest(Request $request) : Promise {
        return call(function() use($request) {
            $potentialResponse = yield $this->beforeAction($request);
            if ($potentialResponse instanceof Response) {
                $response = $potentialResponse;
            } else {
                $response = yield $this->handle($request);
                if (!$response instanceof Response) {
                    $msg = 'The type resolved from a %s::handle() %s must be a %s.';
                    throw new InvalidTypeException(sprintf(
                        $msg,
                        get_class($this),
                        Promise::class,
                        Response::class
                    ));
                }
                $potentialResponse = yield $this->afterAction($request, $response);
                if ($potentialResponse instanceof Response) {
                    $response = $potentialResponse;
                }
            }

            return $response;
        });
    }

    /**
     * Override this method in your concrete implementations to short circuit or perform some logic before the
     * Controller's primary execution occurs.
     *
     * If you resolve the returned Promise with a Response it will be sent back to the user and neither the handle()
     * method nor the afterAction() will be executed. If you resolve the Promise with any other value it will be
     * ignored.
     *
     * @param Request $request
     * @return Promise(Response|null)
     */
    protected function beforeAction(Request $request) : Promise {
        return new Success();
    }

    /**
     * Override this method in your concrete implementations to decorate or change the Response after the Controller's
     * handle() method generates a Response.
     *
     * If you resolve the returned Promise with a Response it will be sent back to the user in place of the Response
     * from the handle() method. If you resolve the Promise with any other value it will be ignored.
     *
     * @param Request $request
     * @param Response $response
     * @return Promise(Response|null)
     */
    protected function afterAction(Request $request, Response $response) : Promise {
        return new Success();
    }

    /**
     * Execute the primary logic for your Controller; the Promise MUST resolve with a Response object.
     *
     * @param Request $request
     * @return Promise(Response)
     */
    abstract protected function handle(Request $request) : Promise;
}
