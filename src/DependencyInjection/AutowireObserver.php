<?php

namespace Labrador\Http\DependencyInjection;

use Amp\Http\Server\Middleware;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\AnnotatedContainerVersion;
use Cspray\AnnotatedContainer\Bootstrap\ServiceGatherer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceWiringObserver;
use Labrador\Http\Application;
use Labrador\Http\Controller\Controller;
use Labrador\Http\Controller\DtoController;
use Labrador\Http\Controller\DtoControllerHandler;
use Labrador\Http\Controller\HttpController;
use Labrador\Http\Controller\RouteMappingAttribute;
use Labrador\Http\Middleware\ApplicationMiddleware;
use Labrador\Http\Router\RequestMapping;
use Labrador\Http\Router\Route;
use Labrador\Http\Router\Router;
use Psr\Log\LoggerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use function Amp\ByteStream\getStdout;
use function Cspray\AnnotatedContainer\autowiredParams;
use function Cspray\AnnotatedContainer\rawParam;

class AutowireObserver extends ServiceWiringObserver {

    public function wireServices(AnnotatedContainer $container, ServiceGatherer $gatherer) : void {
        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);

        getStdout()->write(file_get_contents(__DIR__ . '/../../resources/ascii/labrador.txt') . PHP_EOL);
        getStdout()->write('Labrador HTTP Version: dev-main' . PHP_EOL);
        getStdout()->write('Annotated Container Version: ' . AnnotatedContainerVersion::getVersion() . PHP_EOL);
        getStdout()->write('Amp HTTP Server Version: v3.0.0-beta.3' . PHP_EOL . PHP_EOL);

        $logger->info('Container created, beginning to autowire services.');

        /** @var Application $app */
        $app = $container->get(Application::class);
        /** @var Router $router */
        $router = $container->get(Router::class);

        foreach ($gatherer->getServicesForType(Controller::class) as $controller) {
            assert($controller instanceof Controller);
            $this->handlePotentialHttpController($container, $controller, $logger, $router);
        }

        foreach ($gatherer->getServicesForType(Middleware::class) as $middleware) {
            assert($middleware instanceof Middleware);
            $this->handleApplicationMiddleware($logger, $app, $middleware);
        }

        foreach ($gatherer->getServicesForType(DtoController::class) as $dtoController) {
            assert($dtoController instanceof DtoController);
            $this->handleDtoController($container, $dtoController, $logger, $router);
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

    private function handlePotentialHttpController(
        AnnotatedContainer $container,
        Controller $controller,
        LoggerInterface $logger,
        Router $router,
    ) : void {
        /** @var class-string $serviceType */
        $reflection = new ReflectionClass($controller);
        $httpAttributes = $reflection->getAttributes(HttpController::class);

        if ($httpAttributes !== []) {
            $this->handleHttpController(
                $container, $router, $logger, $httpAttributes[0]->newInstance(), $controller
            );
        }
    }

    private function handleDtoController(
        AnnotatedContainer $container,
        DtoController $controller,
        LoggerInterface $logger,
        Router $router
    ) : void {
        $reflection = new ReflectionObject($controller);
        foreach ($reflection->getMethods() as $reflectionMethod) {
            $routeMappingAttributes = $reflectionMethod->getAttributes(RouteMappingAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            foreach ($routeMappingAttributes as $routeMappingAttribute) {
                $routeMapping = $routeMappingAttribute->newInstance();
                assert($routeMapping instanceof RouteMappingAttribute);

                $route = $router->addRoute(
                    RequestMapping::fromMethodAndPath($routeMapping->getHttpMethod(), $routeMapping->getPath()),
                    $this->createDtoHandler($container, $controller, $reflectionMethod),
                    ...$this->getMiddlewareFromRouteMappingAttribute($container, $routeMapping)
                );
                $this->logAddedRoute($route, $logger);
            }
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

    private function handleApplicationMiddleware(
        LoggerInterface $logger,
        Application $application,
        Middleware $middleware
    ) : void {
        /** @var class-string $serviceType */
        $reflection = new ReflectionClass($middleware);
        $attributes = $reflection->getAttributes(ApplicationMiddleware::class);

        if ($attributes === []) {
            return;
        }

        $appMiddleware = $attributes[0]->newInstance();

        $application->addMiddleware(
            $middleware,
            $appMiddleware->getPriority()
        );

        $logger->info(
            'Adding {middleware} to application with {priority} priority.',
            [
                'middleware' => $middleware::class,
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