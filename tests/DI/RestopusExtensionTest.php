<?php

declare(strict_types=1);

namespace Doomy\Restopus\tests\DI;

use Doomy\Restopus\DI\RestopusExtension;
use Doomy\Restopus\Routing\RestRouterFactory;
use Nette\Application\Routers\RouteList;
use Nette\DI\Compiler;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use PHPUnit\Framework\TestCase;

final class RestopusExtensionTest extends TestCase
{
    private const PRESENTER_NAMESPACE = 'Doomy\Restopus\tests\Support\Presenter';

    private const PRESENTERS_DIR = __DIR__ . '/../Support/Presenter';

    public function testRegistersInternalServicesSoConsumersDoNotHaveTo(): void
    {
        $builder = $this->loadConfiguration();

        $expectedServices = [
            'restopus.attributeProvider',
            'restopus.entityViewResponseMapper',
            'restopus.inPathParameterProvider',
            'restopus.requestBodyProvider',
            'restopus.requestMethodMapper',
            'restopus.requestValidator',
        ];

        foreach ($expectedServices as $serviceName) {
            self::assertTrue($builder->hasDefinition($serviceName), $serviceName . ' is not registered');
        }
    }

    public function testDoesNotRegisterAnAuthenticator(): void
    {
        $builder = $this->loadConfiguration();

        // The authenticator is an application concern. Registering one here would push
        // DummyAuthenticator into every consuming application.
        foreach ($builder->getDefinitions() as $definition) {
            $type = $definition->getType();
            self::assertTrue(
                $type === null || ! str_contains($type, 'Authenticator'),
                'Extension must not register an authenticator, got ' . (string) $type
            );
        }
    }

    public function testRouterFactoryReceivesConfiguredNamespaceAndDirectory(): void
    {
        $definition = $this->loadConfiguration()->getDefinition('restopus.routerFactory');
        self::assertInstanceOf(ServiceDefinition::class, $definition);

        $factory = $definition->getFactory();
        self::assertSame(RestRouterFactory::class, $factory->getEntity());
        self::assertSame([
            'restPresenterNamespace' => self::PRESENTER_NAMESPACE,
            'presentersDir' => self::PRESENTERS_DIR,
        ], $factory->arguments);
    }

    public function testRouterIsRegisteredAsRouteListSoNetteResolvesItByType(): void
    {
        $definition = $this->loadConfiguration()->getDefinition('restopus.router');

        // Nette's RoutingExtension::beforeCompile() only installs its SimpleRouter
        // fallback when no service of the Router type exists, so the type matters.
        self::assertSame(RouteList::class, $definition->getType());
    }

    public function testRouterRegistrationCanBeDisabled(): void
    {
        $builder = $this->loadConfiguration([
            'registerRouter' => false,
        ]);

        self::assertFalse($builder->hasDefinition('restopus.router'));
        self::assertTrue($builder->hasDefinition('restopus.routerFactory'));
    }

    public function testPresenterNamespaceIsRequired(): void
    {
        $this->expectException(ValidationException::class);

        (new Processor())->process((new RestopusExtension())->getConfigSchema(), [
            'presentersDir' => self::PRESENTERS_DIR,
        ]);
    }

    public function testPresentersDirIsRequired(): void
    {
        $this->expectException(ValidationException::class);

        (new Processor())->process((new RestopusExtension())->getConfigSchema(), [
            'presenterNamespace' => self::PRESENTER_NAMESPACE,
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function loadConfiguration(array $config = []): ContainerBuilder
    {
        $extension = new RestopusExtension();
        $extension->setCompiler(new Compiler(), 'restopus');
        $extension->setConfig((new Processor())->process($extension->getConfigSchema(), $config + [
            'presenterNamespace' => self::PRESENTER_NAMESPACE,
            'presentersDir' => self::PRESENTERS_DIR,
        ]));
        $extension->loadConfiguration();

        return $extension->getContainerBuilder();
    }
}
