<?php

namespace Labrador\Web\Application;

use Labrador\Web\Application\Event\AddRoutes;
use Labrador\Web\Application\Event\ApplicationStarted;
use Labrador\Web\Application\Event\ApplicationStopped;
use Labrador\Web\Application\Event\ReceivingConnections;
use Labrador\Web\Application\Event\RequestReceived;
use Labrador\Web\Application\Event\ResponseSent;
use Labrador\Web\Application\Event\WillInvokeController;

enum ApplicationEvent : string {
    case ApplicationStarted = ApplicationStarted::class;
    case AddRoutes = AddRoutes::class;
    case ReceivingConnections = ReceivingConnections::class;
    case RequestReceived = RequestReceived::class;
    case WillInvokeController = WillInvokeController::class;
    case ResponseSent = ResponseSent::class;
    case ApplicationStopped = ApplicationStopped::class;
}
