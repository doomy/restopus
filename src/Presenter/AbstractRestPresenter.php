<?php

namespace Doomy\Restopus\Presenter;

use Doomy\Restopus\Request\AbstractRequestEntity;
use Doomy\Restopus\Request\RequestBodyProvider;
use Doomy\Restopus\Request\RequestValidator;
use Doomy\Restopus\Security\Exception\ForbiddenException;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\JsonResponse;
use Nette\DI\Attributes\Inject;
use Nette\Http\IRequest as HttpRequest;
use Nette\Http\IResponse;
use ReflectionMethod;

abstract class AbstractRestPresenter implements IPresenter
{
    final const string PARAM_ACTION = 'action';

    #[Inject]
    public RequestValidator $requestValidator;

    #[Inject]
    public RequestBodyProvider $requestBodyProvider;

    #[Inject]
    public HttpRequest $httpRequest;

    #[Inject]
    public IResponse $httpResponse;

    public function run(Request $request): Response
    {
        $rawBody = $this->httpRequest->getRawBody();
        try {
            if ($rawBody === null) {
                throw new ForbiddenException('Request body is missing');
            }

            $bodyDecoded = json_decode($rawBody, true);
            if (! is_array($bodyDecoded)) {
                throw new ForbiddenException('Invalid request body');
            }

            $action = $request->getParameter(self::PARAM_ACTION);
            if (! is_string($action)) {
                throw new ForbiddenException();
            }
            $methodReflection = $this->getMethodReflection($action);

            $this->requestValidator->validateRequest(
                actionMethodReflection: $methodReflection,
                request: $request,
                requestBody: $bodyDecoded
            );

            $response = $methodReflection->invoke($this, $bodyDecoded);
        } catch (ForbiddenException $exception) {
            $this->httpResponse->setCode(403);
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ]);
        } catch (\InvalidArgumentException $exception) {
            $this->httpResponse->setCode(400);
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            $this->httpResponse->setCode(500);
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ]);
        }
        if (! $response instanceof Response) {
            throw new \RuntimeException('Invalid response type');
        }

        return $response;
    }

    /**
     * @template T of AbstractRequestEntity
     * @param array<string, string> $requestBodyData
     * @param class-string<T> $bodyEntityClass
     * @return T
     */
    protected function getBody(array $requestBodyData, string $bodyEntityClass): AbstractRequestEntity
    {
        /** @var T $bodyEntity */
        $bodyEntity = $this->requestBodyProvider->getBodyEntity($requestBodyData, $bodyEntityClass);
        return $bodyEntity;
    }

    private function getMethodReflection(string $action): ReflectionMethod
    {
        $actionMethodName = 'action' . ucfirst($action);
        return new ReflectionMethod($this, $actionMethodName);
    }
}