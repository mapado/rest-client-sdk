<?php

namespace Mapado\RestClientSdk\Collection;

/**
 * Class HydraPaginatedCollection
 *
 * @author Florent Clerc <florent.clerc@mapado.com>
 */
class HydraPaginatedCollection extends HydraCollection
{
    /**
     * URI of the first page.
     *
     * @var string|null
     */
    private $firstPage = null;

    /**
     * URI of the last page.
     *
     * @var string|null
     */
    private $lastPage = null;

    /**
     * URI of the next page.
     *
     * @var string|null
     */
    private $nextPage = null;

    /**
     * The total number of elements regardless of the pagination.
     *
     * @var integer
     */
    private $totalItems = 0;

    /**
     * @param array $response The Hydra data as an array
     */
    public function __construct($response)
    {
        parent::__construct($response);

        if (!empty($response['hydra:firstPage'])) {
            $this->firstPage = $response['hydra:firstPage'];
        }

        if (!empty($response['hydra:lastPage'])) {
            $this->lastPage = $response['hydra:lastPage'];
        }

        if (!empty($response['hydra:nextPage'])) {
            $this->nextPage = $response['hydra:nextPage'];
        }

        if (!empty($response['hydra:totalItems'])) {
            $this->totalItems = $response['hydra:totalItems'];
        }
    }

    /**
     * Returns first page URI.
     *
     * @return string|null
     */
    public function getFirstPage()
    {
        return $this->firstPage;
    }

    /**
     * Returns last page URI.
     *
     * @return string|null
     */
    public function getLastPage()
    {
        return $this->lastPage;
    }

    /**
     * Returns next page URI.
     *
     * @return string|null
     */
    public function getNextPage()
    {
        return $this->nextPage;
    }

    /**
     * Returns total item count.
     *
     * @return int
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }
}
