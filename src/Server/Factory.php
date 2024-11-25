<?php declare(strict_types = 1);

/**
 * Factory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Server
 * @since          1.0.0
 *
 * @date           10.06.22
 */

namespace FastyBird\Plugin\WebServer\Server;

use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\WebServer\Middleware;
use Psr\Log;
use React\EventLoop;
use React\Http;
use React\Socket;
use Throwable;
use function sprintf;
use function str_replace;

/**
 * HTTP server factory
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Server
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Factory
{

	public function __construct(
		private Middleware\Cors $corsMiddleware,
		private Middleware\StaticFiles $staticFilesMiddleware,
		private Middleware\Router $routerMiddleware,
		private EventLoop\LoopInterface $eventLoop,
		private Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	public function create(Socket\ServerInterface $server): void
	{
		$httpServer = new Http\HttpServer(
			$this->eventLoop,
			$this->corsMiddleware,
			$this->staticFilesMiddleware,
			$this->routerMiddleware,
		);

		$httpServer->on('error', function (Throwable $ex): void {
			// Log error action reason
			$this->logger->error(
				'An error occurred during handling request. Stopping HTTP server',
				[
					'source' => MetadataTypes\Sources\Plugin::WEB_SERVER->value,
					'type' => 'factory',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			$this->eventLoop->stop();
		});

		$httpServer->listen($server);

		if ($server->getAddress() !== null) {
			if ($server instanceof Socket\SecureServer) {
				$this->logger->info(
					sprintf('Listening on "%s"', str_replace('tls:', 'https:', $server->getAddress())),
					[
						'source' => MetadataTypes\Sources\Plugin::WEB_SERVER->value,
						'type' => 'factory',
					],
				);

			} else {
				$this->logger->info(
					sprintf('Listening on "%s"', str_replace('tcp:', 'http:', $server->getAddress())),
					[
						'source' => MetadataTypes\Sources\Plugin::WEB_SERVER->value,
						'type' => 'factory',
					],
				);
			}
		}
	}

}
