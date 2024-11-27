<?php

namespace Doomy\Restopus\Request;

use Doomy\Restopus\Request\Attribute\Authenticated;
use Doomy\Restopus\Request\Attribute\HttpMethod;
use Doomy\Restopus\Request\Attribute\RequestBody;
use Doomy\Restopus\Request\Enum\HttpRequestMethod;
use Doomy\Restopus\Security\Exception\ForbiddenException;
use Doomy\Security\Authenticator\AuthenticatorInterface;
use Doomy\Security\Exception\AuthenticationFailedException;
use Doomy\Security\Exception\InvalidTokenException;
use Doomy\Security\Exception\TokenExpiredException;
use Doomy\Security\Exception\UserBlockedException;
use Doomy\Security\Exception\UserNotFoundException;
use Nette\Application\Request;

final readonly class RequestValidator
{
    public function __construct(
        private readonly RequestBodyProvider $requestBodyProvider,
        private readonly RequestMethodMapper $requestMethodMapper,
        private readonly AuthenticatorInterface $authenticator
    )
    {
    }

   /**
    * @throws ForbiddenException
    * @throws AuthenticationFailedException
    * @param array<string, string> $requestBody
    * @param array<string, int|string> $headers
    */
   public function validateRequest(
       \ReflectionMethod $actionMethodReflection,
       Request $request,
       array $requestBody,
       array $headers
   ): void
    {
        $annotations = $actionMethodReflection->getAttributes();

        foreach ($annotations as $annotation) {
            $anotationInstance = $annotation->newInstance();
            if ($anotationInstance instanceof HttpMethod) {
                $this->checkHttpMethodConsistency($anotationInstance->getHttpRequestMethod(), $request);
            } elseif ($anotationInstance instanceof RequestBody) {
                /** performs validation while creating object */
                $this->requestBodyProvider->getBodyEntity($requestBody, $anotationInstance->getBodyEntityClass());
            } elseif ($anotationInstance instanceof Authenticated) {
                $this->authenticateRequest($headers, $anotationInstance);
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

    /**
     * @param array<string, int|string> $headers
     * @throws ForbiddenException
     * @throws AuthenticationFailedException
     */
    private function authenticateRequest(array $headers, Authenticated $attribute): void
    {
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if ($authHeader === null || ! str_starts_with((string) $authHeader, 'Bearer ')) {
            throw new ForbiddenException('Missing or invalid Authorization header');
        }
        assert(is_string($authHeader));
        $accessToken = substr($authHeader, 7);
        try {
            $this->authenticator->authenticate($accessToken, $attribute->getUserEntityClass());
        } catch (TokenExpiredException|InvalidTokenException $exception) {
            throw new AuthenticationFailedException();
        }
        catch (UserNotFoundException|UserBlockedException $exception) {
            throw new ForbiddenException();
        }
    }
}