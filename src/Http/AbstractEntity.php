<?php declare(strict_types = 1);

/**
 * AbstractEntity.php
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

abstract class AbstractEntity
{

	/** @var mixed */
	protected $data;

	/**
	 * @param mixed $data
	 */
	public function __construct($data = null)
	{
		$this->data = $data;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param mixed $data
	 */
	protected function setData($data): void
	{
		$this->data = $data;
	}

}
