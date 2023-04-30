<?php

namespace Labrador\Http;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Labrador\Http\Application\Application;

class BootstrapResults {

    public function __construct(
        public readonly Application $application,
        public readonly AnnotatedContainer $container
    ) {}

}