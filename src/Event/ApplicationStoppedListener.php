<?php

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\AsyncEvent\AbstractListener;
use Cspray\Labrador\Http\ApplicationEvent;

abstract class ApplicationStoppedListener extends AbstractListener {

    public function __construct() {
        parent::__construct(ApplicationEvent::ApplicationStopped->value);
    }

}