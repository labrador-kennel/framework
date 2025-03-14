<?php declare(strict_types=1);

namespace Labrador\Validation;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<non-empty-string, non-empty-list<non-empty-string>>
 */
final class ValidationResult implements Countable, IteratorAggregate {

    /**
     * @param array<non-empty-string, non-empty-list<non-empty-string>> $propertyMessages
     */
    public function __construct(
        private readonly array $propertyMessages
    ) {
    }

    public function isValid() : bool {
        return count($this) === 0;
    }

    /**
     * @param non-empty-string $property
     * @return list<non-empty-string>
     */
    public function getMessages(string $property) : array {
        return $this->propertyMessages[$property] ?? [];
    }

    public function getIterator() : Traversable {
        yield from $this->propertyMessages;
    }

    public function count() : int {
        return count($this->propertyMessages);
    }
}
