<?php
/**
 * console.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     bin
 * @since          0.1.0
 *
 * @date           08.03.20
 */

declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

exit(Nette\Bootstrap\Configurator::boot()
	->createContainer()
	->getByType(Contributte\Console\Application::class)
	->run());
