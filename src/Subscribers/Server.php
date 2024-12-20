<?php declare(strict_types = 1);

/**
 * Server.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           15.04.20
 */

namespace FastyBird\Plugin\WebServer\Subscribers;

use Doctrine\DBAL;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Plugin\WebServer\Events;
use FastyBird\Plugin\WebServer\Exceptions;
use Symfony\Component\EventDispatcher;

/**
 * Database check subscriber
 *
 * @package         FastyBird:WebServerPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Server implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(private ToolsHelpers\Database $database)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\Startup::class => 'check',
			Events\Request::class => 'request',
			Events\Response::class => 'response',
		];
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidState
	 */
	public function check(): void
	{
		// Check if ping to DB is possible...
		if (!$this->database->ping()) {
			// ...if not, try to reconnect
			$this->database->reconnect();

			// ...and ping again
			if (!$this->database->ping()) {
				throw new Exceptions\InvalidState('Connection to database could not be established');
			}
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws ToolsExceptions\InvalidState
	 */
	public function request(): void
	{
		$this->database->reconnect();

		// Make sure we don't work with outdated entities
		$this->database->clear();
	}

	/**
	 * @throws ToolsExceptions\InvalidState
	 */
	public function response(): void
	{
		// Clearing Doctrine's entity manager allows
		// for more memory to be released by PHP
		$this->database->clear();
	}

}
