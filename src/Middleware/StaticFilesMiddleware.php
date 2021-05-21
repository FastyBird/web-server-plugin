<?php declare(strict_types = 1);

/**
 * StaticFilesMiddleware.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Middleware
 * @since          0.1.0
 *
 * @date           08.05.21
 */

namespace FastyBird\WebServer\Middleware;

use FastyBird\WebServer\Exceptions;
use Narrowspark\MimeType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

/**
 * Public static files middleware
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StaticFilesMiddleware
{

	/** @var string|null */
	private ?string $publicRoot;

	/** @var bool */
	private bool $enabled;

	public function __construct(?string $publicRoot, bool $enabled = false)
	{
		$publicRoot = $publicRoot !== null ? realpath($publicRoot) : null;

		$this->publicRoot = $publicRoot === false ? null : $publicRoot;
		$this->enabled = $enabled;
	}

	public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
	{
		if ($this->publicRoot === null || !$this->enabled) {
			return $next($request);
		}

		$files = [
			$request->getUri()->getPath(),
			$request->getUri()->getPath() . '/index.html',
			$request->getUri()->getPath() . '/index.htm',
		];

		foreach ($files as $filePath) {
			$file = realpath($this->publicRoot . $filePath);

			if ($file !== false && file_exists($file) && !is_dir($file)) {
				$fileContents = file_get_contents($file);

				if ($fileContents === false) {
					throw new Exceptions\FileNotFoundException('Content of requested file could not be loaded');
				}

				$mimeType = MimeType\MimeTypeFileExtensionGuesser::guess($file);

				return new Response(200, ['Content-Type' => $mimeType ?? 'text/plain'], $fileContents);
			}
		}

		return $next($request);
	}

}
