<?php declare(strict_types=1);

namespace Labrador\Web\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;
use Labrador\Web\Controller\Dto\DtoInjectionAttribute;
use Labrador\Web\Controller\Dto\DtoInjectionHandler;
use ReflectionType;

final class SessionHandler implements DtoInjectionHandler {

    public function canCreateDtoValue(?DtoInjectionAttribute $attribute, ReflectionType $type) : bool {
        $actualType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        return $attribute === null && $actualType === Session::class;
    }

    public function createDtoValue(Request $request, ?DtoInjectionAttribute $attribute, ReflectionType $type) : mixed {
        return $request->getAttribute(Session::class);
    }
}