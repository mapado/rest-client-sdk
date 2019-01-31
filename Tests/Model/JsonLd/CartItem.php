<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\JsonLd;

use DateTime;
use Mapado\RestClientSdk\Mapping\Annotations as Rest;

/**
 * Class CartItem
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 *
 * @Rest\Entity(key="cart_item")
 */
class CartItem
{
    /**
     * id
     *
     * @var mixed
     *
     * @Rest\Id
     * @Rest\Attribute(name="id", type="string")
     */
    private $id;

    /**
     * amount
     *
     * @var mixed
     *
     * @Rest\Attribute(name="amount", type="float")
     */
    private $amount;

    /**
     * createdAt
     *
     * @var mixed
     *
     * @Rest\Attribute(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * data
     *
     * @var mixed
     *
     * @Rest\Attribute(name="data", type="array")
     */
    private $data = [];

    /**
     * cart
     *
     * @var mixed
     *
     * @Rest\ManyToOne(name="cart", targetEntity="Cart")
     */
    private $cart;

    /**
     * product
     *
     * @var mixed
     */
    private $product;

    /**
     * cartItemDetailList
     *
     * @var mixed
     */
    private $cartItemDetailList = [];

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
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Setter for amount
     *
     * @param float $amount
     *
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
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Setter for createdAt
     *
     * @param DateTime $createdAt
     *
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
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Setter for data
     *
     * @param array $data
     *
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
     * @return Cart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * Setter for cart
     *
     * @param Cart $cart
     *
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
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Setter for product
     *
     * @param Product $product
     *
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
     * @return array
     */
    public function getCartItemDetailList()
    {
        return $this->cartItemDetailList;
    }

    /**
     * Setter for cartItemDetailList
     *
     * @param array $cartItemDetailList
     *
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
