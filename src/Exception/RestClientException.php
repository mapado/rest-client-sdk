<?php

namespace Mapado\RestClientSdk\Exception;

/**
 * Class SdkException
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RestClientException extends RestException
{
    public function getInitialMessage()
    {
        return json_decode((string)$this->getPrevious()->getResponse()->getBody(), true)['hydra:description'];
    }

    public function getInitialStatusCode()
    {
        return $this->getPrevious()->getCode();
    }
}
