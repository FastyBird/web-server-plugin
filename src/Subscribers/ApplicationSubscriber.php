<?php declare(strict_types = 1);

/**
 * ApplicationSubscriber.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           10.06.22
 */

namespace FastyBird\WebServerPlugin\Subscribers;

use FastyBird\SocketServerFactory\Events as SocketServerFactoryEvents;
use FastyBird\WebServerPlugin\Middleware;
use Psr\Log;
use React\EventLoop;
use React\Http;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Server startup subscriber
 *
 * @package         FastyBird:WebServerPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApplicationSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var Middleware\CorsMiddleware */
	private Middleware\CorsMiddleware $corsMiddleware;

	/** @var Middleware\StaticFilesMiddleware */
	private Middleware\StaticFilesMiddleware $staticFilesMiddleware;

	/** @var Middleware\RouterMiddleware */
	private Middleware\RouterMiddleware $routerMiddleware;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $eventLoop;

	public function __construct(
		Middleware\CorsMiddleware $corsMiddleware,
		Middleware\StaticFilesMiddleware $staticFilesMiddleware,
		Middleware\RouterMiddleware $routerMiddleware,
		EventLoop\LoopInterface $eventLoop,
		?Log\LoggerInterface $logger = null
	) {
		$this->corsMiddleware = $corsMiddleware;
		$this->staticFilesMiddleware = $staticFilesMiddleware;
		$this->routerMiddleware = $routerMiddleware;

		$this->logger = $logger ?? new Log\NullLogger();

		$this->eventLoop = $eventLoop;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			SocketServerFactoryEvents\InitializeEvent::class => 'initialize',
		];
	}

	/**
	 * @param SocketServerFactoryEvents\InitializeEvent $event
	 *
	 * @return void
	 */
	public function initialize(SocketServerFactoryEvents\InitializeEvent $event): void
	{
		$httpServer = new Http\HttpServer(
			$this->eventLoop,
			$this->corsMiddleware,
			$this->staticFilesMiddleware,
			$this->routerMiddleware
		);

		$httpServer->on('error', function (Throwable $ex): void {
			// Log error action reason
			$this->logger->error(
				'An error occurred during handling request. Stopping HTTP server',
				[
					'source'   => 'web-server-plugin',
					'type'     => 'command',
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
				]
			);

			$this->eventLoop->stop();
		});

		$httpServer->listen($event->getServer());

		$this->logger->debug('Launching HTTP Server', [
			'source' => 'web-server-plugin',
			'type'   => 'subscriber',
		]);
	}

}
