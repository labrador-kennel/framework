<?php

namespace Labrador\Http\Application;

use Labrador\Http\Event\AddRoutes;
use Labrador\Http\Event\ApplicationStarted;
use Labrador\Http\Event\ApplicationStopped;
use Labrador\Http\Event\ReceivingConnections;
use Labrador\Http\Event\RequestReceived;
use Labrador\Http\Event\ResponseSent;
use Labrador\Http\Event\WillInvokeController;

enum ApplicationEvent : string {
    case ApplicationStarted = ApplicationStarted::class;
    case AddRoutes = AddRoutes::class;
    case ReceivingConnections = ReceivingConnections::class;
    case RequestReceived = RequestReceived::class;
    case WillInvokeController = WillInvokeController::class;
    case ResponseSent = ResponseSent::class;
    case ApplicationStopped = ApplicationStopped::class;
}
