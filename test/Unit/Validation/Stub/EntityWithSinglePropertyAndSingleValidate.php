<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Validation\Stub;

use Labrador\Validation\Validate;
use Respect\Validation\Rules\Alpha;

final class EntityWithSinglePropertyAndSingleValidate {

    public function __construct(
        #[Validate(new Alpha())]
        public readonly string $foo
    ) {
    }
}
