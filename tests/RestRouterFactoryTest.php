<?php

namespace Doomy\Restopus\Tests;

use PHPUnit\Framework\TestCase;

final class RestRouterFactoryTest extends TestCase
{
    public function testCreateRouter(): void
    {
        $routerFactory = new \Doomy\Restopus\Routing\RestRouterFactory(
            restPresenterNamespace: 'Doomy\\Restopus\\tests\\Support\\Presenter',
            presentersDir: __DIR__ . '/Support/Presenter');
        $router = $routerFactory->createRouter();

        $this->assertInstanceOf(\Nette\Application\Routers\RouteList::class, $router);
        $routers = $router->getRouters();
        self::assertCount(2, $routers);
    }

    public function testNotExistingPresenter(): void
    {
        $routerFactory = new \Doomy\Restopus\Routing\RestRouterFactory(
            restPresenterNamespace: 'Doomy\\Restopus\\XXX\\Presenter',
            presentersDir: __DIR__ . '/Support/Presenter');
        $this->expectException(\Doomy\Restopus\Routing\Exception\RouteCreateException::class);
        $routerFactory->createRouter();
    }

}