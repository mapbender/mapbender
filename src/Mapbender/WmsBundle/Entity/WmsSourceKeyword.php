<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Entity\Keyword;

/**
 * Source entity
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_keyword_source")
 */
class WmsSourceKeyword extends Keyword
{
    
    /**
     * @ORM\ManyToOne(targetEntity="WmsSource", inversedBy="keywords")
     * @ORM\JoinColumn(name="reference_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $reference;

    /**
     * Set reference object
     *
     * @param ContainingKeyword $wmssource
     */
    public function setReferenceObject(ContainingKeyword $wmssource)
    {
        $this->reference = $wmssource;
    }

    /**
     * Get reference object
     *
     * @return ContainingKeyword
     */
    public function getReferenceObject()
    {
        return $this->reference;
    }

}
