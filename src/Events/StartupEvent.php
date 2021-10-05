<?php declare(strict_types = 1);

/**
 * StartupEvent.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Events
 * @since          0.3.0
 *
 * @date           05.10.21
 */

namespace FastyBird\WebServer\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * After message consumed event
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StartupEvent extends EventDispatcher\Event
{

}
