<?php declare(strict_types = 1);

/**
 * WebServerExtension.php
 *
 * @license        More in license.md
 * @copyright      https://fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           21.03.20
 */

namespace FastyBird\WebServer\DI;

use FastyBird\WebServer\Commands;
use FastyBird\WebServer\Http;
use IPub\SlimRouter;
use Nette;
use Nette\DI;
use Nette\Schema;
use React\EventLoop;
use stdClass;

/**
 * Simple web server extension container
 *
 * @package        FastyBird:WebServer!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WebServerExtension extends DI\CompilerExtension
{

	public const ROUTER_MIDDLEWARE_TAG = 'middleware';

	/**
	 * {@inheritDoc}
	 */
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'server' => Schema\Expect::structure([
				'address' => Schema\Expect::string('127.0.0.1'),
				'port'    => Schema\Expect::int(8000),
			]),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $configuration */
		$configuration = $this->getConfig();

		$builder->addDefinition(null)
			->setType(Http\ResponseFactory::class);

		$builder->addDefinition(null)
			->setType(EventLoop\LoopInterface::class)
			->setFactory('React\EventLoop\Factory::create');

		$builder->addDefinition(null)
			->setType(Commands\HttpServerCommand::class)
			->setArgument('address', $configuration->server->address)
			->setArgument('port', $configuration->server->port);
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * ROUTER MIDDLEWARE
		 */

		if ($builder->getByType(SlimRouter\Routing\IRouter::class) === null) {
			$builder->addDefinition(null)
				->setType(SlimRouter\Routing\Router::class);
		}

		$middlewareServices = $builder->findByTag(self::ROUTER_MIDDLEWARE_TAG);

		// Sort by priority
		uasort($middlewareServices, function (array $a, array $b): int {
			$p1 = $a['priority'] ?? 10;
			$p2 = $b['priority'] ?? 10;

			if ($p1 === $p2) {
				return 0;
			}

			return ($p1 < $p2) ? -1 : 1;
		});

		$routerServiceName = $builder->getByType(SlimRouter\Routing\IRouter::class, true);

		if ($routerServiceName !== null) {
			$routerService = $builder->getDefinition($routerServiceName);
			assert($routerService instanceof DI\Definitions\ServiceDefinition);

			foreach ($middlewareServices as $middlewareService => $middlewareServiceTags) {
				$routerService->addSetup('addMiddleware', [$builder->getDefinition($middlewareService)]);
			}
		}
	}

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'nodeWebServer'
	): void {
		$config->onCompile[] = function (
			Nette\Configurator $config,
			DI\Compiler $compiler
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new WebServerExtension());
		};
	}

}
