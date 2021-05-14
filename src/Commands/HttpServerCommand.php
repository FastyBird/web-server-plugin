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

use FastyBird\ApplicationEvents\Events as ApplicationEventsEvents;
use FastyBird\WebServer\Events;
use FastyBird\WebServer\Exceptions;
use FastyBird\WebServer\Middlewares;
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

	/** @var string */
	private string $serverAddress;

	/** @var int */
	private int $serverPort;

	/** @var string|null */
	private ?string $serverCertificate;

	/** @var Middlewares\StaticFilesMiddleware */
	private Middlewares\StaticFilesMiddleware $staticFilesMiddleware;

	/** @var Middlewares\RouterMiddleware */
	private Middlewares\RouterMiddleware $routerMiddleware;

	/** @var EventDispatcher\EventDispatcherInterface */
	private EventDispatcher\EventDispatcherInterface $dispatcher;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $eventLoop;

	/**
	 * @param string $serverAddress
	 * @param int $serverPort
	 * @param string|null $serverCertificate
	 * @param Middlewares\StaticFilesMiddleware $staticFilesMiddleware
	 * @param Middlewares\RouterMiddleware $routerMiddleware
	 * @param EventLoop\LoopInterface $eventLoop
	 * @param EventDispatcher\EventDispatcherInterface $dispatcher
	 * @param Log\LoggerInterface|null $logger
	 * @param string|null $name
	 */
	public function __construct(
		string $serverAddress,
		int $serverPort,
		Middlewares\StaticFilesMiddleware $staticFilesMiddleware,
		Middlewares\RouterMiddleware $routerMiddleware,
		EventLoop\LoopInterface $eventLoop,
		EventDispatcher\EventDispatcherInterface $dispatcher,
		?Log\LoggerInterface $logger = null,
		?string $serverCertificate = null,
		?string $name = null
	) {
		parent::__construct($name);

		$this->serverAddress = $serverAddress;
		$this->serverPort = $serverPort;
		$this->serverCertificate = $serverCertificate;

		$this->staticFilesMiddleware = $staticFilesMiddleware;
		$this->routerMiddleware = $routerMiddleware;

		$this->dispatcher = $dispatcher;
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

		$socketServer = new Socket\Server($this->serverAddress . ':' . $this->serverPort, $this->eventLoop);

		if (
			$this->serverCertificate !== null
			&& is_file($this->serverCertificate)
			&& file_exists($this->serverCertificate)
		) {
			$socketServer = new Socket\SecureServer($socketServer, $this->eventLoop, [
				'local_cert' => $this->serverCertificate,
			]);
		}

		$this->dispatcher->dispatch(new Events\InitializeEvent($socketServer));

		/**
		 * HTTP server
		 */

		try {
			$this->dispatcher->dispatch(new ApplicationEventsEvents\StartupEvent());

			$server = new Http\Server(
				$this->eventLoop,
				$this->staticFilesMiddleware,
				$this->routerMiddleware
			);

			$server->on('error', function (Throwable $ex): void {
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

			$server->listen($socketServer);

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
