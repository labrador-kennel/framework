<?php

namespace Labrador\Web\Application\Event;

use Amp\Http\Server\Request;
use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Controller\Controller;

/**
 * @implements Event<ControllerAndRequest>
 */
final class WillInvokeController implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Controller $controller,
        private readonly Request $request,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function name() : string {
        return ApplicationEvent::WillInvokeController->value;
    }

    public function payload() : ControllerAndRequest {
        return new class($this->controller, $this->request) implements ControllerAndRequest {

            public function __construct(
                private readonly Controller $controller,
                private readonly Request $request,
            ) {
            }

            public function controller() : Controller {
                return $this->controller;
            }

            public function request() : Request {
                return $this->request;
            }
        };
    }

    public function triggeredAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}
