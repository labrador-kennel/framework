<?php

declare(strict_types=1);

/**
 *
 * @license See LICENSE in source root
 */

namespace Labrador\Web\Exception;

use Amp\Http\Server\RequestBody;
use Labrador\Controller\Dto\QueryParams;
use Labrador\Controller\Dto\Url;
use Labrador\Web\Controller\Dto\Body;
use Labrador\Web\Controller\Dto\Dto;
use Labrador\Web\Controller\Dto\DtoInjectionAttribute;
use Labrador\Web\Controller\Dto\Header;
use Labrador\Web\Controller\Dto\Headers;
use Labrador\Web\Controller\Dto\Method;
use Labrador\Web\Controller\Dto\RouteParam;
use Ramsey\Uuid\UuidInterface;

class InvalidType extends Exception {

    public static function fromDispatcherCallbackInvalidReturn() : self {
        $msg = 'A FastRoute\\Dispatcher must be returned from dispatcher callback injected in constructor';
        return new self($msg);
    }

    public static function fromDtoInjectAttributeInvalidTypeHint(DtoInjectionAttribute $attribute, string $classMethod, string $parameterName) : self {
        return match($attribute::class) {
            RouteParam::class => self::fromRouteParamAttributeInvalidTypeHint($classMethod, $parameterName),
            Headers::class => self::fromHeadersAttributeInvalidTypeHint($classMethod, $parameterName),
            Header::class => self::fromHeaderAttributeInvalidTypeHint($classMethod, $parameterName),
            Method::class => self::fromMethodAttributeInvalidTypeHint($classMethod, $parameterName),
            Dto::class => self::fromDtoAttributeInvalidTypeHint($classMethod, $parameterName),
            Body::class => self::fromBodyAttributeInvalidTypeHint($classMethod, $parameterName),
        };
    }

    private static function fromRouteParamAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[RouteParam] Attribute but is not type-hinted as a string or %s.',
                $parameterName,
                $classMethod,
                UuidInterface::class
            )
        );
    }

    private static function fromHeadersAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Headers] Attribute but is not type-hinted as an array.',
                $parameterName,
                $classMethod
            )
        );
    }

    private static function fromMethodAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Method] Attribute but is not type-hinted as a string.',
                $parameterName,
                $classMethod
            )
        );
    }

    private static function fromHeaderAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Header] Attribute but is not type-hinted as an array or string.',
                $parameterName,
                $classMethod
            )
        );
    }

    private static function fromBodyAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Body] Attribute but is not type-hinted as a string or %s.',
                $parameterName,
                $classMethod,
                RequestBody::class
            )
        );
    }

    private static function fromDtoAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Dto] Attribute but is not type-hinted with a class type.',
                $parameterName,
                $classMethod
            )
        );
    }

}
