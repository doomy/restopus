<?php

namespace Doomy\Restopus\Request\Service;

use Doomy\Restopus\Request\Attribute\AbstractRestMethodAttribute;
use Doomy\Restopus\Request\Attribute\Authenticated;
use Doomy\Restopus\Request\Attribute\HttpMethod;
use Doomy\Restopus\Request\Attribute\RequestBody;
use Doomy\Restopus\Request\Attribute\Route;
use Doomy\Restopus\Request\Enum\HttpRequestMethod;
use Doomy\Restopus\Security\Exception\ForbiddenException;
use Doomy\Security\Authenticator\AuthenticatorInterface;
use Doomy\Security\Exception\AuthenticationFailedException;
use Doomy\Security\Exception\InvalidTokenException;
use Doomy\Security\Exception\TokenExpiredException;
use Doomy\Security\Exception\UserBlockedException;
use Doomy\Security\Exception\UserNotFoundException;
use Nette\Application\Request;
use Nette\Http\Request as HttpRequest;

final readonly class RequestValidator
{
    public function __construct(
        private RequestBodyProvider $requestBodyProvider,
        private RequestMethodMapper $requestMethodMapper,
        private AuthenticatorInterface $authenticator,
        private InPathParameterProvider $inPathParameterProvider,
        private HttpRequest $httpRequest
    )
    {
    }

   /**
    * @param array<string, string> $requestBody
    * @param array<string, int|string> $headers
    * @param AbstractRestMethodAttribute[] $actionAttributes
    * @throws AuthenticationFailedException
    * @throws ForbiddenException
    */
   public function validateRequest(
       \ReflectionMethod $actionMethodReflection,
       Request $request,
       array|null $requestBody,
       array $headers,
       array $actionAttributes
   ): void
    {
        foreach ($actionAttributes as $attribute) {
            if ($attribute instanceof HttpMethod) {
                $this->checkHttpMethodConsistency($attribute->getHttpRequestMethod(), $request);
            } elseif ($attribute instanceof RequestBody) {
                /** performs validation while creating object */
                if (! is_array($requestBody)) {
                    throw new ForbiddenException('Body cannot be empty');
                }
                $this->requestBodyProvider->getBodyEntity($requestBody, $attribute->getBodyEntityClass());
            } elseif ($attribute instanceof Authenticated) {
                $this->authenticateRequest($headers, $attribute);
            } elseif ($attribute instanceof Route) {
                $this->validateRoute($attribute, $this->httpRequest->getUrl());
            }
        }
    }

    /**
     * @return array<string, string>|null
     * @throws ForbiddenException
     */
    public function decodeBody(?string $rawBody): ?array
    {
        if ($rawBody === null || $rawBody === '') {
            return null;
        }
        $bodyDecoded = json_decode($rawBody, true);
        if (! is_array($bodyDecoded)) {
            throw new ForbiddenException('Invalid request body');
        }

        return $bodyDecoded;
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

    /**
     * @throws ForbiddenException
     */
    private function validateRoute(Route $route, string $requestUrl): void
    {
        $parameterNames = $this->inPathParameterProvider->getRouteParameterNames($route);
        if (count($parameterNames) === 0) {
            return;
        }

        $this->validateInPathParameters($parameterNames, $route, $requestUrl);
    }

    /**
     * @param array<string> $parameterNames
     * @throws ForbiddenException
     */
    private function validateInPathParameters(array $parameterNames, Route $route, string $requestUrl): void
    {
        $pathParameters = $this->inPathParameterProvider->extractPathParameters($parameterNames, $requestUrl, $route);
        foreach ($pathParameters as $parameterName => $parameterValue) {
            if ($parameterValue === null) {
                throw new ForbiddenException('Missing path parameter ' . $parameterName);
            }
        }
    }
}