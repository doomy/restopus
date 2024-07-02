<?php

namespace Doomy\Restopus\Request\Attribute;

use Attribute;
use Doomy\Restopus\Request\Enum\HttpRequestMethod;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class HttpMethod
{
    public function __construct(private HttpRequestMethod $httpRequestMethod) {}

    public function getHttpRequestMethod(): HttpRequestMethod
    {
        return $this->httpRequestMethod;
    }
}