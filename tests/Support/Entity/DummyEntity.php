<?php

namespace Doomy\Restopus\tests\Support\Entity;

use Doomy\Repository\Model\Entity;

final class DummyEntity extends Entity
{
    public function __construct(
        private string $name,
        private string $description,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

}