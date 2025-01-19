<?php

namespace Doomy\Restopus\tests;

use Doomy\Restopus\Test\RestTestTrait;
use Doomy\Testing\Assert\HttpResponseAssert;
use Doomy\Testing\Assert\JsonResponseAssert;
use PHPUnit\Framework\TestCase;

final class RestPresenterTest extends TestCase
{
    use RestTestTrait;

    private const API_URL = 'http://restopus-webtests';

    public function testList(): void
    {
        $response = $this->sendGet(self::API_URL . '/list');
        HttpResponseAssert::assertResponseCode($response, 200);
        $this->assertEquals('{"data":[{"name":"name1","description":"description1"},{"name":"name2","description":"description2"}]}', $response->getBody()->getContents());
    }

    public function testAuthenticatedInvalid(): void
    {
        $response = $this->sendGet(endpointUrl: self::API_URL . '/authenticated', accessToken: 'invalid');
        HttpResponseAssert::assertResponseCode($response, 401);
    }

    public function testAuthenticatedExpired(): void
    {
        $response = $this->sendGet(endpointUrl: self::API_URL . '/authenticated', accessToken: 'expired');
        self::assertSame(401, $response->getStatusCode());
    }

    public function testAuthenticatedValid(): void
    {
        $response = $this->sendGet(endpointUrl: self::API_URL . '/authenticated', accessToken: 'valid');
        self::assertSame(200, $response->getStatusCode());
    }
    public function testInPathParameter(): void
    {
        $response = $this->sendGet(self::API_URL . '/in-path/122');
        JsonResponseAssert::assertJsonOkResponseWithData(['id' => 122], $response->getBody()->getContents());
    }
}