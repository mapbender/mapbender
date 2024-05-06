<?php

namespace FOM\UserBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use FOM\UserBundle\Entity\User;


class UserSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'preUpdate',
        );
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        if (! $entity instanceof User) {
            return;
        }

        if($args->hasChangedField('username')) {
            $class = ClassUtils::getRealClass(\get_class($entity));
            $old_username = $args->getOldValue('username');
            $new_username = $args->getNewValue('username');

            $entityManager->getConnection()->update(
                'acl_security_identities',
                array(
                    'identifier' => sprintf('%s-%s', $class, $new_username)),
                array(
                    'identifier' => sprintf('%s-%s', $class, $old_username)));

        }
    }
}
