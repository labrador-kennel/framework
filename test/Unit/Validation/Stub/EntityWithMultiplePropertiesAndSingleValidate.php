<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Validation\Stub;

use Labrador\Validation\Validate;
use Respect\Validation\Rules\Alnum;
use Respect\Validation\Rules\Alpha;
use Respect\Validation\Rules\NotBlank;

class EntityWithMultiplePropertiesAndSingleValidate {

    private string $foo;

    #[Validate(new NotBlank())]
    private string $bar;

    #[Validate(new Alnum())]
    private string $baz;

}
