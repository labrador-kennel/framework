<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Validation\Stub;

use Labrador\Validation\EntityValidator;
use Labrador\Validation\PropertyValidateMap;

final class EntityValidatorStub extends EntityValidator {

    public function __construct(string $class) {
        parent::__construct(PropertyValidateMap::fromAttributedProperties($class));
    }
}
