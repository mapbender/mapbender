<?php

namespace FOM\UserBundle\Security\Permission;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;

class YamlApplicationSourceInstanceVoter extends YamlApplicationElementVoter
{
    protected function supports($attribute, $subject): bool
    {
        return $attribute === ResourceDomainSourceInstance::ACTION_VIEW
            && $subject instanceof SourceInstance
            && $subject->getLayerset()?->getApplication()?->getSource() === Application::SOURCE_YAML;
    }
}
