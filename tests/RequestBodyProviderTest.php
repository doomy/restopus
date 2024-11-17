<?php

namespace Doomy\Restopus\tests;

use _PHPStan_ab84e5579\Nette\Utils\DateTime;
use Doomy\Restopus\Request\RequestBodyProvider;
use Doomy\Restopus\tests\Support\Request\TestRequestBody;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class RequestBodyProviderTest extends TestCase
{
    public function testCorrect(): void
    {
        $dateTime = new DateTime();

        $requestBodyProvider = new RequestBodyProvider();
        $requestBody = $requestBodyProvider->getBodyEntity([
            'id' => 1,
            'dateTime' => new $dateTime
        ], TestRequestBody::class);

        self::assertInstanceOf(TestRequestBody::class, $requestBody);
        self::assertSame(1, $requestBody->id);
        self::assertEqualsWithDelta($dateTime, $requestBody->dateTime, 1);
    }

    public function testCompositeDatetime(): void
    {
        $requestBodyProvider = new RequestBodyProvider();
        $requestBody = $requestBodyProvider->getBodyEntity([
            'id' => 1,
            'dateTime' => [
                'date' => '2021-01-01',
                'timezone' => 'Europe/Prague'
            ]
        ], TestRequestBody::class);

        self::assertInstanceOf(TestRequestBody::class, $requestBody);
        self::assertSame(1, $requestBody->id);
        self::assertInstanceOf(\DateTimeImmutable::class, $requestBody->dateTime);
        self::assertEquals('2021-01-01', $requestBody->dateTime->format('Y-m-d'));
    }

    public function testInvalidCompositeDatetime(): void
    {
        $this->expectException(\DateMalformedStringException::class);
        $requestBodyProvider = new RequestBodyProvider();
        $requestBodyProvider->getBodyEntity([
            'id' => 1,
            'dateTime' => [
                'date' => 'fdsfafd',
                'timezone' => 'Europe/Prague'
            ]
        ], TestRequestBody::class);
    }

    public function testMissingProperty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $requestBodyProvider = new RequestBodyProvider();
        $requestBodyProvider->getBodyEntity([
            'id' => 1,
        ], TestRequestBody::class);
    }

    public function testInvalidArgumentType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $requestBodyProvider = new RequestBodyProvider();
        $requestBodyProvider->getBodyEntity([
            'id' => new \DateTime(),
            'dateTime' => new \DateTime()
        ], TestRequestBody::class);
    }

}