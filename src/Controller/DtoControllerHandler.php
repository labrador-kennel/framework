<?php

namespace Cspray\Labrador\Http\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Amp\Http\Server\Response;
use Closure;
use Cspray\AnnotatedContainer\Autowire\AutowireableInvoker;
use Cspray\AnnotatedContainer\Autowire\AutowireableParameter;
use Cspray\Labrador\Http\Controller\Dto\Body;
use Cspray\Labrador\Http\Controller\Dto\Dto;
use Cspray\Labrador\Http\Controller\Dto\DtoFactory;
use Cspray\Labrador\Http\Controller\Dto\Header;
use Cspray\Labrador\Http\Controller\Dto\Headers;
use Cspray\Labrador\Http\Controller\Dto\Method;
use Cspray\Labrador\Http\Controller\Dto\QueryParams;
use Cspray\Labrador\Http\Controller\Dto\RouteParam;
use Cspray\Labrador\Http\Controller\Dto\Url;
use Cspray\Labrador\Http\ErrorHandlerFactory;
use League\Uri\Components\Query;
use League\Uri\Contracts\QueryInterface;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionParameter;
use function Cspray\AnnotatedContainer\autowiredParams;
use function Cspray\AnnotatedContainer\rawParam;

final class DtoControllerHandler implements Controller {

    private readonly \ReflectionFunction $reflection;
    private readonly DtoFactory $dtoFactory;
    private ?array $reflectionParameters = null;

    public function __construct(
        private readonly Closure $closure,
        private readonly AutowireableInvoker $invoker,
        private readonly ErrorHandlerFactory $errorHandlerFactory
    ) {
        $this->reflection = new \ReflectionFunction($this->closure);
        $this->dtoFactory = new DtoFactory();
    }

    public function handleRequest(Request $request) : Response {
        $params = [];
        foreach ($this->getReflectionParameters() as $reflectionParameter) {
            $param = $this->getParamFromAttribute($reflectionParameter, $request) ?? $this->getParamFromType($reflectionParameter, $request);
            if ($param !== null) {
                $params[] = $param;
            }
        }

        return $this->invoker->invoke($this->closure, autowiredParams(...$params));
    }

    private function getParamFromAttribute(ReflectionParameter $reflectionParameter, Request $request) : ?AutowireableParameter {
        $parameterName = $reflectionParameter->getName();
        $parameterType = $reflectionParameter->getType()->getName();

        foreach ($reflectionParameter->getAttributes() as $parameterAttribute) {
            if ($parameterAttribute->getName() === Headers::class) {
                return rawParam($parameterName, $request->getHeaders());
            } else if ($parameterAttribute->getName() === Method::class) {
                return rawParam($parameterName, $request->getMethod());
            } else if ($parameterAttribute->getName() === Header::class) {
                $attr = $parameterAttribute->newInstance();
                assert($attr instanceof Header);
                if ($parameterType === 'array') {
                    return rawParam($parameterName, $request->getHeaderArray($attr->name));
                } else if ($parameterType === 'string') {
                    return rawParam($parameterName, $request->getHeader($attr->name));
                }
            } else if ($parameterAttribute->getName() === Url::class) {
                return rawParam($parameterName, $request->getUri());
            } else if ($parameterAttribute->getName() === QueryParams::class) {
                if ($parameterType === 'string') {
                    return rawParam($parameterName, $request->getUri()->getQuery());
                } else if (in_array($parameterType, [QueryInterface::class, Query::class])) {
                    return rawParam($parameterName, Query::createFromUri($request->getUri()));
                }
            } else if ($parameterAttribute->getName() === RouteParam::class) {
                $attr = $parameterAttribute->newInstance();
                assert($attr instanceof RouteParam);
                $paramVal = $request->getAttribute($attr->name);
                if ($parameterType === UuidInterface::class) {
                    return rawParam($parameterName, Uuid::fromString($paramVal));
                } else {
                    return rawParam($parameterName, $paramVal);
                }
            } else if ($parameterAttribute->getName() === Body::class) {
                if ($parameterType === 'string') {
                    return rawParam($parameterName, $request->getBody()->buffer());
                } else {
                    return rawParam($parameterName, $request->getBody());
                }
            } else if ($parameterAttribute->getName() === Dto::class) {
                return rawParam($parameterName, $this->dtoFactory->create($parameterType, $request));
            }
        }

        return null;
    }

    private function getParamFromType(ReflectionParameter $reflectionParameter, Request $request) : ?AutowireableParameter {
        $parameterName = $reflectionParameter->getName();
        $parameterType = $reflectionParameter->getType()->getName();

        if ($parameterType === UriInterface::class) {
            return rawParam($parameterName, $request->getUri());
        } else if (in_array($parameterType, [QueryInterface::class, Query::class])) {
            return rawParam($parameterName, Query::createFromUri($request->getUri()));
        } else if ($parameterType === RequestBody::class) {
            return rawParam($parameterName, $request->getBody());
        }

        return null;
    }

    /**
     * @return list<ReflectionParameter>
     */
    private function getReflectionParameters() : array {
        if ($this->reflectionParameters === null) {
            $this->reflectionParameters = $this->reflection->getParameters();
        }

        return $this->reflectionParameters;
    }

    public function toString() : string {
        return 'why?';
    }
}