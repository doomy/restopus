<?php

namespace Doomy\Restopus\Request;

use Doomy\Restopus\Request\Enum\HttpRequestMethod;
use Doomy\Restopus\Security\Exception\ForbiddenException;
use OpenApi\Annotations\Operation;

final readonly class RequestValidator
{
    public function __construct(
        private RequestMethodMapper $requestMethodMapper
    ) {}

    public function checkHttpMethodConsistency(
        \ReflectionMethod $actionMethodReflection,
        HttpRequestMethod $requestMethod
    ): void
    {
        $annotations = $actionMethodReflection->getAttributes();

        foreach ($annotations as $annotation) {
            $instance = $annotation->newInstance();
            if ($instance instanceof Operation) {
                $targetMethod = $this->requestMethodMapper->mapFromString($instance->method);
                if ($requestMethod !== $targetMethod) {
                    throw new ForbiddenException();
                }
            }
        }

    }

}