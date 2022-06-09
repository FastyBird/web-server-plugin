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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * CORS middleware
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CorsMiddleware
{

	/** @var bool */
	private bool $enabled;

	/** @var string */
	private string $allowOrigin;

	/** @var string[] */
	private array $allowMethods;

	/** @var bool */
	private bool $allowCredentials;

	/** @var string[] */
	private array $allowHeaders;

	/**
	 * @param bool $enabled
	 * @param string $allowOrigin
	 * @param string[] $allowMethods
	 * @param bool $allowCredentials
	 * @param string[] $allowHeaders
	 */
	public function __construct(
		bool $enabled,
		string $allowOrigin,
		array $allowMethods,
		bool $allowCredentials,
		array $allowHeaders
	) {
		$this->enabled = $enabled;
		$this->allowOrigin = $allowOrigin;
		$this->allowMethods = $allowMethods;
		$this->allowCredentials = $allowCredentials;
		$this->allowHeaders = $allowHeaders;
	}

	public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
	{
		$response = $next($request);

		if (!$this->enabled) {
			return $response;
		}

		// Setup content type
		return $response
			// CORS headers
			->withHeader('Access-Control-Allow-Origin', $this->allowOrigin)
			->withHeader('Access-Control-Allow-Methods', implode(',', $this->allowMethods))
			->withHeader('Access-Control-Allow-Credentials', $this->allowCredentials ? 'true' : 'false')
			->withHeader('Access-Control-Allow-Headers', implode(',', $this->allowHeaders));
	}

}
