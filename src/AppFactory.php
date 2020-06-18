<?php

declare(strict_types=1);

namespace Asgrim\MiniMezzio;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Application;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

class AppFactory
{
    /**
     * Create an {@see Application} instance in a shorter way by assuming defaults for most dependencies. Using
     * this method of instantiating the application assumes:
     *
     *  - You have `laminas/diactoros` installed
     *  - You do not need the {@see SapiStreamEmitter}
     *  - Your {@see ServerRequest} comes from {@see $_SERVER}
     */
    public static function create(
        ContainerInterface $container,
        RouterInterface $router,
        bool $developmentMode = false
    ): Application {
        $middlewarePipe = new MiddlewarePipe();

        return new Application(
            new MiddlewareFactory(new MiddlewareContainer($container)),
            $middlewarePipe,
            new RouteCollector($router),
            new RequestHandlerRunner(
                $middlewarePipe,
                new SapiEmitter(),
                [ServerRequestFactory::class, 'fromGlobals'],
                new ServerRequestErrorResponseGenerator(
                    static function (): Response {
                        return new Response();
                    },
                    $developmentMode
                )
            )
        );
    }
}
