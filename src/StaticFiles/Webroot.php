<?php declare(strict_types = 1);

/**
 * Webroot.php
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
use Narrowspark\MimeType;
use React\Filesystem;
use React\Promise;

final class Webroot
{

	/** @var Filesystem\FilesystemInterface */
	private Filesystem\FilesystemInterface $filesystem;

	/** @var string|null  */
	private ?string $publicRoot;

	public function __construct(Filesystem\FilesystemInterface $filesystem, ?string $publicRoot)
	{
		$this->filesystem = $filesystem;
		$this->publicRoot = $publicRoot;
	}

	/**
	 * @param string $path
	 *
	 * @return Promise\PromiseInterface
	 */
	public function file(string $path): Promise\PromiseInterface
	{
		if ($this->publicRoot === null) {
			throw new Exceptions\FileNotFoundException();
		}

		$file = $this->filesystem->file($this->publicRoot . $path);

		return $file
			->exists()
			->then(
				function () use ($file): Promise\PromiseInterface {
					return $this->readFile($file);
				},
				function (): void {
					throw new Exceptions\FileNotFoundException();
				}
			);
	}

	/**
	 * @param Filesystem\Node\FileInterface $file
	 *
	 * @return Promise\PromiseInterface
	 */
	private function readFile(Filesystem\Node\FileInterface $file): Promise\PromiseInterface
	{
		return $file->getContents()
			->then(function ($contents) use ($file): File {
				$mimeType = MimeType\MimeTypeFileExtensionGuesser::guess($file->getPath());

				return new File($contents, $mimeType ?? 'text/plain');
			});
	}

}
