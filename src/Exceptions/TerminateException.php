<?php declare(strict_types = 1);

/**
 * TerminateException.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           05.12.20
 */

namespace FastyBird\WebServerPlugin\Exceptions;

use Exception;

class TerminateException extends Exception implements IException
{

}
