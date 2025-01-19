<?php

declare(strict_types=1);

namespace Doomy\Restopus\Routing;

use Doomy\Restopus\Presenter\AbstractRestPresenter;
use Doomy\Restopus\Request\Service\RouteListMapper;
use Doomy\Restopus\Routing\Exception\RouteCreateException;
use Nette\Application\Routers\RouteList;

final readonly class RestRouterFactory
{
    public function __construct(
        private string $restPresenterNamespace,
        private string $presentersDir
    ) {}

    public function createRouter(): RouteList
    {
        $router = new RouteList();
        $controllers = $this->getAllPresenters();

        $routeListMapper = new RouteListMapper();

        foreach ($controllers as $controller) {
            $routeListMapper->addRoutesFromController($router, $controller);
        }

        return $router;
    }

    /**
     * @return class-string[]
     */
    private function getAllPresenters(): array
    {
        $presenterFiles = glob($this->presentersDir . '/*.php');
        $presenters = [];

        if ($presenterFiles === false) {
            return [];
        }

        foreach ($presenterFiles as $file) {
            $className = self::getClassNameFromFile($file);
            if ($className !== null) {
                $presenters[] = $className;
            }
        }

        return $presenters;
    }

    /**
     * @return class-string|null
     */
    private function getClassNameFromFile(string $file): ?string
    {
        /** @var class-string $className */
        $className = $this->restPresenterNamespace . '\\' . basename($file, '.php');

        try {
            $reflectionClass = new \ReflectionClass($className);
            if ($reflectionClass->isSubclassOf(AbstractRestPresenter::class) && ! $reflectionClass->isAbstract()) {
                return $className;
            }
        } catch (\ReflectionException $e) {
            throw new RouteCreateException($e->getMessage());
        }

        return null;
    }
}
