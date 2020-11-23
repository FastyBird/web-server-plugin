<?php declare(strict_types = 1);

/**
 * ScalarEntity.php
 *
 * @license        More in license.md
 * @copyright      https://fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Http
 * @since          0.1.0
 *
 * @date           17.03.20
 */

namespace FastyBird\WebServer\Http;

final class ScalarEntity extends AbstractEntity
{

	/**
	 * @param mixed $value
	 */
	public function __construct($value)
	{
		parent::__construct($value);
	}

	/**
	 * @param mixed $value
	 *
	 * @return static
	 */
	public static function from($value): self
	{
		return new static($value);
	}

}
