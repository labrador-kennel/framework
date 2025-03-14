<?php

namespace Labrador\Web\Application;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface Application {
    public function start() : void;

    public function stop() : void;
}
