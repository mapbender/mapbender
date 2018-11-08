<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Source\CustomParameter;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class VendorSpecific extends CustomParameter
{
    /** @deprecated, remove after cleaning up extent processing from VendorSpecificTransformer */
    const TYPE_SINGLE           = 'single';
    /** @deprecated, remove after cleaning up extent processing from VendorSpecificTransformer */
    const TYPE_INTERVAL         = 'interval';
    /** @deprecated, remove after cleaning up extent processing from VendorSpecificTransformer */
    const TYPE_MULTIPLE         = 'multiple';

    const TYPE_VS_SIMPLE = 'simple';
    const TYPE_VS_USER = 'user';
    const TYPE_VS_GROUP = 'groups';

    public $vstype;

    /**
     * @return string|null
     */
    public function getVstype()
    {
        return $this->vstype;
    }

    /**
     * @param string $vstype one of the VS_TYPE_* consts
     */
    public function setVstype($vstype)
    {
        $this->vstype = $vstype;
    }

    /**
     * @return string parameter name
     */
    public function getParameterName()
    {
        return $this->name;
    }

    /**
     * @deprecated, only used (indirectly) by WmcParser110
     * @return array
     */
    public function getConfiguration()
    {
        return array(
            'default' => $this->getDefault(),
            'name' => $this->getName(),
            '__name' => $this->getParameterName(),
            'hidden' => $this->getHidden(),
            'vstype' => $this->getVstype(),
        );
    }

    /**
     * @deprecated, remove after cleaning up extent processing from VendorSpecificTransformer
     */
    final public function getType()
    {
        return -1;
    }

    /**
     * @return null
     * @deprecated, remove after cleaning up extent processing from VendorSpecificTransformer
     */
    final public function getExtent()
    {
        return null;
    }
}
