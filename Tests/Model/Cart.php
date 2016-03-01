<?php

namespace Mapado\RestClientSdk\Tests\Model;

use DateTime;
use Mapado\RestClientSdk\Mapping\Annotations as Rest;

/**
 * Class Cart
 * @author Julien Deniau <julien.deniau@mapado.com>
 *
 * @Rest\Entity(key="cart")
 */
class Cart
{
    /**
     * id
     *
     * @var mixed
     * @access private
     *
     * @Rest\Id
     * @Rest\Attribute(name="id", type="string")
     */
    private $id;

    /**
     * status
     *
     * @var mixed
     * @access private
     *
     * @Rest\Attribute(name="status", type="string")
     */
    private $status;

    /**
     * createdAt
     *
     * @var mixed
     * @access private
     *
     * @Rest\Attribute(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * cartItemList
     *
     * @var mixed
     * @access private
     *
     * @Rest\OneToMany(name="cart_items", targetEntity="CartItem")
     */
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
