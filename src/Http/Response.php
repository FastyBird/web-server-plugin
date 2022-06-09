<?php declare(strict_types = 1);

/**
 * Response.php
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

use FastyBird\WebServerPlugin\Exceptions;
use IPub\SlimRouter;

/**
 * Extended HTTP response
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Http
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Response extends SlimRouter\Http\Response
{

	/** @var mixed[] */
	protected array $attributes = [];

	/**
	 * @return mixed[]
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function getEntity(): ?AbstractEntity
	{
		$entity = $this->getAttribute(ResponseAttributes::ATTR_ENTITY, null);

		return $entity instanceof AbstractEntity ? $entity : null;
	}

	/**
	 * @param string $name
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getAttribute(string $name, $default = null)
	{
		if (!$this->hasAttribute($name)) {
			if (func_num_args() < 2) {
				throw new Exceptions\InvalidStateException(sprintf('No attribute "%s" found', $name));
			}

			return $default;
		}

		return $this->attributes[$name];
	}

	public function hasAttribute(string $name): bool
	{
		return array_key_exists($name, $this->attributes);
	}

	/**
	 * @param AbstractEntity $entity
	 *
	 * @return static
	 */
	public function withEntity(AbstractEntity $entity): self
	{
		return $this->withAttribute(ResponseAttributes::ATTR_ENTITY, $entity);
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return static
	 */
	public function withAttribute(string $name, $value): self
	{
		$new = clone $this;
		$new->attributes[$name] = $value;

		return $new;
	}

}
