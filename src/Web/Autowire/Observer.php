<?php

namespace Labrador\Web\Autowire;

use Amp\Http\Server\Middleware;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceFromServiceDefinition;
use Cspray\AnnotatedContainer\Bootstrap\ServiceGatherer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceWiringObserver;
use Labrador\Internal\ReflectionCache;
use Labrador\Logging\LoggerFactory;
use Labrador\Logging\LoggerType;
use Labrador\Web\Application\Application;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Controller\ControllerActions;
use Labrador\Web\Controller\Dto\DtoInjectionHandler;
use Labrador\Web\Controller\Dto\DtoInjectionManager;
use Labrador\Web\Controller\Dto\InjectionHandler\BodyHandler;
use Labrador\Web\Controller\Dto\InjectionHandler\DtoHandler;
use Labrador\Web\Controller\Dto\InjectionHandler\HeaderHandler;
use Labrador\Web\Controller\Dto\InjectionHandler\HeadersHandler;
use Labrador\Web\Controller\Dto\InjectionHandler\MethodHandler;
use Labrador\Web\Controller\Dto\InjectionHandler\QueryParamsHandler;
use Labrador\Web\Controller\Dto\InjectionHandler\RequestHandler;
use Labrador\Web\Controller\Dto\InjectionHandler\RouteParamHandler;
use Labrador\Web\Controller\Dto\InjectionHandler\SessionHandler;
use Labrador\Web\Controller\Dto\InjectionHandler\UrlHandler;
use Labrador\Web\Controller\DtoController;
use Labrador\Web\Controller\HttpController;
use Labrador\Web\Controller\RouteMappingAttribute;
use Labrador\Web\Middleware\ApplicationMiddleware;
use Labrador\Web\Router\Route;
use Labrador\Web\Router\Router;
use Psr\Log\LoggerInterface;
use ReflectionAttribute;
use ReflectionMethod;
use function Cspray\AnnotatedContainer\autowiredParams;
use function Cspray\AnnotatedContainer\rawParam;

class Observer extends ServiceWiringObserver {

    public function wireServices(AnnotatedContainer $container, ServiceGatherer $gatherer) : void {
        /** @var LoggerFactory $loggerFactory */
        $loggerFactory = $container->get(LoggerFactory::class);
        $logger = $loggerFactory->createLogger(LoggerType::Application);

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