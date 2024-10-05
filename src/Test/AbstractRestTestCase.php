<?php

namespace Doomy\Restopus\Test;

use Doomy\Testing\AbstractDbAwareTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractRestTestCase extends AbstractDbAwareTestCase
{
    protected const string ENDPOINT_URL = '';

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client();
    }

    /**
     * @param array<string, string> $data
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendPost(array $data): ResponseInterface
    {
        $dataEncoded = json_encode($data);
        if ($dataEncoded === false) {
            throw new \RuntimeException('Failed to encode data to JSON');
        }

        $request = new Request(
            'POST',
            static::ENDPOINT_URL,
            [
                'Content-Type' => 'application/json',
                'Tests' => '1'
            ],
            $dataEncoded
        );

        try {
            return $this->client->send($request);
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse() !== null) {
                $response = $e->getResponse();
                if ($response->getStatusCode() === 500) {
                    var_dump((string)$response->getBody());
                }
                return $response;
            }

            throw $e;
        }
    }
}