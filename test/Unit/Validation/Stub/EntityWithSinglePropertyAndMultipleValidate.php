<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Validation\Stub;

use Labrador\Validation\Validate;
use Respect\Validation\Rules\Alpha;
use Respect\Validation\Rules\Equals;

final class EntityWithSinglePropertyAndMultipleValidate {

    #[
        Validate(new Alpha()),
        Validate(new Equals('some value'))
    ]
    private string $foo;

}
