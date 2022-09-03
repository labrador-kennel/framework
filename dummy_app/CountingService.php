<?php

namespace Labrador\HttpDummyApp;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
class CountingService {

    private int $counter = 0;

    public function doIt() : void {
        $this->counter++;
    }

    public function getIt() : int {
        return $this->counter;
    }

}