<?php declare(strict_types=1);

namespace Labrador\Web\Controller;

use Attribute;
use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;

/**
 * An Attribute to mark that each method annotated with a RouteMappingAttribute should be treated as its own individual
 * Controller.
 *
 * This Attribute allows for 2 important pieces of functionality provided by Labrador:
 *
 * 1. If you prefer to group cohesive functions on the same resource in one object you can easily do so. For example,
 * you can group GET, POST, PUT, DELETE actions on a "widget" into the same class. Below is a simple skeleton that
 * demonstrates how to use this functionality.
 *
 * use Labrador\Controller\ControllerActions;
 * use Labrador\Controller\Get;
 * use Labrador\Controller\Post;
 * use Amp\Http\Request;
 * use Amp\Http\Response;
 *
 * #[ControllerActions]
 * class WidgetController {
 *
 *     #[Get('/widget')]
 *     public function index() : Response {
 *
 *     }
 *
 *     #[Get('/widget/{id}')]
 *     public function details(Request $request) : Response {
 *
 *     }
 *
 *     #[Post('/widget')]
 *     public function create(Request $request) : Response {
 *
 *     }
 *
 *     // additional methods would be attributed with the appropriate RequestMappingAttribute implementation
 * }
 *
 * 2. Allow for injecting specific portions of the Request utilizing the functionality found in Labrador\Controller\Dto.
 * For example, you could inject a specific header, the request body, or even automatically marshal a Request body
 * into a Data Transfer Object using Valinor. Below is a small example of some functionality provided by the
 * Dto namespace.
 *
 * use Labrador\Controller\ControllerActions;
 * use Labrador\Controller\Get;
 * use Labrador\Controller\Dto\Headers;
 * use Amp\Http\Response;
 *
 * #[ControllerActions]
 * class DtoExample {
 *
 *      #[Get('/headers')]
 *      public function headers(#[Headers] array $headers) : Response {
 *
 *      }
 *
 *
 * }
 *
 *
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class ControllerActions implements ServiceAttribute {

    public function __construct(
        /**
         * @var list<string> $profiles
         */
        private readonly array $profiles = []
    ) {}

    public function getProfiles() : array {
        return $this->profiles;
    }

    public function isPrimary() : bool {
        return false;
    }

    public function getName() : ?string {
        return null;
    }
}