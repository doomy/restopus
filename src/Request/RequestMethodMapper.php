<?php

namespace Doomy\Restopus\Request;

use Doomy\Restopus\Request\Enum\HttpRequestMethod;

final readonly class RequestMethodMapper
{
    public function mapFromString(string $method): HttpRequestMethod
    {
        $methodNormalized = strtoupper($method);

        switch ($methodNormalized) {
            case 'GET':
                return HttpRequestMethod::GET;
            case 'POST':
                return HttpRequestMethod::POST;
            case 'PUT':
                return HttpRequestMethod::PUT;
            case 'DELETE':
                return HttpRequestMethod::DELETE;
            case 'PATCH':
                return HttpRequestMethod::PATCH;
            case 'OPTIONS':
                return HttpRequestMethod::OPTIONS;
            case 'HEAD':
                return HttpRequestMethod::HEAD;
            case 'TRACE':
                return HttpRequestMethod::TRACE;
            default:
                throw new \InvalidArgumentException("Unknown HTTP method: $method");
        }
    }

}