<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\WebServer\Commands;
use FastyBird\WebServer\Http;
use FastyBird\WebServer\Middlewares;
use React\EventLoop;
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

		Assert::notNull($container->getByType(Middlewares\StaticFilesMiddleware::class));
		Assert::notNull($container->getByType(Middlewares\RouterMiddleware::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
