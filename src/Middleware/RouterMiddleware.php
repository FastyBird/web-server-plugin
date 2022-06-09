<?php declare(strict_types = 1);

/**
 * RouterMiddleware.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Middleware
 * @since          0.1.0
 *
 * @date           08.05.21
 */

namespace FastyBird\WebServerPlugin\Middleware;

use FastyBird\WebServerPlugin\Events;
use IPub\SlimRouter\Routing;
use Psr\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application router middleware
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class RouterMiddleware
{

	/** @var EventDispatcher\EventDispatcherInterface|null */
	private ?EventDispatcher\EventDispatcherInterface $dispatcher;

	/** @var Routing\IRouter */
	private Routing\IRouter $router;

	public function __construct(
		Routing\IRouter $router,
		?EventDispatcher\EventDispatcherInterface $dispatcher = null
	) {
		$this->router = $router;
		$this->dispatcher = $dispatcher;
	}

	public function __invoke(ServerRequestInterface $request): ResponseInterface
	{
		if ($this->dispatcher !== null) {
			$this->dispatcher->dispatch(new Events\RequestEvent($request));
		}

		$response = $this->router->handle($request);

		if ($this->dispatcher !== null) {
			$this->dispatcher->dispatch(new Events\ResponseEvent($request, $response));
		}

		return $response;
	}

}
