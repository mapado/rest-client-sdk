<?php

declare(strict_types=1);

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
 * @phpstan-type RequestHistory array{
 *   'method': string,
 *   'url': string,
 *   'parameters': array<mixed>,
 *   'response': ?ResponseInterface,
 *   'responseBody': ?mixed,
 *   'queryTime': float,
 *   'backtrace': array<mixed>,
 * }
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
     * @var array<array<mixed>>
     * @phpstan-var array<RequestHistory>
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
        $this->baseUrl =
            null !== $baseUrl && '/' === mb_substr($baseUrl, -1)
                ? mb_substr($baseUrl, 0, -1)
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

    /** 
     * @return array<array<mixed>>
     * @phpstan-return array<RequestHistory>
     */
    public function getRequestHistory(): array
    {
        return $this->requestHistory;
    }

    /**
     * get a path
     *
     * @param array<mixed> $parameters
     * @return array<mixed>|ResponseInterface|null
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
     * @param array<mixed> $data
     * @param array<mixed> $parameters
     * @return array<mixed>|ResponseInterface
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
     * @param array<mixed> $data
     * @param array<mixed> $parameters
     * @return array<mixed>|ResponseInterface
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
     * 
     * @param array<mixed> $parameters
     * 
     * @return array<mixed>
     */
    protected function mergeDefaultParameters(array $parameters): array
    {
        $request = $this->getCurrentRequest();

        $defaultParameters = ['version' => '1.0'];
        if (null !== $request) {
            $defaultParameters['headers'] = ['Referer' => $request->getUri()];
        }

        /** @var array<mixed>|null $out */
        $out = array_replace_recursive($defaultParameters, $parameters);

        if (null === $out) {
            throw new \RuntimeException(
                sprintf(
                    'Error while calling array_replace_recursive in %s. This should not happen.',
                    __METHOD__
                )
            );
        }

        return $out;
    }

    protected function getCurrentRequest(): ?Request
    {
        if ('cli' === \PHP_SAPI) {
            // we are in cli mode, do not bother to get request
            return null;
        }

        if (!$this->currentRequest) {
            $this->currentRequest = Request::createFromGlobals();
        }

        return $this->currentRequest;
    }

    /**
     * Executes request.
     *
     * @param array<mixed> $parameters
     * @return ResponseInterface|array<mixed>
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

        $responseContentType =
            $headers['Content-Type'] ?? $headers['content-type'] ?? null;
        if ($responseContentType) {
            foreach ($jsonContentTypeList as $contentType) {
                if (
                    false !== mb_stripos($responseContentType[0], $contentType)
                ) {
                    $requestIsJson = true;
                    break;
                }
            }
        }

        if ($requestIsJson) {
            /** @var array<mixed> $decodedJson */
            $decodedJson = json_decode((string) $response->getBody(), true);

            return $decodedJson;
        } else {
            return $response;
        }
    }

    /**
     * @param array<mixed> $parameters
     */
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
                    ? json_decode((string) $response->getBody(), true)
                    : null,
                'queryTime' => $queryTime,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            ];
        }
    }
}
