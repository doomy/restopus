<?php

namespace Doomy\Restopus\Presenter;

use Doomy\Repository\Model\Entity;
use Doomy\Restopus\Attribute\Service\AttributeProvider;
use Doomy\Restopus\Request\AbstractRequestEntity;
use Doomy\Restopus\Request\Service\InPathParameterProvider;
use Doomy\Restopus\Request\Service\RequestBodyProvider;
use Doomy\Restopus\Request\Service\RequestValidator;
use Doomy\Restopus\Response\AbstractResponseEntity;
use Doomy\Restopus\Response\Service\EntityViewResponseMapper;
use Doomy\Restopus\Security\Exception\ForbiddenException;
use Doomy\Security\Exception\AuthenticationFailedException;
use http\Exception\RuntimeException;
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
    /**
     * @var ReflectionMethod[]
     */
    private array $actionReflectionCache = [];

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

    #[Inject]
    public InPathParameterProvider $inPathParameterProvider;

    #[Inject]
    public AttributeProvider $attributeProvider;

    private Request $request;

    public function run(Request $request): Response
    {
        $this->request = $request;

        try {
            $bodyDecoded = $this->requestValidator->decodeBody($this->httpRequest->getRawBody());

            $methodReflection = $this->getActionMethodReflection();

            $this->requestValidator->validateRequest(
                actionMethodReflection: $methodReflection,
                request: $request,
                requestBody: $bodyDecoded,
                headers: $this->httpRequest->getHeaders(),
                actionAttributes: $this->attributeProvider->getMethodAttributes($methodReflection),
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

    protected function getPathParameter(string $parameterName): string
    {
        $actionMethodReflection = $this->getActionMethodReflection();
        $route = $this->attributeProvider->getRoute($actionMethodReflection);

        return $this->inPathParameterProvider->getPathParameter(
            $parameterName,
            $this->httpRequest->getUrl(),
            $route
        ) ?? throw new RuntimeException('Parameter not found');
    }

    private function getActionMethodReflection(): ReflectionMethod
    {
        $action = $this->request->getParameter(self::PARAM_ACTION);
        if (! is_string($action)) {
            throw new ForbiddenException();
        }

        if (isset($this->actionReflectionCache[$action])) {
            return $this->actionReflectionCache[$action];
        }

        $actionMethodName = 'action' . ucfirst($action);
        return $this->actionReflectionCache[$action] = new ReflectionMethod($this, $actionMethodName);
    }
}