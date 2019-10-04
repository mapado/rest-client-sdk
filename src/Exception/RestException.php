<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Exception;

use Psr\Http\Message\ResponseInterface;
use Throwable;

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

    public function __construct(
        string $message,
        string $path,
        array $params = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->path = $path;
        $this->params = $params;
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

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}
