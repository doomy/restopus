<?php

namespace Doomy\Restopus\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

trait RestTestTrait
{
    private Client $client;

    private function sendPost(string $endpointUrl, array $data, ?string $accessToken = null): ResponseInterface
    {
        $dataEncoded = json_encode($data);
        if ($dataEncoded === false) {
            throw new \RuntimeException('Failed to encode data to JSON');
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Tests' => '1',
        ];
        if ($accessToken !== null) {
            $headers['Authorization'] = "Bearer " . $accessToken;
        }

        $request = new Request(
            'POST',
            $endpointUrl,
            $headers,
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