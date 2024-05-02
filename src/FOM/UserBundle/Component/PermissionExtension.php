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
        return array(
            'has_public_access' => new TwigFunction('has_public_access', array($this, 'has_public_access')),
        );
    }

    public function has_public_access(mixed $resource, string $action = "view"): bool
    {
        return $this->permissionManager->isGranted(null, $resource, $action);
    }
}
