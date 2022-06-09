<?php declare(strict_types = 1);

/**
 * ScalarEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Http
 * @since          0.1.0
 *
 * @date           17.03.20
 */

namespace FastyBird\WebServerPlugin\Http;

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
