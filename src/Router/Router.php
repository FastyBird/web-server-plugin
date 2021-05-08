<?php declare(strict_types = 1);

/**
 * IRoutes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Router
 * @since          0.1.0
 *
 * @date           14.12.20
 */

namespace FastyBird\WebServer\Router;

use IPub\SlimRouter;
use Psr\Http\Message\ResponseFactoryInterface;
use SplObjectStorage;

/**
 * Routes configurator
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Router extends SlimRouter\Routing\Router
{

	/** @var SplObjectStorage<IRoutes, null> */
	private SplObjectStorage $routes;

	public function __construct(
		?ResponseFactoryInterface $responseFactory = null,
		?SlimRouter\Controllers\IControllerResolver $controllerResolver = null
	) {
		parent::__construct($responseFactory, $controllerResolver);

		$this->routes = new SplObjectStorage();
	}

	/**
	 * @param IRoutes $routes
	 *
	 * @return void
	 */
	public function registerRoutes(IRoutes $routes): void
	{
		if (!$this->routes->contains($routes)) {
			$this->routes->attach($routes);
		}
	}

	/**
	 * @return void
	 */
	public function injectRoutes(): void
	{
		$this->routes->rewind();

		foreach ($this->routes as $routes) {
			$routes->registerRoutes($this);
		}
	}

}
