<?php

namespace Doomy\Restopus\Presenter;

use Doomy\Repository\Model\Entity;
use Doomy\Restopus\Request\AbstractRequestEntity;
use Doomy\Restopus\Request\Service\RequestBodyProvider;
use Doomy\Restopus\Request\Service\RequestValidator;
use Doomy\Restopus\Response\AbstractResponseEntity;
use Doomy\Restopus\Response\Service\EntityViewResponseMapper;
use Doomy\Restopus\Security\Exception\ForbiddenException;
use Doomy\Security\Exception\AuthenticationFailedException;
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

    #[Inject]
    public EntityViewResponseMapper $entityViewReponseMapper;

    public function run(Request $request): Response
    {
        try {
            $bodyDecoded = $this->requestValidator->decodeBody($this->httpRequest->getRawBody());

            $action = $request->getParameter(self::PARAM_ACTION);
            if (! is_string($action)) {
                throw new ForbiddenException();
            }
            $methodReflection = $this->getMethodReflection($action);

            $this->requestValidator->validateRequest(
                actionMethodReflection: $methodReflection,
                request: $request,
                requestBody: $bodyDecoded,
                headers: $this->httpRequest->getHeaders()
            );

            $response = $methodReflection->invoke($this, $bodyDecoded ?? []);
        } catch (AuthenticationFailedException $exception) {
            $this->httpResponse->setCode(401);
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ]);
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

    /**
     * @param class-string<AbstractResponseEntity> $viewClass
     * @param Entity[] $entities
     * @return array<array<string, mixed>>
     */
    protected function mapEntitiesToResponse(array $entities, string $viewClass): array {
        return array_map(
            fn (Entity $entity) => $this->mapEntityToResponse($entity, $viewClass),
            $entities
        );
    }

    /**
     * @param class-string<AbstractResponseEntity> $viewClass
     * @return array<string, mixed>
     */
    protected function mapEntityToResponse(Entity $entity, string $viewClass): array
    {
        return $this->entityViewReponseMapper->mapEntityToResponse($entity, $viewClass);
    }

    private function getMethodReflection(string $action): ReflectionMethod
    {
        $actionMethodName = 'action' . ucfirst($action);
        return new ReflectionMethod($this, $actionMethodName);
    }
}