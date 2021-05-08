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

/**
 * Routes configurator
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IRoutes
{

	public function registerRoutes(SlimRouter\Routing\IRouter $router): void;

}
