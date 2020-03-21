<?php declare(strict_types = 1);

/**
 * ResponseEvent.php
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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\EventDispatcher;

/**
 * Processed response event
 *
 * @package        FastyBird:NodeWebServer!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class ResponseEvent extends EventDispatcher\Event
{

	/** @var ServerRequestInterface */
	private $request;

	/** @var ResponseInterface */
	private $response;

	public function __construct(
		ServerRequestInterface $request,
		ResponseInterface $response
	) {
		$this->request = $request;
		$this->response = $response;
	}

	/**
	 * @return ServerRequestInterface
	 */
	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}

	/**
	 * @return ResponseInterface
	 */
	public function getResponse(): ResponseInterface
	{
		return $this->response;
	}

}
