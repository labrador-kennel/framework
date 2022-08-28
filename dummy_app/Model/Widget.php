<?php

namespace Cspray\Labrador\HttpDummyApp\Model;

use DateTimeImmutable;
use JsonSerializable;

class Widget implements JsonSerializable {

    public readonly string $name;

    public readonly Author $author;

    public readonly DateTimeImmutable $createdAt;

    public function jsonSerialize() : array {
        return [
            'name' => $this->name,
            'author' => [
                'name' => $this->author->name,
                'email' => $this->author->email,
                'website' => $this->author->website
            ],
            'createdAt' => $this->createdAt->format(DATE_ATOM)
        ];
    }
}