<?php declare(strict_types = 1);

/**
 * IConsoleApplication.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Application
 * @since          0.1.0
 *
 * @date           21.05.21
 */

namespace FastyBird\WebServer\Application;

use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

/**
 * Console application interface
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConsoleApplication
{

	/**
	 * @param Input\InputInterface|null $input
	 * @param Output\OutputInterface|null $output
	 *
	 * @return int
	 */
	public function run(?Input\InputInterface $input = null, ?Output\OutputInterface $output = null): int;

}
