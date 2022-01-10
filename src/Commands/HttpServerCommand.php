<?php declare(strict_types = 1);

/**
 * HttpServerCommand.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Commands
 * @since          0.1.0
 *
 * @date           15.03.20
 */

namespace FastyBird\WebServer\Commands;

use FastyBird\SocketServerFactory;
use FastyBird\WebServer\Events;
use FastyBird\WebServer\Exceptions;
use FastyBird\WebServer\Middleware;
use Nette;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Http;
use React\Socket;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;

/**
 * HTTP server command
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HttpServerCommand extends Console\Command\Command
{

	use Nette\SmartObject;

	/** @var Middleware\CorsMiddleware */
	private Middleware\CorsMiddleware $corsMiddleware;

	/** @var Middleware\StaticFilesMiddleware */
	private Middleware\StaticFilesMiddleware $staticFilesMiddleware;

	/** @var Middleware\RouterMiddleware */
	private Middleware\RouterMiddleware $routerMiddleware;

	/** @var EventDispatcher\EventDispatcherInterface|null */
	private ?EventDispatcher\EventDispatcherInterface $dispatcher;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/** @var SocketServerFactory\SocketServerFactory */
	private SocketServerFactory\SocketServerFactory $socketServerFactory;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $eventLoop;

	/**
	 * @param Middleware\CorsMiddleware $corsMiddleware
	 * @param Middleware\StaticFilesMiddleware $staticFilesMiddleware
	 * @param Middleware\RouterMiddleware $routerMiddleware
	 * @param EventDispatcher\EventDispatcherInterface|null $dispatcher
	 * @param SocketServerFactory\SocketServerFactory $socketServerFactory
	 * @param EventLoop\LoopInterface $eventLoop
	 * @param Log\LoggerInterface|null $logger
	 * @param string|null $name
	 */
	public function __construct(
		Middleware\CorsMiddleware $corsMiddleware,
		Middleware\StaticFilesMiddleware $staticFilesMiddleware,
		Middleware\RouterMiddleware $routerMiddleware,
		SocketServerFactory\SocketServerFactory $socketServerFactory,
		EventLoop\LoopInterface $eventLoop,
		?EventDispatcher\EventDispatcherInterface $dispatcher = null,
		?Log\LoggerInterface $logger = null,
		?string $name = null
	) {
		parent::__construct($name);

		$this->corsMiddleware = $corsMiddleware;
		$this->staticFilesMiddleware = $staticFilesMiddleware;
		$this->routerMiddleware = $routerMiddleware;

		$this->dispatcher = $dispatcher;
		$this->socketServerFactory = $socketServerFactory;

		$this->logger = $logger ?? new Log\NullLogger();

		$this->eventLoop = $eventLoop;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function configure(): void
	{
		parent::configure();

		$this
			->setName('fb:web-server:start')
			->setDescription('Start http server.');
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output
	): int {
		$this->logger->info('[FB:WEB_SERVER] Starting HTTP server');

		$socketServer = $this->socketServerFactory->create();

		/**
		 * HTTP server
		 */

		try {
			if ($this->dispatcher !== null) {
				$this->dispatcher->dispatch(new Events\StartupEvent());
			}

			$httpServer = new Http\HttpServer(
				$this->eventLoop,
				$this->corsMiddleware,
				$this->staticFilesMiddleware,
				$this->routerMiddleware
			);

			$httpServer->on('error', function (Throwable $ex): void {
				// Log error action reason
				$this->logger->error('[FB:WEB_SERVER] Stopping HTTP server', [
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
					'cmd'       => $this->getName(),
				]);

				$this->eventLoop->stop();
			});

			$httpServer->listen($socketServer);

			if ($socketServer->getAddress() !== null) {
				if ($socketServer instanceof Socket\SecureServer) {
					$this->logger->info(sprintf('[FB:WEB_SERVER] Listening on "%s"', str_replace('tls:', 'https:', $socketServer->getAddress())));

				} else {
					$this->logger->info(sprintf('[FB:WEB_SERVER] Listening on "%s"', str_replace('tcp:', 'http:', $socketServer->getAddress())));
				}
			}

			$this->eventLoop->run();

		} catch (Exceptions\TerminateException $ex) {
			// Log error action reason
			$this->logger->error('[FB:WEB_SERVER] HTTP server was forced to close', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'cmd'       => $this->getName(),
			]);

			$this->eventLoop->stop();

		} catch (Throwable $ex) {
			var_dump($ex->getMessage());
			// Log error action reason
			$this->logger->error('[FB:WEB_SERVER] An error occur & stopping server', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'cmd'       => $this->getName(),
			]);

			$this->eventLoop->stop();
		}

		return 0;
	}

}
