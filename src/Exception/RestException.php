<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Exception;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RestException
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RestException extends \RuntimeException
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
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

    public function __construct(
        string $message,
        string $path,
        array $params = [],
        int $code = 0,
        \Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->path = $path;
        $this->params = $params;
        if ($previous instanceof RequestException) {
            $this->response = $previous->getResponse();
            $this->request = $previous->getRequest();
        }
    }

    public function getPath(): string
    {
        return $this->path;
    }

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
