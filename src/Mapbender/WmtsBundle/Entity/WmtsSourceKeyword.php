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
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_keyword_source")
 */
class WmtsSourceKeyword extends Keyword
{
    
    /**
     * @ORM\ManyToOne(targetEntity="WmtsSource", inversedBy="keywords", cascade={"refresh"})
     * @ORM\JoinColumn(name="reference_id", referencedColumnName="id")
     */
    protected $reference;

    /**
     * Set reference object
     *
     * @return ContainingKeyword
     */
    public function setReferenceObject(ContainingKeyword $wmtssource)
    {
        $this->reference = $wmtssource;
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
