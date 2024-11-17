<?php

namespace Doomy\Restopus\tests;

use Doomy\Restopus\Response\Service\EntityViewReponseMapper;
use Doomy\Restopus\tests\Support\Presenter\TestRestPresenter;
use Nette\Application\Responses\JsonResponse;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class AbstractRestPresenterTest extends TestCase
{
    public function testResponseEntities(): void
    {
        $responseMapper = new EntityViewReponseMapper();
        $presenter = new TestRestPresenter();
        $presenter->entityViewReponseMapper = $responseMapper;
        $response = $presenter->list();
        self::assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getPayload();
        Assert::assertSame([
            'data' => [
                ['name' => 'name1', 'description' => 'description1'],
                ['name' => 'name2', 'description' => 'description2'],
            ]
        ], $data);
    }

}