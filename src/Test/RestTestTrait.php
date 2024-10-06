<?php

namespace Doomy\Restopus\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

trait RestTestTrait
{
    private Client $client;

    private function sendPost(string $endpointUrl, array $data): ResponseInterface
    {
        $dataEncoded = json_encode($data);
        if ($dataEncoded === false) {
            throw new \RuntimeException('Failed to encode data to JSON');
        }

        $request = new Request(
            'POST',
            $endpointUrl,
            [
                'Content-Type' => 'application/json',
                'Tests' => '1',
            ],
            $dataEncoded
        );

        try {
            return $this->getClient()->send($request);
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse() !== null) {
                $response = $e->getResponse();
                if ($response->getStatusCode() === 500) {
                    var_dump((string) $response->getBody());
                }
                return $response;
            }

            throw $e;
        }
    }

    private function getClient(): Client
    {
        if (! isset($this->client)) {
            $this->client = new Client();
        }

        return $this->client;
    }
}