<?php

namespace Cspray\Labrador\Http;

enum ApplicationEvent : string {
    case ApplicationStarted = 'labrador.http.appStarted';
    case AddRoutes = 'labrador.http.addRoutes';
    case ReceivingConnections = 'labrador.http.receivingConnections';
    case RequestReceived = 'labrador.http.requestReceived';
    case ControllerInvoked = 'labrador.http.controllerInvoked';
    case ResponseSent = 'labrador.http.responseSent';
    case ApplicationStopped = 'labrador.http.appStopped';
}
