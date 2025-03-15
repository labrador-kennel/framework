<?php declare(strict_types=1);

namespace Labrador\Validation;

/**
 * @template T of object
 */
abstract class EntityValidator {

    protected function __construct(
        private readonly PropertyValidateMap $propertyValidateMap,
    ) {
    }

    /**
     * @psalm-param T $entity
     */
    final public function validate(object $entity) : ValidationResult {
        $errors = [];
        foreach ($this->propertyValidateMap as $property => $validates) {
            /** @var mixed $value */
            $value = $this->getPropertyValue($entity, $property);
            /** @var Validate $validate */
            foreach ($validates as $validate) {
                if (!$validate->rule->validate($value)) {
                    $errors[$property] ??= [];

                    $message = $validate->messageGenerator->getMessage($validate->rule, $entity, $property, $value);

                    assert($message !== '');

                    $errors[$property][] = $message;
                }
            }
        }

        return new ValidationResult($errors);
    }

    protected function getPropertyValue(object $entity, string $property) : mixed {
        return $entity->{$property};
    }
}
