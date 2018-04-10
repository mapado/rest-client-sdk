<?php

namespace Mapado\RestClientSdk\Collection;

/**
 * Class HalCollection
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class HalCollection extends Collection
{
    public function getLinks()
    {
        return $this->getExtraProperty('_links');
    }

    public function getEmbedded()
    {
        return $this->getExtraProperty('_embedded');
    }
}
