<?php declare(strict_types = 1);

/**
 * HttpServerCommand.php
 *
 * @license        More in license.md
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
use FastyBird\WebServer;
use FastyBird\WebServer\Exceptions;
use Fig\Http\Message\StatusCodeInterface;
use IPub\SlimRouter\Routing;
use Nette;
use Psr\EventDispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log;
use React\EventLoop;
use React\Http;
use React\Promise;
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

	/** @var Routing\IRouter */
	private Routing\IRouter $router;

	/** @var EventDispatcher\EventDispatcherInterface */
	private EventDispatcher\EventDispatcherInterface $dispatcher;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $eventLoop;

	/** @var Socket\Server */
	private Socket\Server $socketServer;

	/**
	 * @param Socket\Server $socketServer
	 * @param EventLoop\LoopInterface $eventLoop
	 * @param Routing\IRouter $router
	 * @param EventDispatcher\EventDispatcherInterface $dispatcher
	 * @param Log\LoggerInterface|null $logger
	 * @param string|null $name
	 */
	public function __construct(
		Socket\Server $socketServer,
		EventLoop\LoopInterface $eventLoop,
		Routing\IRouter $router,
		EventDispatcher\EventDispatcherInterface $dispatcher,
		?Log\LoggerInterface $logger = null,
		?string $name = null
	) {
		parent::__construct($name);

		$this->router = $router;
		$this->dispatcher = $dispatcher;
		$this->logger = $logger ?? new Log\NullLogger();

		$this->socketServer = $socketServer;
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

		/**
		 * HTTP server
		 */

		try {
			$this->dispatcher->dispatch(new ApplicationEventsEvents\StartupEvent());

			$server = new Http\Server($this->eventLoop, function (ServerRequestInterface $request): Promise\Promise {
				return new Promise\Promise(function ($resolve) use ($request): void {
					try {
						$this->dispatcher->dispatch(new ApplicationEventsEvents\RequestEvent($request));

						$response = $this->router->handle($request);

						$this->dispatcher->dispatch(new ApplicationEventsEvents\ResponseEvent($request, $response));

						$resolve($response);

					} catch (Throwable $ex) {
						// Log error action reason
						$this->logger->error('[FB:WEB_SERVER] Stopping HTTP server', [
							'exception' => [
								'message' => $ex->getMessage(),
								'code'    => $ex->getCode(),
							],
							'cmd'       => $this->getName(),
						]);

						$this->eventLoop->stop();
					}

					$response = WebServer\Http\Response::text(
						'Server error',
						StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
					);

					$resolve($response);
				});
			});

			$server->listen($this->socketServer);

			if ($this->socketServer->getAddress() !== null) {
				$this->logger->info(sprintf('[FB:WEB_SERVER] Listening on "%s"', str_replace('tcp:', 'http:', $this->socketServer->getAddress())));
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
