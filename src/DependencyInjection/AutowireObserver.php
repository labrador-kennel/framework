<?php

namespace Labrador\Http\DependencyInjection;

use Amp\Http\Server\Middleware;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceFromServiceDefinition;
use Cspray\AnnotatedContainer\Bootstrap\ServiceGatherer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceWiringObserver;
use Labrador\Http\Application;
use Labrador\Http\Controller\Controller;
use Labrador\Http\Controller\ControllerActions;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use Labrador\Http\Controller\Dto\DtoInjectionManager;
use Labrador\Http\Controller\Dto\InjectionHandler\BodyHandler;
use Labrador\Http\Controller\Dto\InjectionHandler\DtoHandler;
use Labrador\Http\Controller\Dto\InjectionHandler\HeaderHandler;
use Labrador\Http\Controller\Dto\InjectionHandler\HeadersHandler;
use Labrador\Http\Controller\Dto\InjectionHandler\MethodHandler;
use Labrador\Http\Controller\Dto\InjectionHandler\QueryParamsHandler;
use Labrador\Http\Controller\Dto\InjectionHandler\RequestHandler;
use Labrador\Http\Controller\Dto\InjectionHandler\RouteParamHandler;
use Labrador\Http\Controller\Dto\InjectionHandler\SessionHandler;
use Labrador\Http\Controller\Dto\InjectionHandler\UrlHandler;
use Labrador\Http\Controller\DtoController;
use Labrador\Http\Controller\HttpController;
use Labrador\Http\Controller\RouteMappingAttribute;
use Labrador\Http\Internal\ReflectionCache;
use Labrador\Http\Middleware\ApplicationMiddleware;
use Labrador\Http\Router\Route;
use Labrador\Http\Router\Router;
use Psr\Log\LoggerInterface;
use ReflectionAttribute;
use ReflectionMethod;
use function Cspray\AnnotatedContainer\autowiredParams;
use function Cspray\AnnotatedContainer\rawParam;

class AutowireObserver extends ServiceWiringObserver {

    public function wireServices(AnnotatedContainer $container, ServiceGatherer $gatherer) : void {
        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);

        $logger->info('Container created, beginning to autowire services.');

        /** @var Application $app */
        $app = $container->get(Application::class);
        /** @var Router $router */
        $router = $container->get(Router::class);
        /** @var DtoInjectionManager $dtoManager */
        $dtoManager = $container->get(DtoInjectionManager::class);

        $dtoHandlers = [
            BodyHandler::class,
            DtoHandler::class,
            HeaderHandler::class,
            HeadersHandler::class,
            MethodHandler::class,
            QueryParamsHandler::class,
            RequestHandler::class,
            RouteParamHandler::class,
            SessionHandler::class,
            UrlHandler::class,
        ];
        foreach ($dtoHandlers as $dtoHandler) {
            $handler = $container->make($dtoHandler);
            assert($handler instanceof DtoInjectionHandler);
            $dtoManager->addHandler($handler);
        }

        foreach ($gatherer->getServicesForType(Controller::class) as $controller) {
            $this->handlePotentialHttpController($container, $controller, $logger, $router);
        }

        foreach ($gatherer->getServicesForType(Middleware::class) as $middleware) {
            $this->handleApplicationMiddleware($logger, $app, $middleware);
        }

        foreach ($gatherer->getServicesWithAttribute(ControllerActions::class) as $dtoController) {
            $this->handleDtoController($container, $dtoController, $logger, $router);
        }
    }

    private function createDtoHandler(
        AnnotatedContainer $container,
        object $dtoController,
        ReflectionMethod $reflectionMethod
    ) : DtoController {
        $description = sprintf('DtoHandler<%s::%s>', $reflectionMethod->getDeclaringClass()->getName(), $reflectionMethod->getName());

        $handler = $container->make(
            DtoController::class,
            autowiredParams(
                rawParam('closure', $reflectionMethod->getClosure($dtoController)),
                rawParam('description', $description),
            )
        );
        assert($handler instanceof DtoController);

        return $handler;
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

    private function handleDtoController(
        AnnotatedContainer $container,
        ServiceFromServiceDefinition $controllerAndDefinition,
        LoggerInterface $logger,
        Router $router
    ) : void {
        $controller = $controllerAndDefinition->getService();
        assert(is_object($controller));
        $reflection = ReflectionCache::reflectionClass($controller);
        foreach ($reflection->getMethods() as $reflectionMethod) {
            $routeMappingAttributes = $reflectionMethod->getAttributes(RouteMappingAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            foreach ($routeMappingAttributes as $routeMappingAttribute) {
                $routeMapping = $routeMappingAttribute->newInstance();
                assert($routeMapping instanceof RouteMappingAttribute);

                $route = $router->addRoute(
                    $routeMapping->getRequestMapping(),
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