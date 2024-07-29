<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Collection;

/**
 * @template E
 * @template ExtraProperty
 * @extends Collection<E, ExtraProperty>
 */
class HalCollection extends Collection
{
    /**
     * @return mixed
     */
    public function getLinks()
    {
        return $this->getExtraProperty('_links');
    }

    /**
     * @return mixed
     */
    public function getEmbedded()
    {
        return $this->getExtraProperty('_embedded');
    }
}
