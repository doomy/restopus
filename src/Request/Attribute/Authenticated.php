<?php

namespace Doomy\Restopus\Request\Attribute;

use Attribute;
use Doomy\Security\Model\User;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Authenticated
{
    /**
     * @template T of User
     * @param class-string<T> $userEntityClass
     */
    public function __construct(
        private string $userEntityClass = User::class
    )
    {
    }

    /**
     * @return class-string<User> $userEntityClass
     */
    public function getUserEntityClass(): string
    {
        return $this->userEntityClass;
    }
}