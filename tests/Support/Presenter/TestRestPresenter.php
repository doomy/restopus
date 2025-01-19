<?php

namespace Doomy\Restopus\tests\Support\Presenter;

use Doomy\Restopus\Request\Attribute\Authenticated;
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
    public function actionList(): Response
    {
        $entities = [
            new \Doomy\Restopus\tests\Support\Entity\DummyEntity('name1', 'description1'),
            new \Doomy\Restopus\tests\Support\Entity\DummyEntity('name2', 'description2'),
        ];
        return new JsonResponse(['data' => $this->mapEntitiesToResponse($entities, DummyEntityView::class)]);
    }

    #[Authenticated]
    #[HttpMethod(HttpRequestMethod::GET)]
    #[Route('/authenticated')]
    public function actionAuthenticated(): Response
    {
        return new JsonResponse(['data' => 'authenticated']);
    }

    #[Route('POST', '/update')]
    public function update(): void
    {
    }

    #[HttpMethod(HttpRequestMethod::GET)]
    #[Route('/in-path/<id>')]
    public function actionWithInPathParameters(array $params): Response
    {
        $id = $this->getPathParameter('id');
        return new JsonResponse(['data' => ['id' => $id]]);
    }
}