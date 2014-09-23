<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\ContainsKeyword;
use Mapbender\CoreBundle\Entity\Keyword;

/**
 * Source entity
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_keyword_layersource")
 */
class WmsLayerSourceKeyword extends Keyword
{
    
    /**
     * @ORM\ManyToOne(targetEntity="WmsLayerSource", inversedBy="keywords", cascade={"refresh"})
     * @ORM\JoinColumn(name="reference_id", referencedColumnName="id")
     */
    protected $reference;

    /**
     * Set reference object
     *
     * @return ContainsKeyword
     */
    public function setReferenceObject(ContainsKeyword $wmssource)
    {
        $this->reference = $wmssource;
    }

    /**
     * Get reference object
     *
     * @return ContainsKeyword
     */
    public function getReferenceObject()
    {
        return $this->reference;
    }

}
