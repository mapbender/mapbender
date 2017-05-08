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
 * @ORM\Table(name="mb_wms_keyword_layersource")
 */
class WmsLayerSourceKeyword extends Keyword
{
    /**
     * @ORM\ManyToOne(targetEntity="WmsLayerSource", inversedBy="keywords")
     * @ORM\JoinColumn(name="reference_id", referencedColumnName="id")
     */
    protected $reference;

    /**
     * Set reference object
     *
     * @param ContainingKeyword $wmsLayerSource
     * @return ContainingKeyword
     */
    public function setReferenceObject(ContainingKeyword $wmsLayerSource)
    {
        $this->reference = $wmsLayerSource;
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
