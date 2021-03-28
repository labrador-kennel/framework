<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\AsyncEvent\StandardEvent;
use Cspray\Labrador\Http\HttpApplication;

class HttpApplicationStartedEvent extends StandardEvent {

    public function __construct(HttpApplication $application) {
        parent::__construct(HttpApplication::APPLICATION_STARTED_EVENT, $application);
    }

}