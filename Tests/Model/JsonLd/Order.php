<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\JsonLd;

use Mapado\RestClientSdk\Mapping\Attributes as Rest;

/**
 * Order Model
 *
 * @author Thomas di Luccio <thomas.diluccio@mapado.com>
 *
 * @Rest\Entity(key="order")
 */
#[Rest\Entity(key: 'order')]
class Order
{
    #[Rest\Id]
    #[Rest\Attribute(name: 'id', type: 'string')]
    private $id;

    /**
     * customerPaidAmount
     *
     * @var int
     */
    #[Rest\Attribute(name: 'customerPaidAmount', type: 'integer')]
    private $customerPaidAmount;

    /**
     * status
     *
     * @var string
     */
    #[Rest\Attribute(name: 'status', type: 'string')]
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
     * Getter for customerPaidAmount
     *
     * @return int
     */
    public function getCustomerPaidAmount()
    {
        return $this->customerPaidAmount;
    }

    /**
     * Setter for customerPaidAmount
     *
     * @param int $customerPaidAmount
     *
     * @return Order
     */
    public function setCustomerPaidAmount($customerPaidAmount)
    {
        $this->customerPaidAmount = $customerPaidAmount;

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
