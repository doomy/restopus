<?php

namespace Doomy\Restopus\tests;

use Doomy\Restopus\Test\RestTestTrait;
use PHPUnit\Framework\TestCase;

final class RestPresenterTest extends TestCase
{
    use RestTestTrait;

    private const ENDPOINT_URL = 'http://restopus-webtests/list';

    public function testList(): void
    {
        $response = $this->sendGet(self::ENDPOINT_URL);
        self::assertSame(200, $response->getStatusCode());
        $this->assertEquals('{"data":[{"name":"name1","description":"description1"},{"name":"name2","description":"description2"}]}', $response->getBody()->getContents());
    }



}