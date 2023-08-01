<?php

declare(strict_types=1);

namespace AsgrimTest\MiniMezzio;

use Asgrim\MiniMezzio\AppFactory;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function ob_get_clean;
use function ob_start;
use function uniqid;

/** @coversNothing */
final class AppFactoryIntegrationTest extends TestCase
{
    private const HANDLER_CONTAINER_KEY = 'MyHandlerContainerKey';

    /**
     * Separate process needed because the SapiEmitter sends headers and headers are always already sent.
     *
     * @runInSeparateProcess
     */
    public function testCreatesAndRunsApplication(): void
    {
        $expectedContent = uniqid('expectedContent', true);
        $container       = new ServiceManager();
        $container->setFactory(
            self::HANDLER_CONTAINER_KEY,
            static function () use ($expectedContent) {
                return new class ($expectedContent) implements RequestHandlerInterface {
                    public function __construct(private string $contentToReturn)
                    {
                    }

                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        return new TextResponse($this->contentToReturn, 201);
                    }
                };
            },
        );
        $router = new FastRouteRouter();

        $_SERVER = [
            'SERVER_NAME' => 'mini-mezzio.local',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/hello-world',
        ];

        $app = AppFactory::create($container, $router);
        $app->pipe(new RouteMiddleware($router));
        $app->pipe(new DispatchMiddleware());
        $app->get('/hello-world', self::HANDLER_CONTAINER_KEY);

        ob_start();
        $app->run();
        self::assertSame($expectedContent, ob_get_clean());
    }
}
