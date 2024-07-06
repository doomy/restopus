<?php

namespace Doomy\Restopus\Request\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class RequestBody
{
    /**
     * @param class-string $bodyEntityClass
     */
    public function __construct(
        private string $bodyEntityClass
    ) {}

    /**
     * @return class-string
     */
    public function getBodyEntityClass(): string
    {
        return $this->bodyEntityClass;
    }
}