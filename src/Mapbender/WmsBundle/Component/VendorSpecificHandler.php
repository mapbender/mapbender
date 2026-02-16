<?php

namespace Mapbender\WmsBundle\Component;

use FOM\UserBundle\Entity\Group;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * VendorSpecificHandler class for handling of VendorSpecific.
 *
 * @author Paul Schmidt
 * @internal
 *
 * Used by InstanceTunnelService and WmsInstanceConfigGenerator
 */
class VendorSpecificHandler
{
    /**
     * Scans the given $input string for a dynamic part. A "dynamic part" is an alphabetic character
     * sequence enclosed by a pair of '$' characters. This part is later substituted with (most
     * commonly) a user or group specific value.
     *
     * The first "dynamic part", if found, is returned INCLUDING the surrounding '$' characters.
     *
     * @param string $input
     * @return string|null
     */
    public function findDynamicValuePortion($input)
    {
        $matches = array();
        if (\preg_match('#\$[a-z]+\$#i', $input, $matches)) {
            return $matches[0];
        } else {
            return null;
        }
    }

    /**
     * Checks if a value contains dynamic parts. A "dynamic part" is an alphabetic character sequence
     * enclosed by a pair of '$' characters.
     *
     * @param string $value
     * @return boolean true if a value is dynamic.
     */
    public function isValueDynamic($value)
    {
        return !!$this->findDynamicValuePortion($value);
    }

    public function isValuePublic(VendorSpecific $vendorspec)
    {
        return !$vendorspec->getHidden();
    }

    /**
     * @param SourceInstance|WmsInstance $instance; NOTE: lax typing to avoid conflicts with WMTS
     * @param TokenInterface|null $userToken
     * @return string[]
     */
    public function getPublicParams(SourceInstance $instance, TokenInterface $userToken=null)
    {
        $user = $this->getUserFromToken($userToken);
        $params = array();
        foreach ($instance->getVendorspecifics() as $key => $vendorspec) {
            if ($this->isVendorSpecificValueValid($vendorspec) && $this->isValuePublic($vendorspec)) {
                $paramName = $vendorspec->getParameterName();
                $params[$paramName] = $this->getVendorSpecificValue($vendorspec, $user);
            }
        }
        return array_filter($params);
    }

    /**
     * @param SourceInstance|WmsInstance $instance; NOTE: lax typing to avoid conflicts with WMTS
     * @param TokenInterface|null $userToken
     * @return string[]
     */
    public function getHiddenParams(SourceInstance $instance, TokenInterface $userToken=null)
    {
        $user = $this->getUserFromToken($userToken);
        $params = array();
        foreach ($instance->getVendorspecifics() as $key => $vendorspec) {
            if ($this->isVendorSpecificValueValid($vendorspec) && !$this->isValuePublic($vendorspec)) {
                $paramName = $vendorspec->getParameterName();
                $params[$paramName] = $this->getVendorSpecificValue($vendorspec, $user);
            }
        }
        return array_filter($params);
    }

    /**
     * @param SourceInstance|WmsInstance $instance; NOTE: lax typing to avoid conflicts with WMTS
     * @param TokenInterface|null $userToken
     * @return string[]
     */
    public function getAllParams(SourceInstance $instance, TokenInterface $userToken=null)
    {
        $user = $this->getUserFromToken($userToken);
        $params = array();
        foreach ($instance->getVendorspecifics() as $key => $vendorspec) {
            if ($this->isVendorSpecificValueValid($vendorspec)) {
                $paramName = $vendorspec->getParameterName();
                $params[$paramName] = $this->getVendorSpecificValue($vendorspec, $user);
            }
        }
        return array_filter($params);
    }

    /**
     * Shortcut method equivalent to !!getHiddenParams($instance, <any token>), without requiring the token.
     * @param SourceInstance|WmsInstance $instance; NOTE: lax typing to avoid conflicts with WMTS
     * @return bool
     */
    public function hasHiddenParams(SourceInstance $instance)
    {
        foreach ($instance->getVendorspecifics() as $key => $vendorspec) {
            if ($this->isVendorSpecificValueValid($vendorspec) && !$this->isValuePublic($vendorspec)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param TokenInterface $userToken
     * @return UserInterface|null
     */
    protected function getUserFromToken(TokenInterface $userToken=null)
    {
        if (!$userToken || $userToken instanceof NullToken) {
            return null;
        } else {
            $user = $userToken->getUser();
            if ($user && $user instanceof UserInterface) {
                return $user;
            } else {
                return null;
            }
        }
    }

    /**
     * Returns the constant parameter value, or resolves a dynamically referenced value from the given object.
     * Dynamic references occur in the form '$id$' or '$groups$' and operate on FOM User entities.
     * Only for '$groups$': Returns the concatenated group ids from rom User->getGroups(), separated by comma.
     * Dynamic references on an empty $object return null.
     *
     * @param VendorSpecific $vs
     * @param UserInterface|null $object
     * @return string|null
     */
    public function getVendorSpecificValue(VendorSpecific $vs, $object)
    {
        $value = $vs->getDefault();
        if ($vs->getVstype() !== VendorSpecific::TYPE_VS_SIMPLE) {
            while ($dynamicPart = $this->findDynamicValuePortion($value)) {
                $substitution = $this->extractDynamicReference($vs, $object, trim($dynamicPart, '$'));
                $value = \str_replace($dynamicPart, $substitution, $value);
            }
        }
        return $value ?: null;
    }

    /**
     * @param VendorSpecific $vs
     * @param object $object
     * @param string $attributeName
     * @return string|null
     */
    protected function extractDynamicReference(VendorSpecific $vs, $object, $attributeName)
    {
        if (!$object || !is_object($object)) {
            return null;
        }
        if ($vs->getVstype() === VendorSpecific::TYPE_VS_GROUP && !($object instanceof Group)) {
            $values = array();
            if ($object instanceof \FOM\UserBundle\Entity\User) {
                $groups = $object->getGroups();
                foreach ($groups ?: array() as $fomGroup) {
                    $values[] = $this->extractDynamicReference($vs, $fomGroup, $attributeName);
                }
            }
            return implode(',', array_filter($values)) ?: null;
        }
        $attributeValue = EntityUtil::getValueFromGetter($object, $attributeName);
        // Special-case handling for getting the groups property from a User entity. We extract the ids and merge
        // them into a single string, comma-separated.
        // NOTE that this is different from a TYPE_VS_GROUP, where the property extracted from the group entities can be
        // configured freely.
        if (is_array($attributeValue) || (is_object($attributeValue) && ($attributeValue instanceof \Traversable))) {
            $groupIds = array();
            foreach ($attributeValue as $item) {
                if ($item instanceof Group) {
                    $groupIds[] = $item->getId();
                }
            }
            return implode(',', array_filter($groupIds)) ?: null;
        } else {
            return $attributeValue ?: null;
        }
    }

    public function isVendorSpecificValueValid(VendorSpecific $vs)
    {
        if ($vs->getDefault()) {
            return true;
        } else {
            return false;
        }
    }
}
