<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\Http\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseSentEvent extends HttpEvent {

    public function __construct(ServerRequestInterface $request, ResponseInterface $response) {
        parent::__construct($request, Engine::RESPONSE_SENT_EVENT);
        $this->setResponse($response);
    }

}