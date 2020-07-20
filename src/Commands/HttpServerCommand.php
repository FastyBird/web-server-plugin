<?php declare(strict_types = 1);

/**
 * HttpServerCommand.php
 *
 * @license        More in license.md
 * @copyright      https://fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NodeWebServer!
 * @subpackage     Commands
 * @since          0.1.0
 *
 * @date           15.03.20
 */

namespace FastyBird\NodeWebServer\Commands;

use Closure;
use FastyBird\NodeWebServer;
use Fig\Http\Message\StatusCodeInterface;
use IPub\SlimRouter\Routing;
use Nette;
use Psr\Http\Message\ResponseInterface;
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
 * @package        FastyBird:NodeWebServer!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method onBeforeServerStart()
 * @method onServerStart()
 * @method onRequest(ServerRequestInterface $request)
 * @method onResponse(ServerRequestInterface $request, ResponseInterface $response)
 */
class HttpServerCommand extends Console\Command\Command
{

	use Nette\SmartObject;

	/** @var Closure[] */
	public $onBeforeServerStart = [];

	/** @var Closure[] */
	public $onServerStart = [];

	/** @var Closure[] */
	public $onRequest = [];

	/** @var Closure[] */
	public $onResponse = [];

	/** @var Routing\IRouter */
	private $router;

	/** @var Log\LoggerInterface */
	private $logger;

	/** @var EventLoop\LoopInterface */
	private $eventLoop;

	/** @var string */
	private $address;

	/** @var int */
	private $port;

	/**
	 * @param EventLoop\LoopInterface $eventLoop
	 * @param Routing\IRouter $router
	 * @param string $address
	 * @param int $port
	 * @param Log\LoggerInterface|null $logger
	 * @param string|null $name
	 */
	public function __construct(
		EventLoop\LoopInterface $eventLoop,
		Routing\IRouter $router,
		string $address = '127.0.0.1',
		int $port = 8000,
		?Log\LoggerInterface $logger = null,
		?string $name = null
	) {
		parent::__construct($name);

		$this->router = $router;
		$this->logger = $logger ?? new Log\NullLogger();

		$this->eventLoop = $eventLoop;

		$this->address = $address;
		$this->port = $port;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function configure(): void
	{
		parent::configure();

		$this
			->setName('fb:node:server:start')
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
			$this->onBeforeServerStart();

			$server = new Http\Server(function (ServerRequestInterface $request): Promise\Promise {
				return new Promise\Promise(function ($resolve, $reject) use ($request): void {
					try {
						$this->onRequest($request);

						$response = $this->router->handle($request);

						$this->onResponse($request, $response);

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

					$response = NodeWebServer\Http\Response::text(
						'Server error',
						StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
					);

					$resolve($response);
				});
			});

			$socket = new Socket\Server($this->address . ':' . (string) $this->port, $this->eventLoop);
			$server->listen($socket);

			if ($socket->getAddress() !== null) {
				$this->logger->debug(sprintf('[FB:WEB_SERVER] Listening on "%s"', str_replace('tcp:', 'http:', $socket->getAddress())));
			}

			$this->onServerStart();

			$this->eventLoop->run();

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

		return 0;
	}

}
