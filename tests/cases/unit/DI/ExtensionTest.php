<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use FastyBird\WebServerPlugin\Application;
use FastyBird\WebServerPlugin\Commands;
use FastyBird\WebServerPlugin\Http;
use FastyBird\WebServerPlugin\Middleware;
use FastyBird\WebServerPlugin\Server;
use Nette;
use React\EventLoop;
use Tests\Cases\Unit\BaseTestCase;

final class ExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		self::assertNotNull($this->container->getByType(Application\Console::class, false));
		self::assertNotNull($this->container->getByType(Application\Application::class, false));

		self::assertNotNull($this->container->getByType(Commands\HttpServer::class, false));

		self::assertNotNull($this->container->getByType(Http\ResponseFactory::class, false));

		self::assertNotNull($this->container->getByType(EventLoop\LoopInterface::class, false));

		self::assertNotNull($this->container->getByType(Middleware\Cors::class, false));
		self::assertNotNull($this->container->getByType(Middleware\StaticFiles::class, false));
		self::assertNotNull($this->container->getByType(Middleware\Router::class, false));

		self::assertNotNull($this->container->getByType(Server\Factory::class, false));
	}

}
