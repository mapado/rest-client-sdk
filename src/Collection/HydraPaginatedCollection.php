<?php

namespace Mapado\RestClientSdk\Collection;

/**
 * Class HydraPaginatedCollection
 *
 * @author Florent Clerc <florent.clerc@mapado.com>
 */
class HydraPaginatedCollection extends HydraCollection
{
    private $firstPage = null;
    private $lastPage = null;
    private $nextPage = null;

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
    }

    /**
     *  return mixed
     */
    public function getFirstPage()
    {
        return $this->firstPage;
    }

    /**
     *  return mixed
     */
    public function getLastPage()
    {
        return $this->lastPage;
    }

    /**
     *  getNextPage
     *
     *  return mixed
     */
    public function getNextPage()
    {
        return $this->nextPage;
    }
}
