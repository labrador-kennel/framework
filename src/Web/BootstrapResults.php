<?php

namespace Labrador\Web;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Labrador\Web\Application\Application;

class BootstrapResults {

    public function __construct(
        public readonly Application $application,
        public readonly AnnotatedContainer $container
    ) {}

}