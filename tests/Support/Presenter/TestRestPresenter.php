<?php

namespace Doomy\Restopus\tests\Support\Presenter;

use Doomy\Restopus\Request\Attribute\HttpMethod;
use Doomy\Restopus\Request\Attribute\Route;
use Doomy\Restopus\Request\Enum\HttpRequestMethod;
use Doomy\Restopus\tests\Support\Response\View\DummyEntityView;
use Nette\Application\Response;
use Nette\Application\Responses\JsonResponse;

class TestRestPresenter extends \Doomy\Restopus\Presenter\AbstractRestPresenter
{
    #[HttpMethod(HttpRequestMethod::GET)]
    #[Route('/list')]
    public function list(): Response
    {
        $entities = [
            new \Doomy\Restopus\tests\Support\Entity\DummyEntity('name1', 'description1'),
            new \Doomy\Restopus\tests\Support\Entity\DummyEntity('name2', 'description2'),
        ];
        return new JsonResponse(['data' => $this->mapEntitiesToResponse($entities, DummyEntityView::class)]);
    }

    #[Route('POST', '/update')]
    public function update(): void
    {
    }

}