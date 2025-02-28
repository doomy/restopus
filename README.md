# Rest API support libraries

works with Nette presenters & routing

## Installation
```
composer require doomy/restopus
```

in your NEON configuration (`config.neon` / `common.neon`):
```neon
services:
    routerFactory: Doomy\Restopus\Routing\RestRouterFactory(
        restPresenterNamespace: <Your\Presenter\Namespace>,
        presentersDir: %rootDir%/src/Presenter
    )
    router:
        factory: @routerFactory::createRouter()
```

## Example

```php

final class EventPresenter extends AbstractRestPresenter

    #[HttpMethod(HttpRequestMethod::POST)]
    #[Authenticated(userEntityClass: User::class)]
    #[RequestBody(EventCreateRequestBody::class)]
    #[Route('/events/create')]
    public function actionCreateEvent(array $requestData): Response
    {
        $body = $this->getBody($requestData, EventCreateRequestBody::class);
        $this->eventFacade->createEvent(
            $body->title,
            $body->description,
            $body->start,
            $body->end,
            $body->location,
            $this->authenticator->getIdentity()
                ->getId()
        );
        $this->httpResponse->setCode(201);

        return new VoidResponse();
    }
```

example in tests:
```php
final class EventCreateTest extends TestCase
{
    use RestTestTrait;
   
    private const ENDPOINT_URL = '<YOUR-TEST-SERVER>/events/create';
    public function testEventCreateNotLoggedIn(): void
    {
        $response = $this->sendPost(self::ENDPONT_URL, []);
        Assert::assertSame(403, $response->getStatusCode());
    }

    public function testEventCreateLoggedIn(): void
    {
        $start = new \DateTimeImmutable();
        $end = (new \DateTimeImmutable())->modify('+1 day');

        $response = $this->sendPost(self::ENDPOINT_URL, [
            'title' => 'Event name',
            'description' => 'Event description',
        ], '<ACCESS_TOKEN>');
        HttpResponseAssert::assertResponseCode($response, 201);
```
