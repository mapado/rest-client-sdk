<?php

namespace Mapado\RestClientSdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use Mapado\RestClientSdk\Exception\RestClientException;
use Mapado\RestClientSdk\Exception\RestException;

/**
 * Class RestClient
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RestClient
{
    /**
     * httpClient
     *
     * @var ClientInterface
     * @access private
     */
    private $httpClient;

    /**
     * baseUrl
     *
     * @var string
     * @access private
     */
    private $baseUrl;

    /**
     * logHistory
     *
     * @var boolean
     * @access private
     */
    private $logHistory;

    /**
     * requestHistory
     *
     * @var array
     * @access private
     */
    private $requestHistory;

    /**
     * @param ClientInterface $httpClient
     * @param string $baseUrl
     */
    public function __construct(ClientInterface $httpClient, $baseUrl = null)
    {
        $this->httpClient = $httpClient;
        $this->baseUrl      = $baseUrl;

        $this->logHistory = false;
        $this->requestHistory = [];
    }

    public function isHistoryLogged()
    {
        return $this->logHistory;
    }

    /**
     * setLogHistory
     *
     * @param boolean $logHistory
     * @access public
     * @return RestClient
     */
    public function setLogHistory($logHistory)
    {
        $this->logHistory = $logHistory;
        return $this;
    }

    public function getRequestHistory()
    {
        return $this->requestHistory;
    }

    /**
     * get a path
     * @param string $path
     *
     * @return array
     */
    public function get($path)
    {
        $requestUrl = $this->baseUrl . $path;

        try {
            return $this->executeRequest('GET', $requestUrl);
        } catch (ClientException $e) {
            return null;
        } catch (TransferException $e) {
            throw new RestException('Error while getting resource', $path, [], 1, $e);
        }
    }

    /**
     * delete
     *
     * @param string $path
     * @access public
     * @return void
     */
    public function delete($path)
    {
        try {
            $this->executeRequest('DELETE', $this->baseUrl . $path);
        } catch (ClientException $e) {
            return null;
        } catch (TransferException $e) {
            throw new RestException('Error while deleting resource', $path, [], 2, $e);
        }
    }

    public function post($path, $data)
    {
        try {
            return $this->executeRequest('POST', $this->baseUrl . $path, [
                'json' => $data
            ]);
        } catch (ClientException $e) {
            throw new RestClientException('Cannot create resource', $path, [], 3, $e);
        } catch (TransferException $e) {
            throw new RestException('Error while posting resource', $path, [], 4, $e);
        }
    }

    public function put($path, $data)
    {
        try {
            return $this->executeRequest('PUT', $this->baseUrl . $path, [
                'json' => $data
            ]);
        } catch (ClientException $e) {
            throw new RestClientException('Cannot update resource', $path, [], 5, $e);
        } catch (TransferException $e) {
            throw new RestException('Error while puting resource', $path, [], 6, $e);
        }
    }

    /**
     * executeRequest
     *
     * @param string $method
     * @param string $url
     * @param bool $parameters
     * @access private
     * @return Response|array
     */
    private function executeRequest($method, $url, $parameters = [])
    {
        $startTime = null;
        if ($this->isHistoryLogged()) {
            $startTime = microtime(true);
        }

        try {
            $response = $this->httpClient->request($method, $url, $parameters);
            $this->logRequest($startTime, $method, $url, $parameters, $response);
        } catch (TransferException $e) {
            $this->logRequest($startTime, $method, $url, $parameters);

            throw $e;
        }

        $headers = $response->getHeaders();
        if (isset($headers['Content-Type']) && $headers['Content-Type'][0] === 'application/ld+json') {
            return json_decode($response->getBody(), true);
        } else {
            return $response;
        }
    }

    /**
     * logRequest
     *
     * @param mixed $startTime
     * @param mixed $method
     * @param mixed $url
     * @param mixed $parameters
     * @param mixed $response
     * @access private
     * @return void
     */
    private function logRequest($startTime, $method, $url, $parameters, $response = null)
    {
        if ($this->isHistoryLogged()) {
            $queryTime = microtime(true) - $startTime;

            $this->requestHistory[] = [
                'method' => $method,
                'url' => $url,
                'parameters' => $parameters,
                'response' => $response,
                'responseBody' => $response ? json_decode($response->getBody(), true) : null,
                'queryTime' => $queryTime,
            ];
        }
    }
}
