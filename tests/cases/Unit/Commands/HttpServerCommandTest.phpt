<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\WebServer\Commands;
use FastyBird\WebServer\Middleware;
use Mockery;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class HttpServerCommandTest extends BaseMockeryTestCase
{

	public function testExecute(): void
	{
		$promise = Mockery::mock(Promise\PromiseInterface::class);
		$promise
			->shouldReceive('then')
			->andReturn($promise);

		$logger = Mockery::mock(Log\LoggerInterface::class);
		$logger
			->shouldReceive('info')
			->withArgs(['[FB:WEB_SERVER] Starting HTTP server'])
			->times(1)
			->getMock()
			->shouldReceive('info')
			->withArgs(['[FB:WEB_SERVER] Listening on "http://127.0.0.1:8001"'])
			->times(1);

		$eventLoop = Mockery::mock(EventLoop\LoopInterface::class);
		$eventLoop
			->shouldReceive('addReadStream')
			->getMock()
			->shouldReceive('run')
			->withNoArgs();

		$eventDispatcher = Mockery::mock(EventDispatcher\EventDispatcherInterface::class);
		$eventDispatcher
			->shouldReceive('dispatch')
			->times(2);

		$corsFilesMiddleware = Mockery::mock(Middleware\CorsMiddleware::class);

		$staticFilesMiddleware = Mockery::mock(Middleware\StaticFilesMiddleware::class);

		$routerMiddleware = Mockery::mock(Middleware\RouterMiddleware::class);

		$application = new Application();
		$application->add(new Commands\HttpServerCommand(
			'127.0.0.1',
			8001,
			$corsFilesMiddleware,
			$staticFilesMiddleware,
			$routerMiddleware,
			$eventLoop,
			$eventDispatcher,
			$logger
		));

		$command = $application->get('fb:web-server:start');

		$commandTester = new CommandTester($command);
		$commandTester->execute([]);

		Assert::true(true);
	}

}

$test_case = new HttpServerCommandTest();
$test_case->run();
