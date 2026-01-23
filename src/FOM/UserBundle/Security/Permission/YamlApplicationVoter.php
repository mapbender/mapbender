<?php

namespace FOM\UserBundle\Security\Permission;


use Mapbender\CoreBundle\Entity\Application;

class YamlApplicationVoter extends YamlBaseVoter
{
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === ResourceDomainApplication::ACTION_VIEW
            && $subject instanceof Application
            && $subject->getSource() === Application::SOURCE_YAML;
    }

    protected function allowOnEmptyRoles(): bool
    {
        return false;
    }
}
