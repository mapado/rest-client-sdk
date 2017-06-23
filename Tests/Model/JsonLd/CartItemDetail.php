<?php

namespace Mapado\RestClientSdk\Tests\Model\JsonLd;

use Mapado\RestClientSdk\Mapping\Annotations as Rest;

/**
 * Class CartItem
 * @author Julien Deniau <julien.deniau@mapado.com>
 *
 * @Rest\Entity(key="cart_item_details")
 */
class CartItemDetail
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

    private $name;

    private $cartItem;

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
     * @return CartItemDetail
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Getter for name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Setter for name
     *
     * @param string $name
     * @return CartItemDetail
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Getter for cartItem
     *
     * @return CartItem
     */
    public function getCartItem()
    {
        return $this->cartItem;
    }

    /**
     * Setter for cartItem
     *
     * @param CartItem $cartItem
     * @return CartItemDetail
     */
    public function setCartItem(CartItem $cartItem)
    {
        $this->cartItem = $cartItem;
        $cartItem->addCartItemDetailList($this);
        return $this;
    }
}
