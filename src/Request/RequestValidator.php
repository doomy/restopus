<?php

namespace Doomy\Restopus\Request;

use Doomy\Restopus\Request\Enum\HttpRequestMethod;
use Doomy\Restopus\Security\Exception\ForbiddenException;
use Doomy\Restopus\Request\Attribute\HttpMethod;

final readonly class RequestValidator
{
   public function checkHttpMethodConsistency(
        \ReflectionMethod $actionMethodReflection,
        HttpRequestMethod $requestMethod
    ): void
    {
        $annotations = $actionMethodReflection->getAttributes();

        foreach ($annotations as $annotation) {
            $instance = $annotation->newInstance();
            if ($instance instanceof HttpMethod) {
                $targetMethod = $instance->getHttpRequestMethod();
                if ($requestMethod !== $targetMethod) {
                    throw new ForbiddenException();
                }
            }
        }

    }

}