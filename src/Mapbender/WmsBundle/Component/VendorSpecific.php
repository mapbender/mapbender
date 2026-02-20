<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Source\CustomParameter;

/**
 * @author Paul Schmidt
 */
class VendorSpecific extends CustomParameter
{
    const TYPE_VS_SIMPLE = 'simple';
    const TYPE_VS_USER = 'user';
    const TYPE_VS_GROUP = 'groups';

    public $vstype;

    public function __unserialize(array $array)
    {
        if (array_key_exists('vstype', $array)) $this->vstype = $array['vstype'];
        parent::__unserialize($array);
    }


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
}
