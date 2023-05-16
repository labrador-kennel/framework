<?php

namespace Labrador\Web;

enum RequestAttribute : string {
    case Controller = 'labrador.http.controller';
    case RequestId = 'labrador.http.requestId';
}