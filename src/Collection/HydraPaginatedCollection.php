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
     * @var string URI of the first page
     */
    private $firstPage = null;

    /**
    * @var string URI of the last page
     */
    private $lastPage = null;

    /**
    * @var string URI of the next page
     */
    private $nextPage = null;

    /**
     * @var integer the total number of elements regardless of the pagination
     */
    private $totalItems = 0;

    /**
     * @param array response - The Hydra data as an array
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
     *  getFirstPage
     *
     *  @return mixed
     */
    public function getFirstPage()
    {
        return $this->firstPage;
    }

    /**
     *  getLastPage
     *
     *  @return mixed
     */
    public function getLastPage()
    {
        return $this->lastPage;
    }

    /**
     *  getNextPage
     *
     *  @return mixed
     */
    public function getNextPage()
    {
        return $this->nextPage;
    }

    /**
     *  getTotalItems
     *
     *  @return integer
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }
}
