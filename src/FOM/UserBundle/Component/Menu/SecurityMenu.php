<?php


namespace FOM\UserBundle\Component\Menu;

use FOM\UserBundle\Controller\SecurityController;
use Mapbender\ManagerBundle\Component\Menu\MenuItem;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityMenu extends MenuItem
{
    public function enabled(AuthorizationCheckerInterface $authorizationChecker)
    {
        /** @see SecurityController::indexAction */
        $userOid = new ObjectIdentity('class', 'FOM\UserBundle\Entity\User');
        $groupOid = new ObjectIdentity('class', 'FOM\UserBundle\Entity\Group');
        $aclOid = new ObjectIdentity('class', 'Symfony\Component\Security\Acl\Domain\Acl');

        return
            $authorizationChecker->isGranted('VIEW', $userOid)
            || $authorizationChecker->isGranted('VIEW', $groupOid)
            || $authorizationChecker->isGranted('EDIT', $aclOid)
        ;
    }
}
