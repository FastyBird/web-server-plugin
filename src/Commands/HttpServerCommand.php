<?php declare(strict_types = 1);

/**
 * HttpServerCommand.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NodeWebServer!
 * @subpackage     Commands
 * @since          0.1.0
 *
 * @date           15.03.20
 */

namespace FastyBird\NodeWebServer\Commands;

use Bunny;
use Closure;
use FastyBird\NodeLibs;
use FastyBird\NodeLibs\Connections as NodeLibsConnections;
use FastyBird\NodeLibs\Consumers as NodeLibsConsumers;
use FastyBird\NodeLibs\Exceptions as NodeLibsExceptions;
use FastyBird\NodeLibs\Helpers as NodeLibsHelpers;
use FastyBird\NodeWebServer\Exceptions;
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
 * @method onRequest(ServerRequestInterface $request)
 * @method onResponse(ServerRequestInterface $request, ResponseInterface $response)
 * @method onConsumerMessage()
 */
class HttpServerCommand extends Console\Command\Command
{

	use Nette\SmartObject;

	/** @var Closure[] */
	public $onRequest = [];

	/** @var Closure[] */
	public $onResponse = [];

	/** @var Closure[] */
	public $onConsumerMessage = [];

	/** @var NodeLibsConnections\IRabbitMqConnection */
	private $rabbitMqConnection;

	/** @var NodeLibsHelpers\IInitialize */
	private $initialize;

	/** @var NodeLibsConsumers\IExchangeConsumer */
	private $exchangeConsumer;

	/** @var Routing\IRouter */
	private $router;

	/** @var Log\LoggerInterface */
	private $logger;

	/** @var EventLoop\LoopInterface */
	private $eventLoop;

	public function __construct(
		NodeLibsConnections\IRabbitMqConnection $rabbitMqConnection,
		NodeLibsHelpers\IInitialize $initialize,
		NodeLibsConsumers\IExchangeConsumer $exchangeConsumer,
		Log\LoggerInterface $logger,
		EventLoop\LoopInterface $eventLoop,
		?Routing\IRouter $router = null,
		?string $name = null
	) {
		parent::__construct($name);

		$this->rabbitMqConnection = $rabbitMqConnection;
		$this->initialize = $initialize;
		$this->exchangeConsumer = $exchangeConsumer;

		$this->router = $router ?? new Routing\Router();
		$this->logger = $logger;

		$this->eventLoop = $eventLoop;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function configure(): void
	{
		parent::configure();

		$this
			->setName('fb:devices-node:server')
			->setDescription('Start http server.');
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output
	): int {
		$this->logger->info('[STARTING] FB devices node - HTTP server');

		/**
		 * Rabbit MQ consumer
		 */

		$this->rabbitMqConnection->getAsyncClient()
			->connect()
			->then(function (Bunny\Async\Client $client) {
				return $client->channel();
			})
			->then(function (Bunny\Channel $channel): Promise\PromiseInterface {
				$qosResult = $channel->qos(0, 5);

				if ($qosResult instanceof Promise\PromiseInterface) {
					return $qosResult
						->then(function () use ($channel): Bunny\Channel {
							return $channel;
						});
				}

				throw new Exceptions\InvalidStateException('RabbitMQ QoS could not be configured');
			})
			->then(function (Bunny\Channel $channel): void {
				// Create exchange
				$this->initialize->registerExchange();

				// Create queue to connect to...
				$channel->queueDeclare(
					$this->exchangeConsumer->getQueueName(),
					false,
					true
				);

				// ...and bind it to the exchange
				foreach ($this->exchangeConsumer->getRoutingKeys(true) as $routingKey) {
					$channel->queueBind(
						$this->exchangeConsumer->getQueueName(),
						NodeLibs\Constants::RABBIT_MQ_MESSAGE_BUS_EXCHANGE_NAME,
						$routingKey
					);
				}

				$channel->consume(
					function (Bunny\Message $message, Bunny\Channel $channel, Bunny\Async\Client $client): void {
						$result = $this->exchangeConsumer->consume($message);

						switch ($result) {
							case NodeLibsConsumers\IExchangeConsumer::MESSAGE_ACK:
								$channel->ack($message); // Acknowledge message
								break;

							case NodeLibsConsumers\IExchangeConsumer::MESSAGE_NACK:
								$channel->nack($message); // Message will be re-queued
								break;

							case NodeLibsConsumers\IExchangeConsumer::MESSAGE_REJECT:
								$channel->reject($message, false); // Message will be discarded
								break;

							case NodeLibsConsumers\IExchangeConsumer::MESSAGE_REJECT_AND_TERMINATE:
								$channel->reject($message, false); // Message will be discarded
								$client->stop();
								break;

							default:
								throw new Exceptions\InvalidArgumentException('Unknown return value of message bus consumer');
						}

						$this->onConsumerMessage();
					},
					$this->exchangeConsumer->getQueueName()
				);
			});

		/**
		 * HTTP server
		 */

		try {
			$server = new Http\Server(function (ServerRequestInterface $request): ResponseInterface {
				$this->onRequest($request);

				$response = $this->router->handle($request);

				$this->onResponse($request, $response);

				return $response;
			});

			$socket = new Socket\Server('127.0.0.1:8000', $this->eventLoop);
			$server->listen($socket);

			if ($socket->getAddress() !== null) {
				$this->logger->debug(sprintf('[HTTP_SERVER] Listening on "%s"', str_replace('tcp:', 'http:', $socket->getAddress())));
			}

			$this->eventLoop->run();

		} catch (Throwable $ex) {
			if ($ex instanceof NodeLibsExceptions\TerminateException) {
				// Log terminate action reason
				$this->logger->warning('[TERMINATED] FB devices node - HTTP server', [
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
					'cmd'       => $this->getName(),
				]);

			} else {
				// Log error action reason
				$this->logger->error('[ERROR] FB devices node - HTTP server', [
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
					'cmd'       => $this->getName(),
				]);
			}

			$this->eventLoop->stop();
		}

		return 0;
	}

}