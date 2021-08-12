<?php


namespace FOM\UserBundle\Controller;


use Mapbender\ManagerBundle\Component\ManagerBundle;

abstract class AbstractSecurityController extends UserControllerBase
{
    /**
     * @return string[]
     */
    protected function getACLClasses()
    {
        $aclClasses = array();
        foreach ($this->get('kernel')->getBundles() as $bundle) {
            if ($bundle instanceof ManagerBundle) {
                $aclClasses = \array_merge($aclClasses, $bundle->getACLClasses() ?: array());
            }
        }
        return $aclClasses;
    }
}
