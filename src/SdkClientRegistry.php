<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk;

use Mapado\RestClientSdk\Exception\SdkClientNotFoundException;

class SdkClientRegistry
{
    /** @var array<string, SdkClient> $sdkClientList */
    private array $sdkClientList;

    /**
     * @param array<string, SdkClient> $sdkClientList
     */
    public function __construct(array $sdkClientList)
    {
        $this->sdkClientList = $sdkClientList;
    }

    public function getSdkClient(string $name): SdkClient
    {
        $client = $this->sdkClientList[$name] ?? null;

        if (!$client) {
            throw new SdkClientNotFoundException(
                'Sdk client not found for name ' . $name
            );
        }

        return $client;
    }

    /**
     * @return array<SdkClient>
     */
    public function getSdkClientList(): array
    {
        return $this->sdkClientList;
    }

    public function getSdkClientForClass(string $entityClassname): SdkClient
    {
        foreach ($this->sdkClientList as $sdkClient) {
            if ($sdkClient->getMapping()->hasClassMetadata($entityClassname)) {
                return $sdkClient;
            }
        }

        throw new SdkClientNotFoundException(
            'Sdk client not found for entity class ' . $entityClassname
        );
    }
}
