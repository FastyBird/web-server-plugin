<?php declare(strict_types = 1);

/**
 * TerminateException.php
 *
 * @license        More in license.md
 * @copyright      https://fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           05.12.20
 */

namespace FastyBird\WebServer\Exceptions;

use Exception;

class TerminateException extends Exception implements IException
{

}
