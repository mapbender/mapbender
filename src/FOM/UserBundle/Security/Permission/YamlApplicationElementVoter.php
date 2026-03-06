<?php


namespace FOM\UserBundle\Security\Permission;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class YamlApplicationElementVoter extends YamlBaseVoter
{
    protected function supports($attribute, $subject): bool
    {
        return $attribute === ResourceDomainElement::ACTION_VIEW
            && $subject instanceof Element
            && $subject->getApplication()?->getSource() === Application::SOURCE_YAML;
    }
}
