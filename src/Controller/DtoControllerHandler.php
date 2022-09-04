<?php

namespace Labrador\Http\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Amp\Http\Server\Response;
use Closure;
use Cspray\AnnotatedContainer\Autowire\AutowireableInvoker;
use Labrador\Http\Controller\Dto\Body;
use Labrador\Http\Controller\Dto\Dto;
use Labrador\Http\Controller\Dto\DtoFactory;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\Header;
use Labrador\Http\Controller\Dto\Headers;
use Labrador\Http\Controller\Dto\Method;
use Labrador\Http\Controller\Dto\QueryParams;
use Labrador\Http\Controller\Dto\RouteParam;
use Labrador\Http\Controller\Dto\Url;
use Labrador\Http\Exception\InvalidDtoAttribute;
use Labrador\Http\Exception\InvalidType;
use League\Uri\Components\Query;
use League\Uri\Contracts\QueryInterface;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use function Cspray\AnnotatedContainer\autowiredParams;
use function Cspray\AnnotatedContainer\rawParam;

final class DtoControllerHandler implements Controller {

    /**
     * @var array<string, Closure>
     */
    private readonly array $paramFactoryMap;

    public function __construct(
        private readonly Closure $closure,
        private readonly AutowireableInvoker $invoker,
        private readonly DtoFactory $dtoFactory,
        private readonly string $description
    ) {
        $paramMap = [];
        foreach ((new ReflectionFunction($this->closure))->getParameters() as $reflectionParameter) {
            $factory = $this->getParamFactoryForDtoInjectionAttribute($reflectionParameter);
            if ($factory instanceof Closure) {
                $paramMap[$reflectionParameter->getName()] = $factory;
            }
        }
        $this->paramFactoryMap = $paramMap;
    }

    public function handleRequest(Request $request) : Response {
        $params = [];
        foreach ($this->paramFactoryMap as $paramName => $paramFactory) {
            $params[] = rawParam($paramName, $paramFactory($request));
        }

        $response = $this->invoker->invoke($this->closure, autowiredParams(...$params));
        assert($response instanceof Response);
        return $response;
    }

    private function getParamFactoryForDtoInjectionAttribute(ReflectionParameter $reflectionParameter) : ?Closure {
        $parameterName = $reflectionParameter->getName();
        $parameterType = $reflectionParameter->getType();
        if ($parameterType instanceof ReflectionNamedType) {
            $parameterType = $parameterType->getName();
        } else {
            $parameterType = null;
        }

        $dtoInjectionAttributes = $reflectionParameter->getAttributes(DtoInjectionAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);
        if (count($dtoInjectionAttributes) > 1) {
            throw InvalidDtoAttribute::fromMultipleAttributes($this->description, $reflectionParameter->getName());
        } else if (count($dtoInjectionAttributes) === 1) {
            $parameterAttribute = $dtoInjectionAttributes[0];
            switch ($parameterAttribute->getName()) {
                case Headers::class:
                    if ($parameterType !== 'array') {
                        throw InvalidType::fromHeadersAttributeInvalidTypeHint($this->description, $parameterName);
                    }
                    return fn(Request $request) => $request->getHeaders();
                case Header::class:
                    $attr = $parameterAttribute->newInstance();
                    assert($attr instanceof Header);
                    if ($parameterType === 'array') {
                        return fn(Request $request) => $request->getHeaderArray($attr->name);
                    } else if ($parameterType === 'string') {
                        return fn(Request $request) => $request->getHeader($attr->name);
                    } else {
                        throw InvalidType::fromHeaderAttributeInvalidTypeHint($this->description, $parameterName);
                    }
                case Method::class:
                    if ($parameterType !== 'string') {
                        throw InvalidType::fromMethodAttributeInvalidTypeHint($this->description, $parameterName);
                    }
                    return fn(Request $request) => $request->getMethod();
                case Url::class:
                    if ($parameterType !== UriInterface::class) {
                        throw InvalidType::fromUrlAttributeInvalidTypeHint($this->description, $parameterName);
                    }
                    return fn(Request $request) => $request->getUri();
                case QueryParams::class:
                    if ($parameterType === 'string') {
                        return fn(Request $request) => $request->getUri()->getQuery();
                    } else if (in_array($parameterType, [QueryInterface::class, Query::class])) {
                        return fn(Request $request) => Query::createFromUri($request->getUri());
                    } else {
                        throw InvalidType::fromQueryParamsAttributeInvalidTypeHint($this->description, $parameterName);
                    }
                case RouteParam::class:
                    $attr = $parameterAttribute->newInstance();
                    assert($attr instanceof RouteParam);
                    if ($parameterType === UuidInterface::class) {
                        return fn(Request $request) => Uuid::fromString((string) $request->getAttribute($attr->name));
                    } else if ($parameterType === 'string') {
                        return fn(Request $request) : mixed => $request->getAttribute($attr->name);
                    } else {
                        throw InvalidType::fromRouteParamAttributeInvalidTypeHint(
                            $this->description,
                            $reflectionParameter->getName()
                        );
                    }
                case Body::class:
                    if ($parameterType === 'string') {
                        return fn(Request $request) => $request->getBody()->buffer();
                    } else if ($parameterType === RequestBody::class) {
                        return fn(Request $request) => $request->getBody();
                    } else {
                        throw InvalidType::fromBodyAttributeInvalidTypeHint($this->description, $parameterName);
                    }
                case Dto::class:
                    if ($parameterType === null || !class_exists($parameterType)) {
                        throw InvalidType::fromDtoAttributeInvalidTypeHint($this->description, $parameterName);
                    }
                    return fn(Request $request) => $this->dtoFactory->create($parameterType, $request);
            }
        }

        return $parameterType === null ? null : $this->getParamFactoryFromType($parameterType);
    }

    private function getParamFactoryFromType(string $parameterType) : ?Closure {
        if ($parameterType === UriInterface::class) {
            return fn(Request $request) => $request->getUri();
        } else if (in_array($parameterType, [QueryInterface::class, Query::class])) {
            return fn(Request $request) => Query::createFromUri($request->getUri());
        } else if ($parameterType === RequestBody::class) {
            return fn(Request $request) => $request->getBody();
        } else if ($parameterType === Request::class) {
            return fn(Request $request) => $request;
        }

        return null;
    }

    public function toString() : string {
        return $this->description;
    }
}