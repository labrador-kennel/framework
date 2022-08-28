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
use Cspray\Labrador\Http\Controller\Dto\Get;
use Cspray\Labrador\Http\Controller\Dto\Post;
use Cspray\Labrador\Http\Controller\Dto\Put;
use Cspray\Labrador\Http\Controller\DtoControllerHandler;
use Cspray\Labrador\Http\Controller\HttpController;
use Cspray\Labrador\Http\ErrorHandlerFactory;
use Cspray\Labrador\Http\HttpMethod;
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
        /** @var ErrorHandlerFactory $errorHandlerFactory */
        $errorHandlerFactory = $container->get(ErrorHandlerFactory::class);
        foreach ($containerDefinition->getServiceDefinitions() as $serviceDefinition) {
            if ($serviceDefinition->isAbstract()) {
                continue;
            }

            if (is_a($serviceDefinition->getType()->getName(), Controller::class, true)) {
                $this->handleController($container, $logger, $router, $serviceDefinition, $errorHandlerFactory);
            } else if (is_a($serviceDefinition->getType()->getName(), Middleware::class, true)) {
                $this->handleMiddleware($container, $logger, $app, $serviceDefinition);
            } else {
                $reflection = new ReflectionClass($serviceDefinition->getType()->getName());
                $attr = $reflection->getAttributes(DtoController::class);
                if ($attr !== []) {
                    $dtoController = $container->get($serviceDefinition->getType()->getName());
                    foreach ($reflection->getMethods() as $reflectionMethod) {
                        $get = $reflectionMethod->getAttributes(Get::class);
                        if ($get !== []) {
                            $router->addRoute(
                                RequestMapping::fromMethodAndPath(HttpMethod::Get, $get[0]->newInstance()->path),
                                new DtoControllerHandler($reflectionMethod->getClosure($dtoController), $container, $errorHandlerFactory),
                            );
                        }

                        $post = $reflectionMethod->getAttributes(Post::class);
                        if ($post !== []) {
                            $router->addRoute(
                                RequestMapping::fromMethodAndPath(HttpMethod::Post, $post[0]->newInstance()->path),
                                new DtoControllerHandler($reflectionMethod->getClosure($dtoController), $container, $errorHandlerFactory)
                            );
                        }

                        $put = $reflectionMethod->getAttributes(Put::class);
                        if ($put !== []) {
                            $router->addRoute(
                                RequestMapping::fromMethodAndPath(HttpMethod::Put, $put[0]->newInstance()->path),
                                new DtoControllerHandler($reflectionMethod->getClosure($dtoController), $container, $errorHandlerFactory)
                            );
                        }
                    }
                }
            }
        }
    }

    private function handleController(
        AnnotatedContainer $container,
        LoggerInterface $logger,
        Router $router,
        ServiceDefinition $serviceDefinition,
        ErrorHandlerFactory $errorHandlerFactory
    ) : void {
        $reflection = new ReflectionClass($serviceDefinition->getType()->getName());
        $httpAttributes = $reflection->getAttributes(HttpController::class);

        if ($httpAttributes !== []) {
            $this->handleHttpController(
                $container, $router, $logger, $httpAttributes[0]->newInstance(), $serviceDefinition
            );
            return;
        }

        $dtoAttributes = $reflection->getAttributes(DtoController::class);
        if ($dtoAttributes !== []) {
            $dtoReflection = new \ReflectionObject($dtoService = $container->get($serviceDefinition->getType()->getName()));
            foreach ($dtoReflection->getMethods() as $reflectionMethod) {
                $getAttributes = $reflectionMethod->getAttributes(Get::class);
                if ($getAttributes !== []) {
                    /** @var Get $get */
                    $get = $getAttributes[0]->newInstance();
                    $router->addRoute(
                        RequestMapping::fromMethodAndPath(HttpMethod::Get, $get->path),
                        new DtoControllerHandler($reflectionMethod->getClosure($dtoService), $container, $errorHandlerFactory)
                    );
                }
            }
        }

    }

    private function handleHttpController(
        AnnotatedContainer $container,
        Router $router,
        LoggerInterface $logger,
        HttpController $httpController,
        ServiceDefinition $serviceDefinition
    ) : void {
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