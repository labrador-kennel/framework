<?php

namespace Labrador\Web;

enum RequestAttribute : string {
    case RequestHandler = 'labrador.http.requestHandler';
    case RequestId = 'labrador.http.requestId';
}
