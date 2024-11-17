<?php

namespace Doomy\Restopus\tests\Support\Response\View;

use Doomy\Restopus\Response\AbstractResponseEntity;

final class DummyEntityView extends AbstractResponseEntity
{
    public string $name;
    public string $description;

}