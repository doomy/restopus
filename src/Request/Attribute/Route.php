<?php

namespace Doomy\Restopus\Request\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Route
{
    public function __construct(
        private string $route
    ) {}

    public function getRoute(): string
    {
        return $this->route;
    }
}