<?php

declare(strict_types=1);

/**
 *
 * @license See LICENSE in source root
 */

namespace Labrador\Http\Exception;

use Amp\Http\Server\RequestBody;
use League\Uri\Components\Query;
use League\Uri\Contracts\QueryInterface;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\UuidInterface;

class InvalidType extends Exception {

    public static function fromDispatcherCallbackInvalidReturn() : self {
        $msg = 'A FastRoute\\Dispatcher must be returned from dispatcher callback injected in constructor';
        return new self($msg);
    }

    public static function fromRouteParamAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[RouteParam] Attribute but is not type-hinted as a string or %s.',
                $parameterName,
                $classMethod,
                UuidInterface::class
            )
        );
    }

    public static function fromHeadersAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Headers] Attribute but is not type-hinted as an array.',
                $parameterName,
                $classMethod
            )
        );
    }

    public static function fromMethodAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Method] Attribute but is not type-hinted as a string.',
                $parameterName,
                $classMethod
            )
        );
    }

    public static function fromHeaderAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Header] Attribute but is not type-hinted as an array or string.',
                $parameterName,
                $classMethod
            )
        );
    }

    public static function fromUrlAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Url] Attribute but is not type-hinted as a %s.',
                $parameterName,
                $classMethod,
                UriInterface::class
            )
        );
    }

    public static function fromQueryParamsAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[QueryParams] Attribute but is not type-hinted as a string, %s, or %s.',
                $parameterName,
                $classMethod,
                QueryInterface::class,
                Query::class
            )
        );
    }

    public static function fromBodyAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Body] Attribute but is not type-hinted as a string or %s.',
                $parameterName,
                $classMethod,
                RequestBody::class
            )
        );
    }

    public static function fromDtoAttributeInvalidTypeHint(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s is marked with a #[Dto] Attribute but is not type-hinted with a class type.',
                $parameterName,
                $classMethod
            )
        );
    }

}
