<?php declare(strict_types = 1);

/**
 * InvalidStateException.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NodeWebServer!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           10.03.20
 */

namespace FastyBird\NodeWebServer\Exceptions;

use FastyBird\NodeLibs\Exceptions as NodeLibsExceptions;

class InvalidStateException extends NodeLibsExceptions\InvalidStateException implements IException
{

}
