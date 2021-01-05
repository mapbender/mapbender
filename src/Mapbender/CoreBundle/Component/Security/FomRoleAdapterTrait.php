<?php


namespace Mapbender\CoreBundle\Component\Security;


use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trait FomRoleAdapterTrait
{
    /**
     * Get role names from given token INCLUDING local database roles (FOM Group entities).
     * @todo (in FOM): FOM-managed roles should already be on the token
     *
     * @param TokenInterface $token
     * @return string[]
     */
    protected function getRoleNamesFromToken(TokenInterface $token)
    {
        $names = array();
        foreach ($token->getRoles() as $tokenRole) {
            $names[] = $tokenRole->getRole();
        }
        $user = $token->getUser();
        if ($user && \is_object($user) && ($user instanceof \FOM\UserBundle\Entity\User)) {
            // custom FOM Group entity assignment roles are NOT visible via token->getRoles
            // @todo: fix this in FOM
            $names = array_values(array_unique(array_merge($names, $user->getRoles())));
        }
        return $names;
    }
}
