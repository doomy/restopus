<?php

namespace Doomy\Restopus\tests\Support\Request;

use Doomy\Restopus\Request\AbstractRequestEntity;

final class TestRequestBody extends AbstractRequestEntity
{
    public int $id;

    public \DateTimeInterface $dateTime;

}