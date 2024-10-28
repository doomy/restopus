<?php

namespace Doomy\Restopus\tests\Support\Presenter;

use Doomy\Restopus\Request\Attribute\Route;

class TestRestPresenter extends \Doomy\Restopus\Presenter\AbstractRestPresenter
{
    #[Route('GET', '/list')]
    public function list(): void
    {
    }

    #[Route('POST', '/update')]
    public function update(): void
    {
    }

}