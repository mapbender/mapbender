<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SourceMenu extends MenuItem
{
    public function enabled(AuthorizationCheckerInterface $authorizationChecker)
    {
        $sourceOid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        return $authorizationChecker->isGranted('VIEW', $sourceOid);
    }
}
