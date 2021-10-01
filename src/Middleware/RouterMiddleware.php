<?php declare(strict_types = 1);

/**
 * RouterMiddleware.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Middleware
 * @since          0.1.0
 *
 * @date           08.05.21
 */

namespace FastyBird\WebServer\Middleware;

use FastyBird\ApplicationExchange\Events as ApplicationExchangeEvents;
use IPub\SlimRouter\Routing;
use Psr\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application router middleware
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class RouterMiddleware
{

	/** @var EventDispatcher\EventDispatcherInterface */
	private EventDispatcher\EventDispatcherInterface $dispatcher;

	/** @var Routing\IRouter */
	private Routing\IRouter $router;

	public function __construct(
		Routing\IRouter $router,
		EventDispatcher\EventDispatcherInterface $dispatcher
	) {
		$this->router = $router;
		$this->dispatcher = $dispatcher;
	}

	public function __invoke(ServerRequestInterface $request): ResponseInterface
	{
		$this->dispatcher->dispatch(new ApplicationExchangeEvents\RequestEvent($request));

		$response = $this->router->handle($request);

		$this->dispatcher->dispatch(new ApplicationExchangeEvents\ResponseEvent($request, $response));

		return $response;
	}

}
