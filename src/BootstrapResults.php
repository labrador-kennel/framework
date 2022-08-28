<?php

namespace Cspray\Labrador\Http;

use Cspray\AnnotatedContainer\AnnotatedContainer;

class BootstrapResults {

    public function __construct(
        public readonly Application $application,
        public readonly AnnotatedContainer $container
    ) {}

}