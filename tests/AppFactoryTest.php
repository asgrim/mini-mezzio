<?php

declare(strict_types=1);

namespace AsgrimTest\MiniMezzio;

use Asgrim\MiniMezzio\AppFactory;
use Laminas\Diactoros\Response\TextResponse;
use Mezzio\Router\Route;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @covers \Asgrim\MiniMezzio\AppFactory */
final class AppFactoryTest extends TestCase
{
    public function testCreatesApplication(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $router    = $this->createMock(RouterInterface::class);

        // Check we can perform something simple like adding a route
        $router->expects(self::once())
            ->method('addRoute')
            ->with(self::isInstanceOf(Route::class));

        $app = AppFactory::create($container, $router);
        $app->get('/hello-world', new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new TextResponse('hey');
            }
        });
    }
}
