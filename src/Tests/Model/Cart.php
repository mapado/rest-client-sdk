<?php

namespace Mapado\RestClientSdk\Tests\Model;

use DateTime;

/**
 * Class Cart
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Cart
{
    private $id;

    private $status;

    private $createdAt;

    private $cartItemList = [];

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
     * @return Cart
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Getter for status
     *
     * return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Setter for status
     *
     * @param string $status
     * @return Cart
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Getter for createdAt
     *
     * return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Setter for createdAt
     *
     * @param DateTime $createdAt
     * @return Cart
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Getter for cartItemList
     *
     * return array
     */
    public function getCartItemList()
    {
        return $this->cartItemList;
    }

    /**
     * Setter for cartItemList
     *
     * @param array $cartItemList
     * @return Cart
     */
    public function setCartItemList($cartItemList)
    {
        $this->cartItemList = $cartItemList;
        return $this;
    }

    public function addCartItemList($cartItem)
    {
        $this->cartItemList[] =  $cartItem;
    }
}
