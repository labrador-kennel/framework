<?php

namespace Cspray\Labrador\Http\DependencyInjection;

use Amp\Http\Server\Middleware;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Observer;
use Cspray\AnnotatedContainer\ContainerDefinition;
use Cspray\AnnotatedContainer\Definition\ServiceDefinition;
use Cspray\Labrador\Http\Application;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Controller\Dto\DtoController;
use Cspray\Labrador\Http\Controller\DtoControllerHandler;
use Cspray\Labrador\Http\Controller\HttpController;
use Cspray\Labrador\Http\Controller\RouteMappingAttribute;
use Cspray\Labrador\Http\Middleware\ApplicationMiddleware;
use Cspray\Labrador\Http\Router\RequestMapping;
use Cspray\Labrador\Http\Router\Route;
use Cspray\Labrador\Http\Router\Router;
use Psr\Log\LoggerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use function Cspray\AnnotatedContainer\autowiredParams;
use function Cspray\AnnotatedContainer\rawParam;

class AutowireObserver implements Observer {

    public function beforeCompilation() : void {
        // noop
    }

    public function afterCompilation(ContainerDefinition $containerDefinition) : void {
        // noop
    }

    public function beforeContainerCreation(ContainerDefinition $containerDefinition) : void {
        // noop
    }

    public function afterContainerCreation(ContainerDefinition $containerDefinition, AnnotatedContainer $container) : void {
        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);

        $logger->info('Container created, beginning to autowire services.');

        /** @var Application $app */
        $app = $container->get(Application::class);
        /** @var Router $router */
        $router = $container->get(Router::class);

        foreach ($containerDefinition->getServiceDefinitions() as $serviceDefinition) {
            if ($serviceDefinition->isAbstract()) {
                continue;
            }

            /** @var class-string $serviceType */
            $serviceType = $serviceDefinition->getType()->getName();
            if (is_a($serviceType, Controller::class, true)) {
                $this->handleController($container, $logger, $router, $serviceDefinition);
            } else if (is_a($serviceType, Middleware::class, true)) {
                $this->handleMiddleware($container, $logger, $app, $serviceDefinition);
            } else {
                $reflection = new ReflectionClass($serviceType);
                $attr = $reflection->getAttributes(DtoController::class);
                if ($attr !== []) {
                    $dtoController = $container->get($serviceType);
                    assert(is_object($dtoController));
                    foreach ($reflection->getMethods() as $reflectionMethod) {
                        $routeMappingAttributes = $reflectionMethod->getAttributes(RouteMappingAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
                        foreach ($routeMappingAttributes as $routeMappingAttribute) {
                            $routeMapping = $routeMappingAttribute->newInstance();
                            assert($routeMapping instanceof RouteMappingAttribute);

                            $route = $router->addRoute(
                                RequestMapping::fromMethodAndPath($routeMapping->getHttpMethod(), $routeMapping->getPath()),
                                $this->createDtoHandler($container, $dtoController, $reflectionMethod),
                                ...$this->getMiddlewareFromRouteMappingAttribute($container, $routeMapping)
                            );
                            $this->logAddedRoute($route, $logger);
                        }
                    }
                }
            }
        }
    }

    private function createDtoHandler(
        AnnotatedContainer $container,
        object $dtoController,
        ReflectionMethod $reflectionMethod
    ) : DtoControllerHandler {
        $description = sprintf('DtoHandler<%s::%s>', $reflectionMethod->getDeclaringClass()->getName(), $reflectionMethod->getName());

        $handler = $container->make(
            DtoControllerHandler::class,
            autowiredParams(
                rawParam('closure', $reflectionMethod->getClosure($dtoController)),
                rawParam('description', $description),
            )
        );
        assert($handler instanceof DtoControllerHandler);

        return $handler;
    }

    private function handleController(
        AnnotatedContainer $container,
        LoggerInterface $logger,
        Router $router,
        ServiceDefinition $serviceDefinition
    ) : void {
        /** @var class-string $serviceType */
        $serviceType = $serviceDefinition->getType()->getName();
        $reflection = new ReflectionClass($serviceType);
        $httpAttributes = $reflection->getAttributes(HttpController::class);

        if ($httpAttributes !== []) {
            $this->handleHttpController(
                $container, $router, $logger, $httpAttributes[0]->newInstance(), $serviceDefinition
            );
        }
    }

    private function handleHttpController(
        AnnotatedContainer $container,
        Router $router,
        LoggerInterface $logger,
        HttpController $httpController,
        ServiceDefinition $serviceDefinition
    ) : void {
        $controller = $container->get($serviceDefinition->getType()->getName());
        assert($controller instanceof Controller);

        $route = $router->addRoute(
            RequestMapping::fromMethodAndPath($httpController->getHttpMethod(), $httpController->getPath()),
            $controller,
            ...$this->getMiddlewareFromRouteMappingAttribute($container, $httpController)
        );
        $this->logAddedRoute($route, $logger);
    }

    private function logAddedRoute(Route $route, LoggerInterface $logger) : void {
        $logger->info(
            'Autowiring route {method} {path} to {controller} controller.',
            [
                'method' => $route->requestMapping->method->value,
                'path' => $route->requestMapping->pathPattern,
                'controller' => $route->controller->toString()
            ]
        );
    }

    private function handleMiddleware(
        AnnotatedContainer $container,
        LoggerInterface $logger,
        Application $application,
        ServiceDefinition $serviceDefinition
    ) : void {
        /** @var class-string $serviceType */
        $serviceType = $serviceDefinition->getType()->getName();
        $reflection = new ReflectionClass($serviceType);
        $attributes = $reflection->getAttributes(ApplicationMiddleware::class);

        if ($attributes === []) {
            return;
        }

        $appMiddleware = $attributes[0]->newInstance();
        $middleware = $container->get($serviceDefinition->getType()->getName());

        assert($middleware instanceof Middleware);

        $application->addMiddleware(
            $middleware,
            $appMiddleware->getPriority()
        );

        $logger->info(
            'Adding {middleware} to application with {priority} priority.',
            [
                'middleware' => $serviceDefinition->getType()->getName(),
                'priority' => $appMiddleware->getPriority()->name
            ]
        );
    }

    /**
     * @return list<Middleware>
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