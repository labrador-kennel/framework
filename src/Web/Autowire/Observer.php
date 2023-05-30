<?php

namespace Labrador\Web\Autowire;

use Amp\Http\Server\Middleware;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceFromServiceDefinition;
use Cspray\AnnotatedContainer\Bootstrap\ServiceGatherer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceWiringObserver;
use Labrador\Logging\LoggerFactory;
use Labrador\Logging\LoggerType;
use Labrador\Web\Application\Application;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Controller\RouteMappingAttribute;
use Labrador\Web\Router\Route;
use Labrador\Web\Router\Router;
use Psr\Log\LoggerInterface;

class Observer extends ServiceWiringObserver {

    public function wireServices(AnnotatedContainer $container, ServiceGatherer $gatherer) : void {
        /** @var LoggerFactory $loggerFactory */
        $loggerFactory = $container->get(LoggerFactory::class);
        $logger = $loggerFactory->createLogger(LoggerType::Application);

        /** @var Application $app */
        $app = $container->get(Application::class);
        /** @var Router $router */
        $router = $container->get(Router::class);

        foreach ($gatherer->getServicesForType(Controller::class) as $controller) {
            $this->handlePotentialHttpController($container, $controller, $logger, $router);
        }

        foreach ($gatherer->getServicesForType(Middleware::class) as $middleware) {
            $this->handleApplicationMiddleware($logger, $app, $middleware);
        }
    }

    private function handlePotentialHttpController(
        AnnotatedContainer $container,
        ServiceFromServiceDefinition $controllerAndDefinition,
        LoggerInterface $logger,
        Router $router,
    ) : void {
        $controller = $controllerAndDefinition->getService();
        assert($controller instanceof Controller);
        $attr = $controllerAndDefinition->getDefinition()->getAttribute();

        if ($attr instanceof HttpController) {
            $this->handleHttpController(
                $container, $router, $logger, $attr, $controller
            );
        }
    }

    private function handleHttpController(
        AnnotatedContainer $container,
        Router $router,
        LoggerInterface $logger,
        HttpController $httpController,
        Controller $controller
    ) : void {
        $route = $router->addRoute(
            $httpController->getRequestMapping(),
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

    private function handleApplicationMiddleware(
        LoggerInterface $logger,
        Application $application,
        ServiceFromServiceDefinition $middlewareAndDefinition
    ) : void {
        $middleware = $middlewareAndDefinition->getService();
        assert($middleware instanceof Middleware);

        $attr = $middlewareAndDefinition->getDefinition()->getAttribute();

        if ($attr instanceof ApplicationMiddleware) {
            $application->addMiddleware(
                $middleware,
                $attr->getPriority()
            );
            $logger->info(
                'Adding {middleware} to application with {priority} priority.',
                [
                    'middleware' => $middleware::class,
                    'priority' => $attr->getPriority()->name
                ]
            );
        }

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
        foreach ($attr->getMiddleware() as $middlewareClass) {
            $middlewareService = $container->get($middlewareClass);
            assert($middlewareService instanceof Middleware);
            $middleware[] = $middlewareService;
        }
        return $middleware;
    }
}