<?php

declare(strict_types=1);

namespace Doomy\Restopus\DI;

use Doomy\Restopus\Attribute\Service\AttributeProvider;
use Doomy\Restopus\Request\Service\InPathParameterProvider;
use Doomy\Restopus\Request\Service\RequestBodyProvider;
use Doomy\Restopus\Request\Service\RequestMethodMapper;
use Doomy\Restopus\Request\Service\RequestValidator;
use Doomy\Restopus\Response\Service\EntityViewResponseMapper;
use Doomy\Restopus\Routing\RestRouterFactory;
use Nette\Application\Routers\RouteList;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Reference;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

/**
 * Registers Restopus' own services, so that a consuming application does not have to
 * list internal wiring in its config.neon.
 *
 * @property object{
 *     presenterNamespace: string,
 *     presentersDir: string,
 *     registerRouter: bool,
 * } $config
 */
final class RestopusExtension extends CompilerExtension
{
    /**
     * Internal wiring: no constructor arguments and nothing application-specific to
     * configure. A consuming application has no reason to know these exist.
     *
     * @var list<class-string>
     */
    private const INTERNAL_SERVICES = [
        AttributeProvider::class,
        EntityViewResponseMapper::class,
        InPathParameterProvider::class,
        RequestBodyProvider::class,
        RequestMethodMapper::class,
        RequestValidator::class,
    ];

    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'presenterNamespace' => Expect::string()->required(),
            'presentersDir' => Expect::string()->required(),
            'registerRouter' => Expect::bool(true),
        ]);
    }

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        foreach (self::INTERNAL_SERVICES as $class) {
            $builder->addDefinition($this->prefix(lcfirst(self::getShortClassName($class))))
                ->setFactory($class);
        }

        $builder->addDefinition($this->prefix('routerFactory'))
            ->setFactory(RestRouterFactory::class, [
                'restPresenterNamespace' => $this->config->presenterNamespace,
                'presentersDir' => $this->config->presentersDir,
            ]);

        if (! $this->config->registerRouter) {
            return;
        }

        $builder->addDefinition($this->prefix('router'))
            ->setType(RouteList::class)
            ->setFactory([new Reference($this->prefix('routerFactory')), 'createRouter']);
    }

    /**
     * @param class-string $class
     */
    private static function getShortClassName(string $class): string
    {
        $separatorPosition = strrpos($class, '\\');

        return $separatorPosition === false ? $class : substr($class, $separatorPosition + 1);
    }
}
