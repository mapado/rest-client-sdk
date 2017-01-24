<?php

namespace Mapado\RestClientSdk\Exception;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RestException
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
     * RestException constructor.
     *
     * @param string          $message
     * @param string          $path
     * @param array           $params
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(
        $message,
        $path,
        array $params = [],
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->path = $path;
        $this->params = $params;
        if ($previous instanceof RequestException) {
            $this->response = $previous->getResponse();
        }
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}
