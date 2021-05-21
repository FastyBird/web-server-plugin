<?php declare(strict_types = 1);

/**
 * IApplication.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Application
 * @since          0.1.0
 *
 * @date           23.02.21
 */

namespace FastyBird\WebServer\Application;

use Psr\Http\Message\ResponseInterface;

/**
 * Base application interface
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IApplication
{

	/**
	 * Dispatch application!
	 *
	 * @return string|int|bool|void|ResponseInterface|null
	 */
	public function run();

}
