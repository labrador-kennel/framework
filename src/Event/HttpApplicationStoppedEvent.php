<?php declare(strict_types=1);


namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\AsyncEvent\StandardEvent;
use Cspray\Labrador\Http\HttpApplication;

class HttpApplicationStoppedEvent extends StandardEvent {

    public function __construct(HttpApplication $app) {
        parent::__construct(HttpApplication::APPLICATION_STOPPED_EVENT, $app);
    }

}