<?php

namespace Mapbender\VectorTilesBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\VectorTilesBundle\VectorTilesDataSource;

#[ORM\Entity]
#[ORM\Table(name: 'mb_vectortiles_instance')]
class VectorTileInstance extends SourceInstance
{

    #[ORM\ManyToOne(targetEntity: VectorTileSource::class, cascade: ['refresh'], inversedBy: 'instances')]
    #[ORM\JoinColumn(name: 'source', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $source;


    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getLayers()
    {
        return [];
    }

    public function getDisplayTitle()
    {
        return $this->getTitle() ?: $this->getSource()->getTitle();
    }
}
