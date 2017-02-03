<?php

namespace Mapado\RestClientSdk\Tests\Model\JsonLd;

/**
 * Order Model
 * @author Thomas di Luccio <thomas.diluccio@mapado.com>
 */
class Order
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
     * @return Order
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
