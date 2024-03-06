<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\JsonLd;

use Mapado\RestClientSdk\Mapping\Attributes as Rest;

/**
 * Class Cart
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
#[Rest\Entity(key: 'cart')]
class Cart
{
    #[Rest\Id]
    #[Rest\Attribute(name: 'id', type: 'string')]
    private $id;

    #[Rest\Attribute(name: 'status', type: 'string')]
    private $status;

    /**
     * createdAt
     */
    #[Rest\Attribute(name: 'created_at', type: 'datetime')]
    private $createdAt;

    #[Rest\OneToMany(name: 'cart_items', targetEntity: 'CartItem')]
    private $cartItemList = [];

    /**
     * clientPhoneNumber
     *
     * @var string
     */
    #[Rest\Attribute(name: 'clientPhoneNumber', type: 'phone_number')]
    private $clientPhoneNumber;

    #[Rest\ManyToOne(name: 'order', targetEntity: 'Order')]
    private $order;

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
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Setter for createdAt
     *
     * @return Cart
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Getter for cartItemList
     *
     * @return array
     */
    public function getCartItemList()
    {
        return $this->cartItemList;
    }

    /**
     * Setter for cartItemList
     *
     * @param array $cartItemList
     *
     * @return Cart
     */
    public function setCartItemList($cartItemList)
    {
        $this->cartItemList = $cartItemList;

        return $this;
    }

    public function addCartItemList($cartItem): void
    {
        $this->cartItemList[] = $cartItem;
    }

    /**
     * Getter for clientPhoneNumber
     *
     * @return string
     */
    public function getClientPhoneNumber()
    {
        return $this->clientPhoneNumber;
    }

    /**
     * Setter for clientPhoneNumber
     *
     * @param string $clientPhoneNumber
     *
     * @return Cart
     */
    public function setClientPhoneNumber($clientPhoneNumber)
    {
        $this->clientPhoneNumber = $clientPhoneNumber;

        return $this;
    }

    /**
     * Getter for order
     *
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Setter for order
     *
     * @param string $order
     *
     * @return Cart
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }
}
