<?php declare(strict_types = 1);

/**
 * File.php
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

final class File
{

	/** @var string */
	private string $contents;

	/** @var string */
	private string $mimeType;

	public function __construct(string $contents, string $mimeType)
	{
		$this->contents = $contents;
		$this->mimeType = $mimeType;
	}

	/**
	 * @return string
	 */
	public function getContents(): string
	{
		return $this->contents;
	}

	/**
	 * @return string
	 */
	public function getMimeType(): string
	{
		return $this->mimeType;
	}

}
