<?php declare(strict_types=1);

namespace Labrador\Web\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

/**
 * A Controller implementation that allows you to hook into the processing so that you may short circuit normal
 * processing with beforeAction or decorate a Response from normal processing with afterAction.
 *
 * @license See LICENSE in source root.
 */
abstract class HookableController extends SelfDescribingController implements Controller {

    /**
     * The entry point for Amp\Http\Server to start the execution of a Request; this method delegates processing to the
     * handle() method and ensures the beforeAction and afterAction methods are called appropriately.
     *
     * @param Request $request
     * @return Response
     */
    final public function handleRequest(Request $request) : Response {
        $potentialResponse = $this->beforeAction($request);
        if ($potentialResponse instanceof Response) {
            $response = $potentialResponse;
        } else {
            $response = $this->handle($request);
            $potentialResponse = $this->afterAction($request, $response);
            if ($potentialResponse instanceof Response) {
                $response = $potentialResponse;
            }
        }

        return $response;
    }

    /**
     * Override this method in your concrete implementations to short circuit or perform some logic before the
     * Controller's primary execution occurs.
     *
     * If you resolve the returned Promise with a Response it will be sent back to the user and neither the handle()
     * method nor the afterAction() will be executed. If you resolve the Promise with any other value it will be
     * ignored.
     */
    protected function beforeAction(Request $request) : ?Response {
        return null;
    }

    /**
     * Override this method in your concrete implementations to decorate or change the Response after the Controller's
     * handle() method generates a Response.
     *
     * If you resolve the returned Promise with a Response it will be sent back to the user in place of the Response
     * from the handle() method. If you resolve the Promise with any other value it will be ignored.
     */
    protected function afterAction(Request $request, Response $response) : ?Response {
        return null;
    }

    /**
     * Execute the primary logic for your Controller; the Promise MUST resolve with a Response object.
     *
     * @param Request $request
     * @return Response
     */
    abstract protected function handle(Request $request) : Response;
}
