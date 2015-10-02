<?php

namespace Mapado\RestClientSdk\Exception;

/**
 * Class RestException
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RestException extends \RuntimeException
{
    private $path;

    private $params;

    public function __construct($message, $path, array $params = [], $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->path = $path;
        $this->params = $params;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getParams()
    {
        return $this->params;
    }
}
