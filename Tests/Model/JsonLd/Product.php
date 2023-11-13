<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\JsonLd;

use Mapado\RestClientSdk\Mapping\Attributes as Rest;

/**
 * Class Product
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 *
 * @Rest\Entity(key="product", repository="Mapado\RestClientSdk\Test\Model\ModelRepository")
 */
#[Rest\Entity(key: 'product', repository: ModelRepository::class)]
class Product
{
    /**
     * id
     *
     * @var int
     *
     * @Rest\Id
     *
     * @Rest\Attribute(name="id", type="integer")
     */
    #[
        Rest\Id,
        Rest\Attribute(name: 'id', type: 'integer')
    ]
    private $id;

    /**
     * value
     *
     * @var string
     *
     * @Rest\Attribute(name="product_value", type="string")
     */
    #[Rest\Attribute(name: 'product_value', type: 'string')]
    private $value;

    /**
     * currency
     *
     * @var string
     *
     * @Rest\Attribute(name="currency", type="string")
     */
    #[Rest\Attribute(name: 'currency', type: 'string')]
    private $currency;

    /**
     * Getter for id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter for id
     *
     * @param int $id
     *
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
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Setter for value
     *
     * @param string $value
     *
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
     * @return Product
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }
}
