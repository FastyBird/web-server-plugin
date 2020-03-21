<?php declare(strict_types = 1);

namespace Tests\Cases;

use Bunny;
use FastyBird\NodeLibs\Connections as NodeLibsConnections;
use FastyBird\NodeLibs\Consumers as NodeLibsConsumers;
use FastyBird\NodeLibs\Helpers as NodeLibsHelpers;
use FastyBird\NodeWebServer\Commands;
use IPub\SlimRouter;
use Mockery;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

final class HttpServerCommandTest extends BaseMockeryTestCase
{

	public function testExecute(): void
	{
		$promise = Mockery::mock(Promise\PromiseInterface::class);
		$promise
			->shouldReceive('then')
			->andReturn($promise);

		$rabbitMqAsyncClient = Mockery::mock(Bunny\Async\Client::class);
		$rabbitMqAsyncClient
			->shouldReceive('connect')
			->withNoArgs()
			->andReturn($promise)
			->times(1);

		$rabbitMqConnection = Mockery::mock(NodeLibsConnections\IRabbitMqConnection::class);
		$rabbitMqConnection
			->shouldReceive('getAsyncClient')
			->withNoArgs()
			->andReturn($rabbitMqAsyncClient)
			->times(1);

		$initialize = Mockery::mock(NodeLibsHelpers\IInitialize::class);

		$consumer = Mockery::mock(NodeLibsConsumers\IExchangeConsumer::class);

		$router = Mockery::mock(SlimRouter\Routing\IRouter::class);

		$logger = Mockery::mock(Log\LoggerInterface::class);
		$logger
			->shouldReceive('info')
			->withArgs(['[STARTING] FB devices node - HTTP server'])
			->times(1)
			->getMock()
			->shouldReceive('debug')
			->withArgs(['[HTTP_SERVER] Listening on "http://127.0.0.1:8000"'])
			->times(1);

		$eventLoop = Mockery::mock(EventLoop\LoopInterface::class);
		$eventLoop
			->shouldReceive('addReadStream')
			->getMock()
			->shouldReceive('run')
			->withNoArgs();

		$application = new Application();
		$application->add(new Commands\HttpServerCommand(
			$rabbitMqConnection,
			$initialize,
			$consumer,
			$logger,
			$eventLoop,
			$router
		));

		$command = $application->get('fb:node:server:start');

		$commandTester = new CommandTester($command);
		$commandTester->execute([]);

		Assert::true(true);
	}

}

$test_case = new HttpServerCommandTest();
$test_case->run();
