<?php declare(strict_types = 1);

/**
 * ConsumerMessageEvent.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NodeWebServer!
 * @subpackage     Events
 * @since          0.1.0
 *
 * @date           21.03.20
 */

namespace FastyBird\NodeWebServer\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Processed consumer message event
 *
 * @package        FastyBird:NodeWebServer!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class ConsumerMessageEvent extends EventDispatcher\Event
{

}
