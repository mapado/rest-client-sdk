<?php

namespace Mapado\RestClientSdk\Tests\Model;

use DateTime;

/**
 * Class CartItem
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class CartItem
{
    private $id;

    private $amount;

    private $createdAt;

    private $data = [];

    private $cart;

    private $product;

    private $cartItemDetailList = [];

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
     * @return CartItem
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Getter for amount
     *
     * return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Setter for amount
     *
     * @param float $amount
     * @return CartItem
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
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
     * @return CartItem
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Getter for data
     *
     * return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Setter for data
     *
     * @param array $data
     * @return CartItem
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Getter for cart
     *
     * return Cart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * Setter for cart
     *
     * @param Cart $cart
     * @return CartItem
     */
    public function setCart(Cart $cart)
    {
        $this->cart = $cart;
        $this->cart->addCartItemList($this);

        return $this;
    }

    /**
     * Getter for product
     *
     * return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Setter for product
     *
     * @param Product $product
     * @return CartItem
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * Getter for cartItemDetailList
     *
     * return array
     */
    public function getCartItemDetailList()
    {
        return $this->cartItemDetailList;
    }

    /**
     * Setter for cartItemDetailList
     *
     * @param array $cartItemDetailList
     * @return CartItem
     */
    public function setCartItemDetailList(array $cartItemDetailList)
    {
        $this->cartItemDetailList = $cartItemDetailList;
        return $this;
    }

    public function addCartItemDetailList($itemDetail)
    {
        $this->cartItemDetailList[] = $itemDetail;
        return $this;
    }
}
