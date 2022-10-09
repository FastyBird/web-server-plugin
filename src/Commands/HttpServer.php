<?php declare(strict_types = 1);

/**
 * HttpServer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Commands
 * @since          0.1.0
 *
 * @date           15.03.20
 */

namespace FastyBird\WebServerPlugin\Commands;

use FastyBird\WebServerPlugin\Events;
use FastyBird\WebServerPlugin\Exceptions;
use FastyBird\WebServerPlugin\Server;
use Nette;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Socket;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;
use function file_exists;
use function is_file;

/**
 * HTTP server command
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HttpServer extends Console\Command\Command
{

	use Nette\SmartObject;

	public const NAME = 'fb:web-server:start';

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly string $serverAddress,
		private readonly int $serverPort,
		private readonly Server\Factory $serverFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		Log\LoggerInterface|null $logger = null,
		private readonly string|null $serverCertificate = null,
		string|null $name = null,
	)
	{
		parent::__construct($name);

		$this->logger = $logger ?? new Log\NullLogger();
	}

	protected function configure(): void
	{
		parent::configure();

		$this
			->setName(self::NAME)
			->setDescription('Start http server.');
	}

	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): int
	{
		$this->logger->info(
			'Launching HTTP Server',
			[
				'source' => 'web-server-plugin',
				'type' => 'command',
			],
		);

		try {
			$this->dispatcher?->dispatch(new Events\Startup());

			$socketServer = new Socket\SocketServer(
				$this->serverAddress . ':' . $this->serverPort,
				[],
				$this->eventLoop,
			);

			if ($this->serverCertificate !== null) {
				if (
					is_file($this->serverCertificate)
					&& file_exists($this->serverCertificate)
				) {
					$socketServer = new Socket\SecureServer($socketServer, $this->eventLoop, [
						'local_cert' => $this->serverCertificate,
					]);

				} else {
					throw new Exceptions\InvalidArgument('Provided SSL certificate file could not be loaded');
				}
			}

			$this->serverFactory->create($socketServer);

			$this->eventLoop->run();

		} catch (Exceptions\Terminate $ex) {
			// Log error action reason
			$this->logger->error(
				'HTTP server was forced to close',
				[
					'source' => 'web-server-plugin',
					'type' => 'command',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error(
				'An unhandled error occurred. Stopping HTTP server',
				[
					'source' => 'web-server-plugin',
					'type' => 'command',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

			return self::FAILURE;
		}

		return self::SUCCESS;
	}

}
