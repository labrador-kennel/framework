<?php declare(strict_types=1);

namespace Labrador\Logging;

enum LoggerType : string {
    case WebServer = 'labrador.web-server';
    case Application = 'labrador.app';
    case Router = 'labrador.router';
    case Worker = 'labrador.worker';
}
