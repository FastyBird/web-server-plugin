<?php declare(strict_types = 1);

/**
 * WebServerExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           21.03.20
 */

namespace FastyBird\WebServer\DI;

use FastyBird\WebServer\Application;
use FastyBird\WebServer\Commands;
use FastyBird\WebServer\Exceptions;
use FastyBird\WebServer\Http;
use FastyBird\WebServer\Middleware;
use FastyBird\WebServer\Router;
use Fig\Http\Message\RequestMethodInterface;
use IPub\SlimRouter;
use Nette;
use Nette\DI;
use Nette\Schema;
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

	/** @var bool */
	private bool $cliMode;

	public function __construct(bool $cliMode = false)
	{
		if (func_num_args() <= 0) {
			throw new Exceptions\InvalidArgumentException(sprintf('Provide CLI mode, e.q. %s(%%consoleMode%%).', self::class));
		}

		$this->cliMode = $cliMode;
	}

	/**
	 * @param Nette\Configurator $config
	 * @param bool $cliMode
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		bool $cliMode = false,
		string $extensionName = 'fbWebServer'
	): void {
		$config->onCompile[] = function (
			Nette\Configurator $config,
			DI\Compiler $compiler
		) use (
			$extensionName,
			$cliMode
		): void {
			$compiler->addExtension($extensionName, new WebServerExtension($cliMode));
		};
	}

	/**
	 * {@inheritDoc}
	 */
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'static' => Schema\Expect::structure([
				'webroot' => Schema\Expect::string(null)->nullable(),
				'enabled' => Schema\Expect::bool(false),
			]),
			'cors'   => Schema\Expect::structure([
				'enabled' => Schema\Expect::bool(false),
				'allow'   => Schema\Expect::structure([
					'origin'      => Schema\Expect::string('*'),
					'methods'     => Schema\Expect::arrayOf('string')
						->default([
							RequestMethodInterface::METHOD_GET,
							RequestMethodInterface::METHOD_POST,
							RequestMethodInterface::METHOD_PATCH,
							RequestMethodInterface::METHOD_DELETE,
							RequestMethodInterface::METHOD_OPTIONS,
						]),
					'credentials' => Schema\Expect::bool(true),
					'headers'     => Schema\Expect::arrayOf('string')
						->default([
							'Content-Type',
							'Authorization',
							'X-Requested-With',
						]),
				]),
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

		$builder->addDefinition($this->prefix('routing.responseFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Http\ResponseFactory::class);

		$builder->addDefinition($this->prefix('routing.router'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Router::class);

		$builder->addDefinition($this->prefix('command.server'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\HttpServerCommand::class);

		// Webserver middlewares
		$builder->addDefinition($this->prefix('middlewares.cors'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\CorsMiddleware::class)
			->setArguments([
				'enabled'          => $configuration->cors->enabled,
				'allowOrigin'      => $configuration->cors->allow->origin,
				'allowMethods'     => $configuration->cors->allow->methods,
				'allowCredentials' => $configuration->cors->allow->credentials,
				'allowHeaders'     => $configuration->cors->allow->headers,
			]);

		$builder->addDefinition($this->prefix('middlewares.staticFiles'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\StaticFilesMiddleware::class)
			->setArgument('publicRoot', $configuration->static->webroot)
			->setArgument('enabled', $configuration->static->enabled);

		$builder->addDefinition($this->prefix('middlewares.router'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\RouterMiddleware::class);

		// Applications

		if ($this->cliMode === true) {
			$builder->addDefinition($this->prefix('application.console'), new DI\Definitions\ServiceDefinition())
				->setType(Application\Console::class);
		}

		$builder->addDefinition($this->prefix('application.classic'), new DI\Definitions\ServiceDefinition())
			->setType(Application\Application::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		$routerServiceName = $builder->getByType(SlimRouter\Routing\IRouter::class, true);

		if ($routerServiceName !== null) {
			$routerService = $builder->getDefinition($routerServiceName);
			assert($routerService instanceof DI\Definitions\ServiceDefinition);

			/**
			 * ROUTES
			 */

			$routesConfigurationServices = $builder->findByType(Router\IRoutes::class);

			foreach ($routesConfigurationServices as $routesConfigurationService) {
				if ($routesConfigurationService instanceof DI\Definitions\ServiceDefinition) {
					$routerService->addSetup('registerRoutes', [$routesConfigurationService]);
				}
			}

			$routerService->addSetup('injectRoutes');

			/**
			 * ROUTER MIDDLEWARE
			 */

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

			foreach ($middlewareServices as $middlewareService => $middlewareServiceTags) {
				$routerService->addSetup('addMiddleware', [$builder->getDefinition($middlewareService)]);
			}
		}
	}

}
