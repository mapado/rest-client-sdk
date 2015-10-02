<?php

namespace Mapado\RestClientSdk\Tests\Model;

/**
 * Class Product
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Product
{
    private $id;

    private $value;

    private $currency;

    /**
     * Getter for id
     *
     * return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter for id
     *
     * @param string $id
     * @return Product
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Getter for value
     *
     * return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Setter for value
     *
     * @param float $value
     * @return Product
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Getter for currency
     *
     * return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Setter for currency
     *
     * @param string $currency
     * @return Product
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }
}
