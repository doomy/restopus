<?php

namespace Doomy\Restopus\Request;

use Doomy\Restopus\Request\Attribute\HttpMethod;
use Doomy\Restopus\Request\Attribute\RequestBody;
use Doomy\Restopus\Request\Enum\HttpRequestMethod;
use Doomy\Restopus\Security\Exception\ForbiddenException;
use Nette\Application\Request;

final readonly class RequestValidator
{
    public function __construct(
        private readonly RequestBodyProvider $requestBodyProvider,
        private readonly RequestMethodMapper $requestMethodMapper
    )
    {
    }

   /**
    * @throws ForbiddenException
    */
   public function validateRequest(
       \ReflectionMethod $actionMethodReflection,
       Request $request
   ): void
    {
        $annotations = $actionMethodReflection->getAttributes();

        foreach ($annotations as $annotation) {
            $anotationInstance = $annotation->newInstance();
            if ($anotationInstance instanceof HttpMethod) {
                $this->checkHttpMethodConsistency($anotationInstance->getHttpRequestMethod(), $request);
            } elseif ($anotationInstance instanceof RequestBody) {
                /** performs validation while creating object */
                $this->requestBodyProvider->getBodyEntity($request->getPost(), $anotationInstance->getBodyEntityClass());
            }
        }
    }

    private function checkHttpMethodConsistency(
        HttpRequestMethod $targetMethod,
        Request $request
    ): void
    {
        if ($request->getMethod() === null) {
            throw new ForbiddenException();
        }

        $requestMethod = $this->requestMethodMapper->mapFromString($request->getMethod());

        if ($requestMethod !== $targetMethod) {
            throw new ForbiddenException();
        }
    }
}