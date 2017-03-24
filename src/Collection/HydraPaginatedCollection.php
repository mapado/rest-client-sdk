<?php

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
     *
     * @return string|null
     */
    public function getFirstPage()
    {
        return $this->getExtraProperty('hydra:firstPage');
    }

    /**
     * Returns last page URI.
     *
     * @return string|null
     */
    public function getLastPage()
    {
        return $this->getExtraProperty('hydra:lastPage');
    }

    /**
     * Returns next page URI.
     *
     * @return string|null
     */
    public function getNextPage()
    {
        return $this->getExtraProperty('hydra:nextPage');
    }

    /**
     * Returns total item count.
     *
     * @return int
     */
    public function getTotalItems()
    {
        return $this->getExtraProperty('hydra:totalItems') ?: 0;
    }
}
