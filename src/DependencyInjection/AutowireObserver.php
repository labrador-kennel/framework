<?php

namespace Cspray\Labrador\Http\DependencyInjection;

use Amp\Http\Server\Middleware;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Observer;
use Cspray\AnnotatedContainer\ContainerDefinition;
use Cspray\AnnotatedContainer\Definition\ServiceDefinition;
use Cspray\Labrador\Http\Application;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Controller\HttpController;
use Cspray\Labrador\Http\Middleware\ApplicationMiddleware;
use Cspray\Labrador\Http\Router\RequestMapping;
use Cspray\Labrador\Http\Router\Router;
use Psr\Log\LoggerInterface;
use ReflectionClass;

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

            if (is_a($serviceDefinition->getType()->getName(), Controller::class, true)) {
                $this->handleController($container, $logger, $router, $serviceDefinition);
            } else if (is_a($serviceDefinition->getType()->getName(), Middleware::class, true)) {
                $this->handleMiddleware($container, $logger, $app, $serviceDefinition);
            }
        }
    }

    private function handleController(
        AnnotatedContainer $container,
        LoggerInterface $logger,
        Router $router,
        ServiceDefinition $serviceDefinition
    ) : void {
        $reflection = new ReflectionClass($serviceDefinition->getType()->getName());
        $attributes = $reflection->getAttributes(HttpController::class);

        if ($attributes === []) {
            return;
        }

        /** @var HttpController $httpController */
        $httpController = $attributes[0]->newInstance();

        $route = $router->addRoute(
            RequestMapping::fromMethodAndPath($httpController->getMethod(), $httpController->getRoutePattern()),
            $container->get($serviceDefinition->getType()->getName())
        );

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
        $reflection = new ReflectionClass($serviceDefinition->getType()->getName());
        $attributes = $reflection->getAttributes(ApplicationMiddleware::class);

        if ($attributes === []) {
            return;
        }

        /** @var ApplicationMiddleware $appMiddleware */
        $appMiddleware = $attributes[0]->newInstance();

        $application->addMiddleware(
            $container->get($serviceDefinition->getType()->getName()),
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
}