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

## Tutorial: Example Application using Mini Mezzio
 
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
