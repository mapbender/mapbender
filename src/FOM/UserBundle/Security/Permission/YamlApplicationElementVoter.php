<?php


namespace FOM\UserBundle\Security\Permission;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
/**
 * Yaml application's security is also supplied in the yaml file, therefore the
 * regular PermissionManager can't be used.
 * @see YamlApplicationVoter for the supported yaml format
 */
class YamlApplicationElementVoter extends YamlApplicationVoter
{
    protected function supports($attribute, $subject): bool
    {
        return $attribute === ResourceDomainElement::ACTION_VIEW
            && $subject instanceof Element
            && $subject->getApplication()?->getSource() === Application::SOURCE_YAML;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var Element $subject */
        $roleNames = $subject->getYamlRoles();
        if (!$roleNames) {
            // Empty list of roles => allow all
            return true;
        }

        return $this->checkYamlRoles($roleNames, $token);
    }
}
