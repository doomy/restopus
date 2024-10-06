<?php

namespace Doomy\Restopus\Request;

use Doomy\Restopus\Request\Attribute\Route;
use Nette\Application\Routers\RouteList;

final readonly class RouteListMapper
{
    /**
     * @param class-string $controller
     */
    public function addRoutesFromController(RouteList $router, string $controller): void
    {
        $reflectionClass = new \ReflectionClass($controller);
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(Route::class);
            foreach ($attributes as $attribute) {
                $action = $this->getActionFromMethodName($method->getName());
                $basePresenterName = $this->getBasePresenterName($controller);
                $route = $attribute->newInstance();
                $router->addRoute($route->getRoute(), $basePresenterName . ':' . $action);
            }
        }
    }

    private function getActionFromMethodName(string $methodName): string
    {
        return lcfirst(substr($methodName, 6));
    }

    private function getBasePresenterName(string $controller): string
    {
        return substr($controller, strrpos($controller, '\\') + 1, -9);
    }
}