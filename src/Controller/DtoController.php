<?php

namespace Labrador\Http\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Amp\Http\Server\Response;
use Closure;
use Cspray\AnnotatedContainer\Autowire\AutowireableInvoker;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionManager;
use Labrador\Http\Exception\InvalidDtoAttribute;
use Labrador\Http\Exception\InvalidType;
use League\Uri\Components\Query;
use League\Uri\Contracts\QueryInterface;
use Psr\Http\Message\UriInterface;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use function Cspray\AnnotatedContainer\autowiredParams;
use function Cspray\AnnotatedContainer\rawParam;

final class DtoController implements Controller {

    /**
     * @var array<string, Closure>
     */
    private readonly array $paramFactoryMap;

    public function __construct(
        private readonly Closure $closure,
        private readonly AutowireableInvoker $invoker,
        private readonly DtoInjectionManager $dtoInjectionManager,
        private readonly string $description
    ) {
        $paramMap = [];
        foreach ((new ReflectionFunction($this->closure))->getParameters() as $reflectionParameter) {
            $factory = $this->getParamFactoryForDtoInjectionAttribute($reflectionParameter) ?? $this->getParamFactoryFromType($reflectionParameter->getType());
            if ($factory instanceof Closure) {
                $paramMap[$reflectionParameter->getName()] = $factory;
            }
        }
        $this->paramFactoryMap = $paramMap;
    }

    public function handleRequest(Request $request) : Response {
        $params = [];
        foreach ($this->paramFactoryMap as $paramName => $paramFactory) {
            // TODO The $paramFactory closure could return any object type, if the type is a Response then that will be used and additional processing should stop
            $params[] = rawParam($paramName, $paramFactory($request));
        }

        $response = $this->invoker->invoke($this->closure, autowiredParams(...$params));
        assert($response instanceof Response);
        return $response;
    }

    private function getParamFactoryForDtoInjectionAttribute(ReflectionParameter $reflectionParameter) : ?Closure {
        $parameterType = $reflectionParameter->getType();

        $dtoInjectionAttributes = $reflectionParameter->getAttributes(DtoInjectionAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);
        if (count($dtoInjectionAttributes) > 1) {
            throw InvalidDtoAttribute::fromMultipleAttributes($this->description, $reflectionParameter->getName());
        }

        if (count($dtoInjectionAttributes) === 1) {
            $attribute = $dtoInjectionAttributes[0]->newInstance();
            if ($parameterType === null || !$this->dtoInjectionManager->isValidTypeForAttribute($attribute, $parameterType)) {
                throw InvalidType::fromDtoInjectAttributeInvalidTypeHint($attribute, $this->description, $reflectionParameter->getName());
            }
            return fn(Request $request) : mixed => $this->dtoInjectionManager->createDtoObject($request, $attribute, $parameterType);
        }

        return null;
    }

    private function getParamFactoryFromType(?ReflectionType $type) : ?Closure {
        $parameterType = $type instanceof ReflectionNamedType ? $type->getName() : null;
        if ($parameterType === UriInterface::class) {
            return fn(Request $request) => $request->getUri();
        }

        if (in_array($parameterType, [QueryInterface::class, Query::class], true)) {
            return fn(Request $request) => Query::createFromUri($request->getUri());
        }

        if ($parameterType === RequestBody::class) {
            return fn(Request $request) => $request->getBody();
        }

        if ($parameterType === Request::class) {
            return fn(Request $request) => $request;
        }

        return null;
    }

    public function toString() : string {
        assert($this->description !== '');
        return $this->description;
    }
}