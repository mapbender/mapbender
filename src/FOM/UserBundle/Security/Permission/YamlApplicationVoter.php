<?php

namespace FOM\UserBundle\Security\Permission;


use FOM\UserBundle\Entity\User;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Yaml application's security is also supplied in the yaml file, therefore the
 * regular PermissionManager can't be used.
 *
 * The following yaml keys are relevant for it's security:
 * `published: true`: only used when `roles` is not present. It grants view rights to the public
 * `roles`: Can contain the following children:
 * - public: grants access to the public
 * - registered: grants access to all registered users:
 * - users (array): grants access to the given users by username
 * - groups (array): grants access to the given groups by group title
 *
 * Example:
 * roles:
 *   - users:
 *       - user1
 *       - user2
 *   - groups:
 *       - group1
 *       - group2
 */
class YamlApplicationVoter extends Voter
{
    public const ROLE_PUBLIC = "public";
    public const ROLE_REGISTERED = "registered";
    public const GROUPS = "groups";
    public const USERS = "users";

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === ResourceDomainApplication::ACTION_VIEW
            && $subject instanceof Application
            && $subject->getSource() === Application::SOURCE_YAML;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // only the view action is supported for yaml applications
        if ($attribute !== ResourceDomainApplication::ACTION_VIEW) return false;

        /** @var Application $subject */
        $roles = $subject->getYamlRoles();
        return $this->checkYamlRoles($roles, $token);
    }

    protected function checkYamlRoles(array $roles, TokenInterface $token): bool
    {
        /** @var UserInterface $user */
        $user = $token->getUser();

        foreach ($roles as $key => $role) {
            if ($role === self::ROLE_PUBLIC) return true;
            if ($role === self::ROLE_REGISTERED && $user !== null) return true;

            if (($key === self::USERS || $key === self::GROUPS) && is_array($role)) {
                if ($this->checkUserAndGroup($user, $key, $role)) return true;
            }

            if (is_array($role)) {
                foreach ($role as $innerKey => $children) {
                    if (is_array($children) && $this->checkUserAndGroup($user, $innerKey, $children) === true) return true;
                }
            }
        }

        return false;
    }

    protected function checkUserAndGroup(?UserInterface $user, string $key, array $children): bool
    {
        if ($user === null) return false;

        if ($key === self::USERS && in_array($user->getUserIdentifier(), $children, true)) {
            return true;
        }

        if ($key === self::GROUPS && $user instanceof User) {
            $groups = $user->getGroups();
            $groupTitles = [];
            foreach ($groups as $group) {
                $groupTitles[] = $group->getTitle();
            }

            if (array_intersect($children, $groupTitles)) return true;
        }

        return false;
    }
}
