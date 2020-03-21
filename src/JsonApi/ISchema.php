<?php declare(strict_types = 1);

/**
 * JsonApiSchemaContainer.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NodeWebServer!
 * @subpackage     JsonApi
 * @since          0.1.0
 *
 * @date           13.03.20
 */

namespace FastyBird\NodeWebServer\JsonApi;

use Neomerx\JsonApi\Contracts;

interface ISchema extends Contracts\Schema\SchemaInterface
{

	/**
	 * @return string
	 */
	public function getEntityClass(): string;

}
