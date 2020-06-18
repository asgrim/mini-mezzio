# Mini Mezzio

Make setting up a Mezzio application even quicker by using this package.
This makes an assumption that you are happy to use:

 - `laminas/diactoros` for the application's PSR-7 server request
   implementation
 - You do not need to use the `SapiStreamEmitter`
 - Your `ServerRequest` comes from `$_SERVER`

All you need to provide is:

 - A container instance implementing `ContainerInterface`, for example
   [laminas-servicemanager](https://docs.laminas.dev/laminas-servicemanager/)
 - A router instance implementing `RouterInterface`, for example [FastRoute](https://docs.mezzio.dev/mezzio/v3/features/router/fast-route/)

You would then pipe the `RouteMiddleware`, `DispatchMiddleware`, and any other
middleware or request handlers that your application needs.

## Basic Usage: Example Application using Mini Mezzio
 
First you must require Mini Mezzio, a container, and a router with
Composer. In this example, we'll use Laminas ServiceManager and FastRoute.

```bash
$ composer require asgrim/mini-mezzio laminas/laminas-servicemanager mezzio/mezzio-fastroute
```

Then in `public/index.php` we can create our application:

```php
<?php

declare(strict_types=1);

use Laminas\Diactoros\Response\TextResponse;
use Laminas\ServiceManager\ServiceManager;
use Asgrim\MiniMezzio\AppFactory;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

require __DIR__ . '/../vendor/autoload.php';

$container = new ServiceManager();
$router = new FastRouteRouter();
$app = AppFactory::create($container, $router);
$app->pipe(new RouteMiddleware($router));
$app->pipe(new DispatchMiddleware());
$app->get('/hello-world', new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new TextResponse('Hello world!');
    }
});
$app->run();
```

You can use the PHP built-in web server to check this works. Spin this up with:

```bash
$ php -S 0.0.0.0:8080 -t public public/index.php
```

Now if you visit http://localhost:8080 in your browser, you should see the text
`Hello world!` displayed. Now you're ready to make middleware in minutes!

## Use the container for pipeline and endpoints

It probably won't be long before you'll want to leverage the container to use
dependency injection for your middleware handlers instead of putting them all
in the `public/index.php`. Since Mezzio already can pull middleware from the
container it is given, you can put your handler in a proper class:

```php
<?php

declare(strict_types=1);

namespace MyApp\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HelloWorldHandler implements RequestHandlerInterface
{
     public function handle(ServerRequestInterface $request): ResponseInterface
     {
         return new JsonResponse(['message' => 'Hello world!'], 200);
     }
 }
```

You can update your `public/index.php` to pull things from the container:

```php
<?php

declare(strict_types=1);

use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Laminas\ServiceManager\ServiceManager;
use Asgrim\MiniMezzio\AppFactory;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\RouterInterface;

require __DIR__ . '/../vendor/autoload.php';

$container = new ServiceManager();
$container->addAbstractFactory(ReflectionBasedAbstractFactory::class);
$container->setFactory(RouterInterface::class, static function () {
    return new FastRouteRouter();
});
$app = AppFactory::create($container, $container->get(RouterInterface::class));
$app->pipe(RouteMiddleware::class);
$app->pipe(DispatchMiddleware::class);
$app->get('/hello-world', \MyApp\Handler\HelloWorldHandler::class);
$app->run();
```

We've updated several things here, using the power of Laminas Service Manager:

 - Added the `ReflectionBasedAbstractFactory` - this allows DI autowiring based
   on reflection. This can autowire most things
 - Added a factory for `\Mezzio\Router\RouterInterface` which returns the
   instance of `Mezzio\Router\FastRouteRouter`. The `RouteMiddleware` can then
   be autowired using the `ReflectionBasedAbstractFactory`
 - `RouteMiddleware` and `DispatchMiddleware` are now referred to with just
   `::class` rather than creating instances; since they're just strings, they
   are looked up in the container.
 - The route `GET /hello-world` is mapped to `\MyApp\Handler\HelloWorldHandler`
   that we created above; again, since this is a string, the instance is
   fetched from the container.
