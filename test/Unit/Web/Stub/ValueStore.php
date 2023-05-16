<?php

namespace Labrador\Test\Unit\Web\Stub;

class ValueStore {

    private array $values = [];

    public function add(int $value) : void {
        $this->values[] = $value;
    }

    /**
     * @return list<int>
     */
    public function getValues() : array {
        return $this->values;
    }
}