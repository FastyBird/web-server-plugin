<?php declare(strict_types = 1);

/**
 * Controller.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     StaticFiles
 * @since          0.1.0
 *
 * @date           08.05.21
 */

namespace FastyBird\WebServer\StaticFiles;

use FastyBird\WebServer\Exceptions;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;

final class Controller
{

	/** @var Webroot */
	private Webroot $webroot;

	public function __construct(Webroot $webroot)
	{
		$this->webroot = $webroot;
	}

	public function __invoke(ServerRequestInterface $request): PromiseInterface
	{
		return $this->webroot->file($request->getUri()->getPath())
			->then(
				function (File $file): Response {
					return new Response(200, ['Content-Type' => $file->getMimeType()], $file->getContents());
				},
				function (Exceptions\FileNotFoundException $ex): void {
					throw $ex;
				}
			);
	}

}
