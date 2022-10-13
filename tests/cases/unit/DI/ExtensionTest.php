<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use FastyBird\WebServerPlugin\Application;
use FastyBird\WebServerPlugin\Commands;
use FastyBird\WebServerPlugin\Http;
use FastyBird\WebServerPlugin\Middleware;
use FastyBird\WebServerPlugin\Server;
use React\EventLoop;
use Tests\Cases\Unit\BaseTestCase;

final class ExtensionTest extends BaseTestCase
{

	public function testCompilersServices(): void
	{
		$container = $this->createContainer();

		$this->assertNotNull($container->getByType(Application\Console::class));
		$this->assertNotNull($container->getByType(Application\Application::class));

		$this->assertNotNull($container->getByType(Commands\HttpServer::class));

		$this->assertNotNull($container->getByType(Http\ResponseFactory::class));

		$this->assertNotNull($container->getByType(EventLoop\LoopInterface::class));

		$this->assertNotNull($container->getByType(Middleware\Cors::class));
		$this->assertNotNull($container->getByType(Middleware\StaticFiles::class));
		$this->assertNotNull($container->getByType(Middleware\Router::class));

		$this->assertNotNull($container->getByType(Server\Factory::class));
	}

}
