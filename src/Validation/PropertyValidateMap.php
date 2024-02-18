<?php declare(strict_types=1);

namespace Labrador\Validation;

use IteratorAggregate;
use Labrador\Util\ReflectionCache;
use Labrador\Validation\Exception\ValidateAttributeNotFound;
use Traversable;

/**
 * @implements IteratorAggregate<non-empty-string, list<Validate>>
 */
class PropertyValidateMap implements \Countable, IteratorAggregate {

    /**
     * @param array<non-empty-string, list<Validate>> $propertyValidateMap
     */
    private function __construct(
        private readonly array $propertyValidateMap
    ) {}

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return self
     */
    public static function fromAttributedProperties(string $class) : self {
        $reflection = ReflectionCache::fromClass($class);
        $map = [];
        foreach ($reflection->getProperties() as $property) {
            $validateAttributes = $property->getAttributes(Validate::class);
            if ($validateAttributes === []) {
                continue;
            }

            $map[$property->getName()] ??= [];

            foreach ($validateAttributes as $validateAttribute) {
                $map[$property->getName()][] = $validateAttribute->newInstance();
            }
        }

        if ($map === []) {
            throw ValidateAttributeNotFound::fromClassHasNoPropertiesAttributed($class);
        }

        return new self($map);
    }

    public function count() : int {
        return count($this->propertyValidateMap);
    }

    public function getIterator() : Traversable {
        yield from $this->propertyValidateMap;
    }
}