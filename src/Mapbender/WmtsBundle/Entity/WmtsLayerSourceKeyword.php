<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Entity\Keyword;

/**
 * Source entity
 *
 * @author Paul Schmidt
 *
 * @ ORM\Entity
 * @ ORM\Table(name="mb_wmts_keyword_layersource")
 */
class WmtsLayerSourceKeyword extends Keyword
{
    /**
     * @ORM\ManyToOne(targetEntity="WmtsLayerSource", inversedBy="keywords", cascade={"refresh"})
     * @ORM\JoinColumn(name="reference_id", referencedColumnName="id")
     */
    protected $reference;

    /**
     * Set reference object
     *
     * @return ContainingKeyword
     */
    public function setReferenceObject(ContainingKeyword $wmtslayersource)
    {
        $this->reference = $wmtslayersource;
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