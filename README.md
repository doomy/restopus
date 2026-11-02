# Rest API support libraries

works with Nette presenters & routing

## Installation
```
composer require doomy/restopus
```

Register the extension in your NEON configuration (`config.neon` / `common.neon`):
```neon
extensions:
    restopus: Doomy\Restopus\DI\RestopusExtension

restopus:
    presenterNamespace: <Your\Presenter\Namespace>
    presentersDir: %rootDir%/src/Presenter

application:
    mapping:
        *: <Your\Presenter\Namespace>\*Presenter
```

The extension registers Restopus' internal services and the router itself, so your
application does not have to list them.

### Options

| Option | Default | Description |
|---|---|---|
| `presenterNamespace` | required | Namespace your REST presenters live in |
| `presentersDir` | required | Directory scanned for presenter classes |
| `registerRouter` | `true` | Register the `RouteList` built from the `#[Route]` attributes of your presenters. Set to `false` to compose Restopus' routes into a router of your own, using the `restopus.routerFactory` service. |

### Authentication

Restopus does **not** register an authenticator, because which one to use is an
application decision. Register an implementation of
`Doomy\Security\Authenticator\AuthenticatorInterface` yourself:

```neon
services:
    - Doomy\Security\Authenticator\JwtAuthenticator
```

`Doomy\Security\Authenticator\DummyAuthenticator` accepts **any** access token and
returns a fixed identity. It exists for tests, and must not be registered in production.

### Upgrading from the manual `services:` block

Previous versions required the application to list every internal service:

```neon
services:
    routerFactory: Doomy\Restopus\Routing\RestRouterFactory(
        restPresenterNamespace: <Your\Presenter\Namespace>,
        presentersDir: %rootDir%/src/Presenter
    )
    router:
        factory: @routerFactory::createRouter()
    - Doomy\Restopus\Response\Service\EntityViewResponseMapper
    - Doomy\Restopus\Request\Service\RequestBodyProvider
    - Doomy\Restopus\Request\Service\RequestValidator
    - Doomy\Restopus\Request\Service\RequestMethodMapper
    - Doomy\Security\Authenticator\DummyAuthenticator
    - Doomy\Restopus\Request\Service\InPathParameterProvider
    - Doomy\Restopus\Attribute\Service\AttributeProvider
```

Replace the whole block with the `extensions:` form above, keeping only your own
authenticator registration. Do not keep both: the extension and a leftover manual block
register two services for each type, and autowiring then fails with an ambiguity error.

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
