<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\Hal;

/**
 * Class Order
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Order
{
    private $id;
    private $total;
    private $currency;
    private $status;

    /**
     * Getter for id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter for id
     *
     * @param string $id
     *
     * @return Order
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Getter for total
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Setter for total
     *
     * @param float $total
     *
     * @return Order
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Getter for currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Setter for currency
     *
     * @param string $currency
     *
     * @return Order
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Getter for status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Setter for status
     *
     * @param string $status
     *
     * @return Order
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
}
