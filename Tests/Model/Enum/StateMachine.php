<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\Enum;

use Mapado\RestClientSdk\Mapping\Attributes as Rest;

#[Rest\Entity(key: 'cart')]
class StateMachine
{
    #[
        Rest\Id,
        Rest\Attribute(name: 'id', type: 'string')
    ]
    private $id;

    /**
     * status
     *
     * @var mixed
     */
    #[
        Rest\Attribute(name: 'status', type: 'string')
    ]
    private StateEnum $state;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getState(): StateEnum
    {
        return $this->state;
    }

    public function setState(StateEnum $state): self
    {
        $this->state = $state;

        return $this;
    }
}
