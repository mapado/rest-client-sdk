<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Model;

use Mapado\RestClientSdk\Collection\Collection;
use Mapado\RestClientSdk\Collection\HalCollection;
use Mapado\RestClientSdk\Collection\HydraPaginatedCollection;
use Mapado\RestClientSdk\Helper\ArrayHelper;
use Mapado\RestClientSdk\SdkClient;

/**
 * Class ModelHydrator
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ModelHydrator
{
    /**
     * @var SdkClient
     */
    protected $sdk;

    public function __construct(SdkClient $sdk)
    {
        $this->sdk = $sdk;
    }

    public function convertId(string|int $id, string $modelName): string
    {
        $id = (string) $id;

        // add slash if needed to have a valid hydra id
        if (false === mb_strpos($id, '/')) {
            $mapping = $this->sdk->getMapping();
            $key = $mapping->getKeyFromModel($modelName);
            $id = '/' . $key . '/' . $id;

            if ($prefix = $mapping->getIdPrefix()) {
                $id = $prefix . $id;
            }
        }

        return $id;
    }

    public function hydrate(?array $data, string $modelName): ?object
    {
        $mapping = $this->sdk->getMapping();
        $key = $mapping->getKeyFromModel($modelName);
        $modelName = $mapping->getModelName($key);

        return $this->deserialize($data, $modelName);
    }

    /**
     * convert API response to Collection containing entities
     *
     * @param class-string $modelName
     */
    public function hydrateList(?array $data, string $modelName): Collection
    {
        $collectionKey = $this->sdk->getMapping()->getConfig()['collectionKey'];

        if (is_array($data) && ArrayHelper::arrayHas($data, $collectionKey)) {
            return $this->deserializeAll($data, $modelName);
        }

        return new Collection();
    }

    /**
     * convert list of data as array to Collection containing entities
     *
     * @param class-string $modelName
     */
    private function deserializeAll(array $data, string $modelName): Collection
    {
        $collectionKey = $this->sdk->getMapping()->getConfig()['collectionKey'];

        $itemList = ArrayHelper::arrayGet($data, $collectionKey);

        if (!is_array($itemList)) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to deserialize collection, %s key not found in response',
                    $collectionKey,
                ),
            );
        }

        $itemList = array_map(
            fn(?array $member) => $this->deserialize($member, $modelName),
            $itemList,
        );

        $extraProperties = array_filter(
            $data,
            fn($key) => $key !== $collectionKey,
            \ARRAY_FILTER_USE_KEY,
        );

        /** @var class-string $collectionClassName */
        $collectionClassName = $this->guessCollectionClassname($data);

        if (!class_exists($collectionClassName)) {
            throw new \RuntimeException(
                "Seem's like $collectionClassName does not exist",
            );
        }

        /* @var Collection */
        return new $collectionClassName($itemList, $extraProperties);
    }

    /**
     * convert array to entity
     *
     * @param class-string $modelName
     */
    private function deserialize(?array $data, string $modelName): ?object
    {
        if (null === $data) {
            return null;
        }

        return $this->sdk->getSerializer()->deserialize($data, $modelName);
    }

    /**
     * guess collection classname according to response data
     */
    private function guessCollectionClassname(array $data): string
    {
        switch (true) {
            case !empty($data['@type'])
            && 'hydra:PagedCollection' === $data['@type']:
                return HydraPaginatedCollection::class;

            case array_key_exists('_embedded', $data):
                return HalCollection::class;

            default:
                return Collection::class;
        }
    }
}
