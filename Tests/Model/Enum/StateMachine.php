<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\Enum;

use Mapado\RestClientSdk\Mapping\Annotations as Rest;

/**
 * @Rest\Entity(key="cart")
 */
class StateMachine
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
     * status
     *
     * @var mixed
     *
     * @Rest\Attribute(name="status", type="string")
     */
    private StateEnum $state;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): StateMachine
    {
        $this->id = $id;

        return $this;
    }

    public function getState(): StateEnum
    {
        return $this->state;
    }

    public function setState(StateEnum $state): StateMachine
    {
        $this->state = $state;

        return $this;
    }
}