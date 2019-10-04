<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk;

use GuzzleHttp\Psr7;
use Mapado\RestClientSdk\Exception\RestClientException;
use Mapado\RestClientSdk\Exception\RestException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

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
     * @var ?SymfonyRequest
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

    public function setCurrentRequest(SymfonyRequest $currentRequest): self
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
            $response = $this->executeRequest('GET', $requestUrl, $parameters);

            $this->throwClientException(
                $response,
                $path,
                'Error while getting resource',
                7
            );
            $this->throwServerException(
                $response,
                $path,
                'Error while getting resource',
                1
            );

            return $this->responseOrJsonNullable($response);
        } catch (ClientExceptionInterface $e) {
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
            $response = $this->executeRequest('DELETE', $this->baseUrl . $path);

            $this->throwServerException(
                $response,
                $path,
                'Error while deleting resource',
                2
            );
        } catch (ClientExceptionInterface $e) {
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
            $response = $this->executeRequest(
                'POST',
                $this->baseUrl . $path,
                $parameters
            );

            $this->throwClientException(
                $response,
                $path,
                'Cannot create resource',
                3
            );
            $this->throwClientNotFoundException(
                $response,
                $path,
                'Cannot create resource',
                3
            );
            $this->throwServerException(
                $response,
                $path,
                'Error while posting resource',
                4
            );

            return $this->responseOrJson($response);
        } catch (ClientExceptionInterface $e) {
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
            $response = $this->executeRequest(
                'PUT',
                $this->baseUrl . $path,
                $parameters
            );

            $this->throwClientNotFoundException(
                $response,
                $path,
                'Cannot update resource',
                5
            );
            $this->throwClientException(
                $response,
                $path,
                'Cannot update resource',
                5
            );
            $this->throwServerException(
                $response,
                $path,
                'Error while puting resource',
                6
            );

            return $this->responseOrJson($response);
        } catch (ClientExceptionInterface $e) {
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
        if (null !== $request) {
            $defaultParameters['headers'] = ['Referer' => $request->getUri()];
        }

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

    protected function getCurrentRequest(): ?SymfonyRequest
    {
        if ('cli' === \PHP_SAPI) {
            // we are in cli mode, do not bother to get request
            return null;
        }

        if (!$this->currentRequest) {
            $this->currentRequest = SymfonyRequest::createFromGlobals();
        }

        return $this->currentRequest;
    }

    /**
     * @throws ClientExceptionInterface
     */
    private function executeRequest(
        string $method,
        string $url,
        array $parameters = []
    ): ResponseInterface {
        $parameters = $this->mergeDefaultParameters($parameters);

        $startTime = null;
        if ($this->isHistoryLogged()) {
            $startTime = microtime(true);
        }

        try {
            $request = new Psr7\Request($method, $url, $parameters);
            $response = $this->httpClient->sendRequest($request);
            $this->logRequest(
                $startTime,
                $method,
                $url,
                $parameters,
                $response
            );
        } catch (ClientExceptionInterface $e) {
            $this->logRequest($startTime, $method, $url, $parameters);

            throw $e;
        }

        return $response;
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
                    ? json_decode((string) $response->getBody(), true)
                    : null,
                'queryTime' => $queryTime,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            ];
        }
    }

    private function throwClientException(
        ResponseInterface $response,
        string $path,
        string $message,
        int $code
    ): void {
        $statusCode = $response->getStatusCode();

        if (404 !== $statusCode && $statusCode >= 400 && $statusCode < 500) {
            // 4xx except 404
            $exception = new RestClientException($message, $path, [], $code);

            $exception->setResponse($response);

            throw $exception;
        }
    }

    private function throwServerException(
        ResponseInterface $response,
        string $path,
        string $message,
        int $code
    ): void {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 500 && $statusCode < 600) {
            // 5xx errors
            $exception = new RestException($message, $path, [], $code);

            $exception->setResponse($response);

            throw $exception;
        }
    }

    private function throwClientNotFoundException(
        ResponseInterface $response,
        string $path,
        string $message,
        int $code
    ): void {
        $statusCode = $response->getStatusCode();

        if (404 === $statusCode) {
            $exception = new RestClientException($message, $path, [], $code);

            $exception->setResponse($response);

            throw $exception;
        }
    }

    /**
     * @return array|ResponseInterface|null
     */
    private function responseOrJsonNullable(ResponseInterface $response)
    {
        if (404 === $response->getStatusCode()) {
            return null;
        }

        $headers = $response->getHeaders();
        $jsonContentTypeList = ['application/ld+json', 'application/json'];

        $requestIsJson = false;

        if (isset($headers['Content-Type'])) {
            foreach ($jsonContentTypeList as $contentType) {
                if (
                    false !==
                    mb_stripos($headers['Content-Type'][0], $contentType)
                ) {
                    $requestIsJson = true;
                    break;
                }
            }
        }

        if ($requestIsJson) {
            return json_decode((string) $response->getBody(), true);
        } else {
            return $response;
        }
    }

    /**
     * @return array|ResponseInterface
     */
    private function responseOrJson(ResponseInterface $response)
    {
        $out = $this->responseOrJsonNullable($response);

        if (null === $out) {
            throw new \RuntimeException(
                'response should not be null. This should not happen.'
            );
        }

        return $out;
    }
}
