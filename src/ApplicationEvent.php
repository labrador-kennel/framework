<?php

namespace Labrador\Http;

enum ApplicationEvent : string {
    case ApplicationStarted = 'labrador.http.appStarted';
    case AddRoutes = 'labrador.http.addRoutes';
    case ReceivingConnections = 'labrador.http.receivingConnections';
    case RequestReceived = 'labrador.http.requestReceived';
    case WillInvokeController = 'labrador.http.willInvokeController';
    case ResponseSent = 'labrador.http.responseSent';
    case ApplicationStopped = 'labrador.http.appStopped';
}
