<?php

namespace Labrador\Web\Autowire;

use Amp\Http\Server\Middleware;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceFromServiceDefinition;
use Cspray\AnnotatedContainer\Bootstrap\ServiceGatherer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceWiringListener;
use Cspray\AnnotatedContainer\Profiles;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Controller\RouteMappingAttribute;
use Labrador\Web\Router\Route;
use Labrador\Web\Router\Router;
use Psr\Log\LoggerInterface;

final class RegisterControllerListener extends ServiceWiringListener {

    /**
     * @param list<non-empty-string> $profilesToRouteControllers
     */
    public function __construct(
        private readonly array $profilesToRouteControllers = ['web']
    ) {
    }

    public function wireServices(AnnotatedContainer $container, ServiceGatherer $gatherer) : void {
        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);
        /** @var Router $router */
        $router = $container->get(Router::class);
        /** @var Profiles $profiles */
        $profiles = $container->get(Profiles::class);

        if (!$profiles->isAnyActive($this->profilesToRouteControllers)) {
            return;
        }

        foreach ($gatherer->servicesForType(Controller::class) as $controller) {
            $this->handlePotentialHttpController($container, $controller, $logger, $router);
        }
    }

    private function handlePotentialHttpController(
        AnnotatedContainer $container,
        ServiceFromServiceDefinition $controllerAndDefinition,
        LoggerInterface $logger,
        Router $router,
    ) : void {
        $controller = $controllerAndDefinition->service();
        assert($controller instanceof Controller);
        $attr = $controllerAndDefinition->definition()->attribute();

        if ($attr instanceof AutowireableController) {
            $this->handleAutowireableController(
                $container,
                $router,
                $logger,
                $attr,
                $controller
            );
        }
    }

    private function handleAutowireableController(
        AnnotatedContainer $container,
        Router $router,
        LoggerInterface $logger,
        AutowireableController $httpController,
        Controller $controller
    ) : void {
        $route = $router->addRoute(
            $httpController->requestMapping(),
            $controller,
            ...$this->getMiddlewareFromRouteMappingAttribute($container, $httpController)
        );
        $this->logAddedRoute($route, $logger);
    }

    private function logAddedRoute(Route $route, LoggerInterface $logger) : void {
        $logger->info(
            'Autowiring route {method} {path} to {controller} controller.',
            [
                'method' => $route->requestMapping->getHttpMethod()->value,
                'path' => $route->requestMapping->getPath(),
                'controller' => $route->controller->toString()
            ]
        );
    }

    /**
     * @param AnnotatedContainer $container
     * @param RouteMappingAttribute $attr
     * @return list<Middleware>
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function getMiddlewareFromRouteMappingAttribute(AnnotatedContainer $container, RouteMappingAttribute $attr) : array {
        $middleware = [];
        foreach ($attr->middleware() as $middlewareClass) {
            $middlewareService = $container->get($middlewareClass);
            assert($middlewareService instanceof Middleware);
            $middleware[] = $middlewareService;
        }
        return $middleware;
    }
}
