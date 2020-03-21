<?php declare(strict_types = 1);

/**
 * JsonApiMiddleware.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NodeWebServer!
 * @subpackage     Controllers
 * @since          0.1.0
 *
 * @date           17.04.19
 */

namespace FastyBird\NodeWebServer\Middleware;

use FastyBird\NodeWebServer\Exceptions;
use FastyBird\NodeWebServer\Http;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use IPub\SlimRouter;
use Neomerx\JsonApi;
use Neomerx\JsonApi\Contracts;
use Neomerx\JsonApi\Schema;
use Nette\DI;
use Nette\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class JsonApiMiddleware implements MiddlewareInterface
{

	private const LINK_SELF = Contracts\Schema\DocumentInterface::KEYWORD_SELF;
	private const LINK_RELATED = Contracts\Schema\DocumentInterface::KEYWORD_RELATED;
	private const LINK_FIRST = Contracts\Schema\DocumentInterface::KEYWORD_FIRST;
	private const LINK_LAST = Contracts\Schema\DocumentInterface::KEYWORD_LAST;
	private const LINK_NEXT = Contracts\Schema\DocumentInterface::KEYWORD_NEXT;
	private const LINK_PREV = Contracts\Schema\DocumentInterface::KEYWORD_PREV;

	/** @var string|string[] */
	private $metaAuthor;

	/** @var string */
	private $metaCopyright;

	/** @var Http\ResponseFactory */
	private $responseFactory;

	/** @var LoggerInterface */
	private $logger;

	/** @var DI\Container */
	private $container;

	/**
	 * @param Http\ResponseFactory $responseFactory
	 * @param LoggerInterface $logger
	 * @param DI\Container $container
	 * @param string|string[] $metaAuthor
	 * @param string $metaCopyright
	 */
	public function __construct(
		Http\ResponseFactory $responseFactory,
		LoggerInterface $logger,
		DI\Container $container,
		$metaAuthor,
		string $metaCopyright
	) {
		$this->responseFactory = $responseFactory;
		$this->logger = $logger;
		$this->container = $container;

		$this->metaAuthor = $metaAuthor;
		$this->metaCopyright = $metaCopyright;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 *
	 * @return ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			$response = $handler->handle($request);

			if ($response instanceof Http\Response) {
				$entity = $response->getEntity();

				if ($entity instanceof Http\ScalarEntity) {
					$encoder = $this->getEncoder();

					$links = [
						self::LINK_SELF => new Schema\Link(false, $this->uriToString($request->getUri()), false),
					];

					$meta = $this->getBaseMeta();

					if ($response->hasAttribute(Http\ResponseAttributes::ATTR_TOTAL_COUNT)) {
						$meta = array_merge($meta, [
							'totalCount' => $response->getAttribute(Http\ResponseAttributes::ATTR_TOTAL_COUNT),
						]);

						if (array_key_exists('page', $request->getQueryParams())) {
							$queryParams = $request->getQueryParams();

							$pageOffset = isset($queryParams['page']['offset']) ? (int) $queryParams['page']['offset'] : null;
							$pageLimit = isset($queryParams['page']['limit']) ? (int) $queryParams['page']['limit'] : null;

						} else {
							$pageOffset = null;
							$pageLimit = null;
						}

						if ($pageOffset !== null && $pageLimit !== null) {
							$lastPage = (int) round($response->getAttribute(Http\ResponseAttributes::ATTR_TOTAL_COUNT) / $pageLimit) * $pageLimit;

							if ($lastPage === $response->getAttribute(Http\ResponseAttributes::ATTR_TOTAL_COUNT)) {
								$lastPage = $response->getAttribute(Http\ResponseAttributes::ATTR_TOTAL_COUNT) - $pageLimit;
							}

							$uri = $request->getUri();

							$uriSelf = $uri->withQuery($this->buildPageQuery($pageOffset, $pageLimit));
							$uriFirst = $uri->withQuery($this->buildPageQuery(0, $pageLimit));
							$uriLast = $uri->withQuery($this->buildPageQuery($lastPage, $pageLimit));
							$uriPrev = $uri->withQuery($this->buildPageQuery(($pageOffset - $pageLimit), $pageLimit));
							$uriNext = $uri->withQuery($this->buildPageQuery(($pageOffset + $pageLimit), $pageLimit));

							$links = array_merge($links, [
								self::LINK_SELF  => new Schema\Link(false, $this->uriToString($uriSelf), false),
								self::LINK_FIRST => new Schema\Link(false, $this->uriToString($uriFirst), false),
							]);

							if (($pageOffset - 1) >= 0) {
								$links = array_merge($links, [
									self::LINK_PREV => new Schema\Link(false, $this->uriToString($uriPrev), false),
								]);
							}

							if ((($response->getAttribute(Http\ResponseAttributes::ATTR_TOTAL_COUNT) - $pageLimit) - ($pageOffset + $pageLimit)) >= 0) {
								$links = array_merge($links, [
									self::LINK_NEXT => new Schema\Link(false, $this->uriToString($uriNext), false),
								]);
							}

							$links = array_merge($links, [
								self::LINK_LAST => new Schema\Link(false, $this->uriToString($uriLast), false),
							]);
						}
					}

					$encoder->withMeta($meta);

					$encoder->withLinks($links);

					if (Utils\Strings::contains($request->getUri()->getPath(), '/relationships/')) {
						$uriRelated = $request->getUri();

						$encoder->withLinks(array_merge($links, [
							self::LINK_RELATED => new Schema\Link(false, str_replace('/relationships/', '/', $this->uriToString($uriRelated)), false),
						]));

						$content = $encoder->encodeIdentifiers($entity->getData());

					} else {
						if (array_key_exists('include', $request->getQueryParams())) {
							$encoder->withIncludedPaths(explode(',', $request->getQueryParams()['include']));
						}

						$content = $encoder->encodeData($entity->getData());
					}

					$response->getBody()->write($content);
				}
			}

		} catch (Throwable $ex) {
			$response = $this->responseFactory->createResponse();

			if ($ex instanceof Exceptions\IJsonApiException) {
				$response->withStatus($ex->getCode());

				if ($ex instanceof Exceptions\JsonApiErrorException) {
					$content = $this->getEncoder()->encodeError($ex->getError());

					$response->getBody()->write($content);

				} elseif ($ex instanceof Exceptions\JsonApiMultipleErrorException) {
					$content = $this->getEncoder()->encodeErrors($ex->getErrors());

					$response->getBody()->write($content);
				}

			} elseif ($ex instanceof SlimRouter\Exceptions\HttpException) {
				$response = $response->withStatus($ex->getCode());

				$content = $this->getEncoder()->encodeError(new Schema\Error(
					null,
					null,
					null,
					(string) $ex->getCode(),
					(string) $ex->getCode(),
					$ex->getTitle(),
					$ex->getDescription()
				));

				$response->getBody()->write($content);

			} else {
				$this->logger->error('[JSON:API_MIDDLEWARE] An error occurred during request handling', [
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
				]);

				$response = $response->withStatus(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

				$content = $this->getEncoder()->encodeError(new Schema\Error(
					null,
					null,
					null,
					(string) StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
					(string) $ex->getCode(),
					'Server error',
					'There was an server error, please try again later'
				));

				$response->getBody()->write($content);
			}
		}

		$allowedMethods = [
			RequestMethodInterface::METHOD_GET,
			RequestMethodInterface::METHOD_POST,
			RequestMethodInterface::METHOD_PATCH,
			RequestMethodInterface::METHOD_DELETE,
			RequestMethodInterface::METHOD_OPTIONS,
		];

		// Setup content type
		return $response
			// CORS headers
			->withHeader('Access-Control-Allow-Origin', '*')
			->withHeader('Access-Control-Allow-Methods', implode(',', $allowedMethods))
			->withHeader('Access-Control-Allow-Credentials', 'true')
			->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Api-Key')
			// Content headers
			->withHeader('Content-Type', Contracts\Http\Headers\MediaTypeInterface::JSON_API_MEDIA_TYPE);
	}

	/**
	 * @return JsonApi\Encoder\Encoder
	 */
	private function getEncoder(): JsonApi\Encoder\Encoder
	{
		$encoder = new JsonApi\Encoder\Encoder(
			new JsonApi\Factories\Factory(),
			$this->container->getByType(Contracts\Schema\SchemaContainerInterface::class)
		);

		$encoder->withEncodeOptions(JSON_PRETTY_PRINT);

		$encoder->withJsonApiVersion(Contracts\Encoder\EncoderInterface::JSON_API_VERSION);

		return $encoder;
	}

	/**
	 * @return mixed[]
	 */
	private function getBaseMeta(): array
	{
		$meta = [];

		if ($this->metaAuthor !== null) {
			if (is_array($this->metaAuthor)) {
				$meta['authors'] = $this->metaAuthor;

			} else {
				$meta['author'] = $this->metaAuthor;
			}
		}

		if ($this->metaCopyright !== null) {
			$meta['copyright'] = $this->metaCopyright;
		}

		return $meta;
	}

	/**
	 * @param UriInterface $uri
	 *
	 * @return string
	 */
	private function uriToString(UriInterface $uri): string
	{
		$result = '';

		// Add a leading slash if necessary.
		if (substr($uri->getPath(), 0, 1) !== '/') {
			$result .= '/';
		}

		$result .= $uri->getPath();

		if ($uri->getQuery() !== '') {
			$result .= '?' . $uri->getQuery();
		}

		if ($uri->getFragment() !== '') {
			$result .= '#' . $uri->getFragment();
		}

		return $result;
	}

	/**
	 * @param int $offset
	 * @param int|string $limit
	 *
	 * @return string
	 */
	private function buildPageQuery(int $offset, $limit): string
	{
		$query = [
			'page' => [
				'offset' => $offset,
				'limit'  => $limit,
			],
		];

		return http_build_query($query);
	}

}
