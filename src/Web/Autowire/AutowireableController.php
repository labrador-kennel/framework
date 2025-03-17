<?php declare(strict_types=1);

namespace Labrador\Web\Autowire;

use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;
use Labrador\Web\Controller\RouteMappingAttribute;

interface AutowireableController extends ServiceAttribute, RouteMappingAttribute {

}
