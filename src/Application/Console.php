<?php declare(strict_types = 1);

/**
 * ConsoleApplication.php
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

use Contributte\Console\Application;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;

/**
 * Console application
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Console implements IConsole
{

	/** @var Application */
	private Application $application;

	public function __construct(
		Application $application
	) {
		$this->application = $application;
	}

	/**
	 * @param Input\InputInterface|null $input
	 * @param Output\OutputInterface|null $output
	 *
	 * @return int
	 *
	 * @throws Throwable
	 */
	public function run(?Input\InputInterface $input = null, ?Output\OutputInterface $output = null): int
	{
		if ($input === null) {
			$input = new Input\ArrayInput([
				'command' => 'fb:web-server:start',
			]);

		} else {
			$input->setArgument('command', 'fb:web-server:start');
		}

		return $this->application->run($input, $output);
	}

}
