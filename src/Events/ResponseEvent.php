<?php declare(strict_types = 1);

/**
 * ResponseEvent.php
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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\EventDispatcher;

/**
 * Http response event
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ResponseEvent extends EventDispatcher\Event
{

	/** @var ServerRequestInterface */
	private ServerRequestInterface $request;

	/** @var ResponseInterface */
	private ResponseInterface $response;

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
