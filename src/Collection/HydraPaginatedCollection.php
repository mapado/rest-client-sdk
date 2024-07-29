<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Collection;

/**
 * @template E
 * @template ExtraProperty
 * @extends Collection<E, ExtraProperty>
 */
class HydraPaginatedCollection extends Collection
{
    /**
     * Returns first page URI.
     */
    public function getFirstPage(): ?string
    {
        return $this->getStringExtraProperty('hydra:firstPage');
    }

    /**
     * Returns last page URI.
     */
    public function getLastPage(): ?string
    {
        return $this->getStringExtraProperty('hydra:lastPage');
    }

    /**
     * Returns next page URI.
     */
    public function getNextPage(): ?string
    {
        return $this->getStringExtraProperty('hydra:nextPage');
    }

    /**
     * Returns total item count.
     */
    public function getTotalItems(): int
    {
        return $this->getIntExtraProperty('hydra:totalItems') ?? 0;
    }
}
