<?php

namespace Mapado\RestClientSdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use Mapado\RestClientSdk\Exception\RestClientException;
use Mapado\RestClientSdk\Exception\RestException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RestClient
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RestClient
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var ?string
     */
    private $baseUrl;

    /**
     * @var bool
     */
    private $logHistory;

    /**
     * @var array
     */
    private $requestHistory;

    /**
     * @var ?Request
     */
    private $currentRequest;

    public function __construct(
        ClientInterface $httpClient,
        ?string $baseUrl = null
    ) {
        $this->httpClient = $httpClient;
        $this->baseUrl = (null !== $baseUrl && '/' === substr($baseUrl, -1))
            ? substr($baseUrl, 0, -1)
            : $baseUrl;
        $this->logHistory = false;
        $this->requestHistory = [];
    }

    public function isHistoryLogged(): bool
    {
        return $this->logHistory;
    }

    public function setCurrentRequest(Request $currentRequest): self
    {
        $this->currentRequest = $currentRequest;

        return $this;
    }

    public function setLogHistory(bool $logHistory): self
    {
        $this->logHistory = $logHistory;

        return $this;
    }

    public function getRequestHistory(): array
    {
        return $this->requestHistory;
    }

    /**
     * get a path
     *
     * @return array|ResponseInterface|null
     *
     * @throws RestException
     */
    public function get(string $path, array $parameters = [])
    {
        $requestUrl = $this->baseUrl . $path;
        try {
            return $this->executeRequest('GET', $requestUrl, $parameters);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            if (null !== $response && 404 === $response->getStatusCode()) {
                return null;
            }
            throw new RestClientException(
                'Error while getting resource',
                $path,
                [],
                7,
                $e
            );
        } catch (TransferException $e) {
            throw new RestException(
                'Error while getting resource',
                $path,
                [],
                1,
                $e
            );
        }
    }

    /**
     * @throws RestException
     */
    public function delete(string $path): void
    {
        try {
            $this->executeRequest('DELETE', $this->baseUrl . $path);
        } catch (ClientException $e) {
            return;
        } catch (TransferException $e) {
            throw new RestException(
                'Error while deleting resource',
                $path,
                [],
                2,
                $e
            );
        }
    }

    /**
     * @return array|ResponseInterface
     *
     * @throws RestClientException
     * @throws RestException
     */
    public function post(string $path, array $data, array $parameters = [])
    {
        $parameters['json'] = $data;
        try {
            return $this->executeRequest(
                'POST',
                $this->baseUrl . $path,
                $parameters
            );
        } catch (ClientException $e) {
            throw new RestClientException(
                'Cannot create resource',
                $path,
                [],
                3,
                $e
            );
        } catch (TransferException $e) {
            throw new RestException(
                'Error while posting resource',
                $path,
                [],
                4,
                $e
            );
        }
    }

    /**
     * @return array|ResponseInterface
     *
     * @throws RestClientException
     * @throws RestException
     */
    public function put(string $path, array $data, array $parameters = [])
    {
        $parameters['json'] = $data;

        try {
            return $this->executeRequest(
                'PUT',
                $this->baseUrl . $path,
                $parameters
            );
        } catch (ClientException $e) {
            throw new RestClientException(
                'Cannot update resource',
                $path,
                [],
                5,
                $e
            );
        } catch (TransferException $e) {
            throw new RestException(
                'Error while puting resource',
                $path,
                [],
                6,
                $e
            );
        }
    }

    /**
     * Merge default parameters.
     */
    protected function mergeDefaultParameters(array $parameters): array
    {
        $request = $this->getCurrentRequest();

        $defaultParameters = ['version' => '1.0'];
        $defaultParameters['headers'] = ['Referer' => $request->getUri()];

        return array_replace_recursive($defaultParameters, $parameters);
    }

    protected function getCurrentRequest(): Request
    {
        if (!$this->currentRequest) {
            $this->currentRequest = Request::createFromGlobals();
        }

        return $this->currentRequest;
    }

    /**
     * Executes request.
     *
     * @return ResponseInterface|array
     *
     * @throws TransferException
     */
    private function executeRequest(
        string $method,
        string $url,
        array $parameters = []
    ) {
        $parameters = $this->mergeDefaultParameters($parameters);

        $startTime = null;
        if ($this->isHistoryLogged()) {
            $startTime = microtime(true);
        }

        try {
            $response = $this->httpClient->request($method, $url, $parameters);
            $this->logRequest(
                $startTime,
                $method,
                $url,
                $parameters,
                $response
            );
        } catch (RequestException $e) {
            $this->logRequest(
                $startTime,
                $method,
                $url,
                $parameters,
                $e->getResponse()
            );
            throw $e;
        } catch (TransferException $e) {
            $this->logRequest($startTime, $method, $url, $parameters);
            throw $e;
        }

        $headers = $response->getHeaders();
        $jsonContentTypeList = ['application/ld+json', 'application/json'];

        $requestIsJson = false;

        if (isset($headers['Content-Type'])) {
            foreach ($jsonContentTypeList as $contentType) {
                if (
                    false !== stripos($headers['Content-Type'][0], $contentType)
                ) {
                    $requestIsJson = true;
                    break;
                }
            }
        }

        if ($requestIsJson) {
            return json_decode($response->getBody(), true);
        } else {
            return $response;
        }
    }

    private function logRequest(
        ?float $startTime,
        string $method,
        string $url,
        array $parameters,
        ?ResponseInterface $response = null
    ): void {
        if ($this->isHistoryLogged()) {
            $queryTime = microtime(true) - $startTime;

            $this->requestHistory[] = [
                'method' => $method,
                'url' => $url,
                'parameters' => $parameters,
                'response' => $response,
                'responseBody' => $response
                    ? json_decode($response->getBody(), true)
                    : null,
                'queryTime' => $queryTime,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            ];
        }
    }
}
