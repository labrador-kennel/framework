<?php

namespace Labrador\Web\Application;

use Labrador\Web\Event\AddRoutes;
use Labrador\Web\Event\ApplicationStarted;
use Labrador\Web\Event\ApplicationStopped;
use Labrador\Web\Event\ReceivingConnections;
use Labrador\Web\Event\RequestReceived;
use Labrador\Web\Event\ResponseSent;
use Labrador\Web\Event\WillInvokeController;

enum ApplicationEvent : string {
    case ApplicationStarted = ApplicationStarted::class;
    case AddRoutes = AddRoutes::class;
    case ReceivingConnections = ReceivingConnections::class;
    case RequestReceived = RequestReceived::class;
    case WillInvokeController = WillInvokeController::class;
    case ResponseSent = ResponseSent::class;
    case ApplicationStopped = ApplicationStopped::class;
}
