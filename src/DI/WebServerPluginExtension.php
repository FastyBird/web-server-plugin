<?php declare(strict_types = 1);

/**
 * WebServerPluginExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           21.03.20
 */

namespace FastyBird\WebServerPlugin\DI;

use FastyBird\WebServerPlugin\Application;
use FastyBird\WebServerPlugin\Commands;
use FastyBird\WebServerPlugin\Exceptions;
use FastyBird\WebServerPlugin\Http;
use FastyBird\WebServerPlugin\Middleware;
use Fig\Http\Message\RequestMethodInterface;
use IPub\SlimRouter;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;

/**
 * Simple web server extension container
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WebServerPluginExtension extends DI\CompilerExtension
{

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
		string $extensionName = 'fbWebServerPlugin'
	): void {
		$config->onCompile[] = function (
			Nette\Configurator $config,
			DI\Compiler $compiler
		) use (
			$extensionName,
			$cliMode
		): void {
			$compiler->addExtension($extensionName, new WebServerPluginExtension($cliMode));
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
			->setType(SlimRouter\Routing\Router::class);

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

}
