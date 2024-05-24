<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SourceMenu extends MenuItem
{
    public function enabled(AuthorizationCheckerInterface $authorizationChecker): bool
    {
        return $authorizationChecker->isGranted(ResourceDomainInstallation::ACTION_VIEW_SOURCES);
    }
}
