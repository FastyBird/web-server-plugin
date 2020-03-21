<?php declare(strict_types = 1);

/**
 * RequestEvent.php
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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\EventDispatcher;

/**
 * Received request event
 *
 * @package        FastyBird:NodeWebServer!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class RequestEvent extends EventDispatcher\Event
{

	/** @var ServerRequestInterface */
	private $request;

	public function __construct(
		ServerRequestInterface $request
	) {
		$this->request = $request;
	}

	/**
	 * @return ServerRequestInterface
	 */
	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}

}
