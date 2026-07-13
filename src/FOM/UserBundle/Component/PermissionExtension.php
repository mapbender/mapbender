<?php

namespace FOM\UserBundle\Component;

use FOM\UserBundle\Security\Permission\PermissionManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PermissionExtension extends AbstractExtension
{
    public function __construct(private readonly PermissionManager $permissionManager)
    {
    }

    public function getFunctions(): array
    {
        return [
            'has_public_access' => new TwigFunction('has_public_access', $this->hasPublicAccess(...)),
            'mapbender_has_permissions' => new TwigFunction('mapbender_has_permissions', $this->hasPermissions(...)),
        ];
    }

    public function hasPublicAccess(mixed $resource, string $action = "view"): bool
    {
        return $this->permissionManager->isGranted(null, $resource, $action);
    }

    public function hasPermissions(mixed $resource): bool
    {
        return $this->permissionManager->hasPermissionsDefined($resource);
    }
}
