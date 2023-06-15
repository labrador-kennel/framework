<?php declare(strict_types=1);

namespace Labrador\Validation;


/**
 * @template T of object
 */
abstract class EntityValidator {

    protected function __construct(
        private readonly PropertyValidateMap $propertyValidateMap,
    ) {}

    /**
     * @psalm-param T $entity
     */
    final public function validate(object $entity) : ValidationResult {
        $errors = [];
        foreach ($this->propertyValidateMap as $property => $validates) {
            $value = $this->getPropertyValue($entity, $property);
            /** @var Validate $validate */
            foreach ($validates as $validate) {
                if (!$validate->rule->validate($validate)) {
                    $errors[$property] ??= [];
                    $errors[$property][] = $validate->messageGenerator->getMessage($validate->rule, $entity, $property, $value);
                }
            }
        }

        return new ValidationResult($errors);
    }

    protected function getPropertyValue(object $entity, string $property) : mixed {
        return $entity->{$property};
    }

}
