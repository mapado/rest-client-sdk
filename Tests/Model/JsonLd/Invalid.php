<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\JsonLd;

use Mapado\RestClientSdk\Mapping\Attributes\Entity;
use Mapado\RestClientSdk\Mapping\Attributes\ManyToOne;

#[Entity('invalid')]
class Invalid
{
    #[ManyToOne('client', Client::class)]
    private $client;

    public function getClient(): Client
    {
        return $this->client;
    }
}
