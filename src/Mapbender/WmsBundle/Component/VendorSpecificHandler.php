<?php

namespace Mapbender\WmsBundle\Component;

use Doctrine\ORM\PersistentCollection;
use FOM\UserBundle\Entity\Group;
use Mapbender\CoreBundle\Utils\EntityUtil;

/**
 * VendorSpecificHandler class for handling of VendorSpecific.
 *
 * @author Paul Schmidt
 * @deprecated
 * @internal
 *
 * Only used by WmsInstanceEntityHandler
 */
class VendorSpecificHandler
{
    protected $vendorspecific;

    public function __construct(VendorSpecific $vendorspecific)
    {
        $this->vendorspecific = $vendorspecific;
    }

    public function getVendorspecific()
    {
        return $this->vendorspecific;
    }

    /**
     * Sets a vendor specific
     * @param VendorSpecific $vendorspecific
     * @return \Mapbender\WmsBundle\Component\VendorSpecificHandler
     */
    public function setVendorspecific(VendorSpecific $vendorspecific)
    {
        $this->vendorspecific = $vendorspecific;
        return $this;
    }

    /**
     * Checks if a value is dynamic. A "dynamic value" begins and ends with a '$'. A value of a "dynamic value" is
     * a keyword for an method.
     * @param string $value a string value
     * @return boolean true if a value is dynamic.
     */
    public function isValueDynamic($value)
    {
        $length = strlen($value);
        return $length > 2 && strpos($value, '$', 0) === 0 && strpos($value, '$', $length - 2) === $length - 1;
    }

    /**
     * Checks if a value is dynamic. A "dynamic value" begins and ends with a '$'. A value of a "dynamic value" is
     * a keyword for an method.
     * @param string $value a string value
     * @return boolean true if a value is dynamic.
     */
    public function stripDynamic($value)
    {
        return str_replace('$', '', $value);
    }

    /**
     * Reterns a vendor specific value
     * @param mixed $object
     * @return string|null
     */
    public function getVendorSpecificValue($object)
    {
        $value = $this->vendorspecific->getDefault();
        if ($value) {
            $length = strlen($value);
            if ($length > 2 && strpos($value, '$', 0) === 0 && strpos($value, '$', $length - 2) === $length - 1) {
                $paramVal = $object ? EntityUtil::getValueFromGetter($object, str_replace('$', '', $value)) : null;
                if ($paramVal instanceof PersistentCollection) { # groups
                    $help = array();
                    foreach ($paramVal as $item) {
                        if ($item instanceof Group) {
                            $help[] = $item->getId();
                        }
                    }
                    return implode(',', $help);
                } else {
                    return $paramVal;
                }
            } else {
                return $value;
            }
        }
        return null;
    }

    public function getConfiguration()
    {
        return $this->vendorspecific->getConfiguration();
    }

    public function getKvpConfiguration($object)
    {
        if ($this->vendorspecific->getVstype() === VendorSpecific::TYPE_VS_SIMPLE) {
            return array($this->vendorspecific->getParameterName() => $this->vendorspecific->getDefault());
        } elseif ($this->vendorspecific->getVstype() === VendorSpecific::TYPE_VS_USER &&
            !$this->vendorspecific->getHidden() && $object) {
            return array($this->vendorspecific->getParameterName() => $this->getVendorSpecificValue($object));
        } elseif ($this->vendorspecific->getVstype() === VendorSpecific::TYPE_VS_GROUP &&
            !$this->vendorspecific->getHidden() && $object) {
            return array($this->vendorspecific->getParameterName() => $this->getVendorSpecificValue($object));
        }
        return null;
    }

    public function isVendorSpecificValueValid()
    {
        if ($this->vendorspecific->getDefault()) {
            return true;
        } else {
            return false;
        }
    }
}
