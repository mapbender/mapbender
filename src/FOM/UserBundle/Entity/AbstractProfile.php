<?php


namespace FOM\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
abstract class AbstractProfile
{
    /**
     * No annotations here, the Doctrine metadata is added dynamically in the
     * loadClassMetadata event in
     * @see \FOM\UserBundle\EventListener\UserProfileListener::loadClassMetadata()
     */
    protected $uid;

    /**
     * @param User $uid
     * @return $this
     */
    public function setUid(User $uid)
    {
        $this->uid = $uid;

        return $this;
    }
}
