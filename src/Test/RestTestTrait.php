<?php

namespace Doomy\Restopus\Test;

use Doomy\Restopus\Request\Enum\HttpRequestMethod;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

trait RestTestTrait
{
    private Client $client;

    private function sendPost(string $endpointUrl, array $data, ?string $accessToken = null): ResponseInterface
    {
        return $this->sendRequest($endpointUrl, $data, HttpRequestMethod::POST, $accessToken);
    }

    private function sendGet(string $endpointUrl, ?string $accessToken = null): ResponseInterface
    {
        return $this->sendRequest($endpointUrl, [], HttpRequestMethod::GET, $accessToken);
    }

    private function sendRequest(
        string $endpointUrl,
        array $data,
        HttpRequestMethod $httpRequestMethod,
        ?string $accessToken = null,
        array $customHeaders = []
    ): ResponseInterface
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

        $headers = array_merge($headers, $customHeaders);

        $request = new Request(
            $httpRequestMethod->value,
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