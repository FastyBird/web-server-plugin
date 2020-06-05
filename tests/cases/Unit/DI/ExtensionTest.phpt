<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\NodeLibs\Boot;
use FastyBird\NodeWebServer\Commands;
use FastyBird\NodeWebServer\DI;
use FastyBird\NodeWebServer\Http;
use Ninjify\Nunjuck\TestCase\BaseTestCase;
use React\EventLoop;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

final class ExtensionTest extends BaseTestCase
{

	public function testCompilersServices(): void
	{
		$configurator = Boot\Bootstrap::boot();
		$configurator->addParameters([
			'origin'   => 'com.fastybird.node',
			'rabbitmq' => [
				'queueName' => 'testingQueueName',
			],
		]);

		DI\NodeWebServerExtension::register($configurator);

		$container = $configurator->createContainer();

		Assert::notNull($container->getByType(Commands\HttpServerCommand::class));
		Assert::notNull($container->getByType(Http\ResponseFactory::class));
		Assert::notNull($container->getByType(EventLoop\LoopInterface::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
