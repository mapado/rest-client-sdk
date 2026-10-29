<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Exception;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RestException extends \RuntimeException
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array<mixed>
     */
    private $params;

    /**
     * @var ResponseInterface|null
     */
    private $response;

    /**
     * @var RequestInterface|null
     */
    private $request;

    /** @param array<mixed> $params */
    public function __construct(
        string $message,
        string $path,
        array $params = [],
        int $code = 0,
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->path = $path;
        $this->params = $params;
        if ($previous instanceof RequestException) {
            $this->request = $previous->getRequest();
            $this->response = self::responseOf($previous);
        }
    }

    /**
     * Guzzle 7 keeps the response on RequestException,
     * Guzzle 8 moves it to ResponseException (absent in Guzzle 7)
     */
    public static function responseOf(\Throwable $e): ?ResponseInterface
    {
        if (!$e instanceof RequestException) {
            return null;
        }

        $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;

        return $response instanceof ResponseInterface ? $response : null;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /** @return array<mixed> */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }
}
