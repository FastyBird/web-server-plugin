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

use Neomerx\JsonApi;

/**
 * Json:API schemas container
 *
 * @package        FastyBird:NodeWebServer!
 * @subpackage     JsonApi
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class JsonApiSchemaContainer extends JsonApi\Schema\SchemaContainer
{

	public function __construct()
	{
		parent::__construct(new JsonApi\Factories\Factory(), []);
	}

	/**
	 * @param ISchema $schema
	 *
	 * @return void
	 */
	public function add(ISchema $schema): void
	{
		$this->setProviderMapping($schema->getEntityClass(), get_class($schema));
		$this->setResourceToJsonTypeMapping($schema->getType(), $schema->getEntityClass());
		$this->setCreatedProvider($schema->getEntityClass(), $schema);
	}

}
