<?php

namespace Cspray\Labrador\Http\Http;

enum HttpMethod : string {
    case Connect = 'CONNECT';
    case Delete = 'DELETE';
    case Get = 'GET';
    case Head = 'HEAD';
    case Patch = 'PATCH';
    case Post = 'POST';
    case Put = 'PUT';
    case Options = 'OPTIONS';
    case Trace = 'TRACE';
}
