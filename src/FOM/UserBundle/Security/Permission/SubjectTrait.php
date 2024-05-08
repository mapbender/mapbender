<?php

namespace FOM\UserBundle\Security\Permission;

trait SubjectTrait
{
    public function getSubjectJson(): string
    {
        return json_encode([
            'domain' => $this->getSubjectDomain(),
            'user_id' => $this->getUser()?->getId(),
            'group_id' => $this->getGroup()?->getId(),
            'subject' => $this->getSubject()
        ]);
    }
}
