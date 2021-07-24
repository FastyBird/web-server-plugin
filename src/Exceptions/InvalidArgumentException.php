<?php declare(strict_types = 1);

/**
 * InvalidArgumentException.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Exceptions
 * @since          0.1.1
 *
 * @date           24.07.21
 */

namespace FastyBird\WebServer\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class InvalidArgumentException extends PHPInvalidArgumentException implements IException
{

}
