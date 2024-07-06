<?php

namespace Doomy\Restopus\Presenter;

use Doomy\Restopus\Request\RequestMethodMapper;
use Doomy\Restopus\Request\RequestValidator;
use Doomy\Restopus\Security\Exception\ForbiddenException;
use http\Exception\RuntimeException;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\DI\Attributes\Inject;
use ReflectionMethod;

abstract class AbstractRestPresenter implements IPresenter
{
    final const string PARAM_ACTION = 'action';

    #[Inject]
    public RequestMethodMapper $requestMethodMapper;

    #[Inject]
    public RequestValidator $requestValidator;

    public function run(Request $request): Response
    {
        $action = $request->getParameter(self::PARAM_ACTION);
        if (! is_string($action)) {
            throw new ForbiddenException();
        }
        $methodReflection = $this->getMethodReflection($action);

        $this->checkHttpMethodConsistency($methodReflection, $request);
        $response = $methodReflection->invoke($this, $request);

        if (! $response instanceof Response) {
            throw new RuntimeException('Invalid response type');
        }

        return $response;
    }

    private function checkHttpMethodConsistency(ReflectionMethod $reflectionMethod, Request $request): void
    {
        if ($request->getMethod() === null) {
            throw new ForbiddenException();
        }

        $this->requestValidator->checkHttpMethodConsistency(
            actionMethodReflection: $reflectionMethod,
            requestMethod: $this->requestMethodMapper->mapFromString($request->getMethod())
        );
    }

    private function getMethodReflection(string $action): ReflectionMethod
    {
        $actionMethodName = 'action' . ucfirst($action);
        return new ReflectionMethod($this, $actionMethodName);
    }
}