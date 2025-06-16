<?php

namespace Mapbender\VectorTilesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Entity\Source;

class VectorTileSource extends Source
{

    public function getInstances(): array|ArrayCollection
    {
        return [];
    }

    public function getLayers()
    {

    }

    public function getViewTemplate($frontend = false)
    {

    }
}
