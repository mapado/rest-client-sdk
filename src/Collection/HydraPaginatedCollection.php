<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Collection;

/**
 * Class HydraPaginatedCollection
 *
 * @author Florent Clerc <florent.clerc@mapado.com>
 */
class HydraPaginatedCollection extends Collection
{
    /**
     * Returns first page URI.
     */
    public function getFirstPage(): ?string
    {
        return $this->getExtraProperty('hydra:firstPage');
    }

    /**
     * Returns last page URI.
     */
    public function getLastPage(): ?string
    {
        return $this->getExtraProperty('hydra:lastPage');
    }

    /**
     * Returns next page URI.
     */
    public function getNextPage(): ?string
    {
        return $this->getExtraProperty('hydra:nextPage');
    }

    /**
     * Returns total item count.
     */
    public function getTotalItems(): int
    {
        return $this->getExtraProperty('hydra:totalItems') ?? 0;
    }
}
