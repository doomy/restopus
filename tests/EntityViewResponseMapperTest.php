<?php

namespace Doomy\Restopus\tests;

use Doomy\Restopus\Response\Service\EntityViewResponseMapper;
use Doomy\Restopus\tests\Support\Response\View\DummyEntityView;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class EntityViewResponseMapperTest extends TestCase
{
    public function testMappedData(): void
    {
        $view = new DummyEntityView();
        $view->name = 'name';
        $view->description = 'description';

        $mapper = new EntityViewResponseMapper();
        Assert::assertSame([
            'name' => 'name',
            'description' => 'description'
        ], $mapper->mapViewRoResponse($view));
    }
}