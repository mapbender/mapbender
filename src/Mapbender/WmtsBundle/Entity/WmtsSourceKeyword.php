<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Entity\Keyword;

/**
 * Source entity
 *
 * @author Paul Schmidt
 */
#[ORM\Entity]
#[ORM\Table(name: 'mb_wmts_keyword_source')]
class WmtsSourceKeyword extends Keyword
{

    #[ORM\ManyToOne(targetEntity: WmtsSource::class, cascade: ['refresh'], inversedBy: 'keywords')]
    #[ORM\JoinColumn(name: 'reference_id', referencedColumnName: 'id')]
    protected $reference;

    /**
     * @param ContainingKeyword $wmtssource
     * @return $this
     */
    public function setReferenceObject(ContainingKeyword $wmtssource)
    {
        $this->reference = $wmtssource;
        return $this;
    }

    /**
     * @return ContainingKeyword
     */
    public function getReferenceObject()
    {
        return $this->reference;
    }

}
