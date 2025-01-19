<?php

namespace Doomy\Restopus\Request\Service;

use Doomy\Restopus\Request\Attribute\Route;

final class InPathParameterProvider
{
    /**
     * @var array<string, string>
     */
    private array $cached = [];

    public function getPathParameter(string $parameterName, string $calledUrl, Route $route): ?string
    {
        if (isset($this->cached[$parameterName])) {
            return $this->cached[$parameterName];
        }

        $routeParts = explode('/', $route->getRoute());
        $calledUrlParts = explode('/', $calledUrl);

        $parameterIndex = array_search('<' . $parameterName . '>', $routeParts);
        if ($parameterIndex === false) {
            return null;
        }

        $parameterIndex += 2; // compensate for absolute url having more parts

        return $calledUrlParts[$parameterIndex];
    }

    /**
     * @param array<string> $parameterNames
     * @return array<string, string|null>
     */
    public function extractPathParameters(array $parameterNames, string $calledUrl, Route $route): array
    {
        $parameters = [];
        foreach ($parameterNames as $parameterName) {
            $parameters[$parameterName] = $this->getPathParameter($parameterName, $calledUrl, $route);
        }
        return $parameters;
    }

    /**
     * @return string[]
     */
    public function getRouteParameterNames(Route $route): array
    {
        $matches = [];
        preg_match_all('/\<([a-zA-Z0-9]+)\>/', $route->getRoute(), $matches);
        return $matches[1];
    }
}