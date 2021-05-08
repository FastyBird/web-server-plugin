<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\WebServer\Commands;
use FastyBird\WebServer\Http;
use FastyBird\WebServer\StaticFiles;
use React\EventLoop;
use React\Socket;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class ExtensionTest extends BaseTestCase
{

	public function testCompilersServices(): void
	{
		$container = $this->createContainer();

		Assert::notNull($container->getByType(Commands\HttpServerCommand::class));

		Assert::notNull($container->getByType(Http\ResponseFactory::class));

		Assert::notNull($container->getByType(EventLoop\LoopInterface::class));
		Assert::notNull($container->getByType(Socket\Server::class));

		Assert::notNull($container->getByType(StaticFiles\Controller::class));
		Assert::notNull($container->getByType(StaticFiles\Webroot::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
