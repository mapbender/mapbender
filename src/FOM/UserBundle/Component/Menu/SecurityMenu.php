<?php


namespace FOM\UserBundle\Component\Menu;

use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Mapbender\ManagerBundle\Component\Menu\MenuItem;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityMenu extends MenuItem
{
    public function enabled(AuthorizationCheckerInterface $authorizationChecker): bool
    {
        $securityActions = [
            ResourceDomainInstallation::ACTION_MANAGE_PERMISSION,
            ResourceDomainInstallation::ACTION_VIEW_USERS,
            ResourceDomainInstallation::ACTION_CREATE_USERS,
            ResourceDomainInstallation::ACTION_EDIT_USERS,
            ResourceDomainInstallation::ACTION_DELETE_USERS,
            ResourceDomainInstallation::ACTION_VIEW_GROUPS,
            ResourceDomainInstallation::ACTION_CREATE_GROUPS,
            ResourceDomainInstallation::ACTION_EDIT_GROUPS,
            ResourceDomainInstallation::ACTION_DELETE_GROUPS,
        ];

        foreach ($securityActions as $action) {
            if ($authorizationChecker->isGranted($action)) return true;
        }
        return false;
    }
}
