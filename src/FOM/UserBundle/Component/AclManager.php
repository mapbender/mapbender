<?php

namespace FOM\UserBundle\Component;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * ACL utility service; registered as 'fom.acl.manager'
 *
 * This manager is available as a service called 'fom.acl.manager' and is meant
 * to be used with a form and will delete/update/add ACEs.
 *
 * @author     Christian Wygoda
 * @author     Andriy Oblivantsev
 */
class AclManager
{
    /** @var MutableAclProviderInterface */
    protected $aclProvider;

    /**
     * AclManager constructor.
     *
     * @param MutableAclProviderInterface $aclProvider
     */
    public function __construct(MutableAclProviderInterface $aclProvider)
    {
        $this->aclProvider = $aclProvider;
    }

    /**
     * Replace entity ACEs
     * @param object $entity
     * @param array $aces
     * @throws \Exception
     */
    public function setObjectACEs($entity, $aces)
    {
        $acl = $this->getACL($entity);
        $oldAces = $acl->getObjectAces();

        // Delete old ACEs
        foreach (array_reverse(array_keys($oldAces)) as $idx) {
            $acl->deleteObjectAce(intval($idx));
        }
        $this->aclProvider->updateAcl($acl);
        // Add new ACEs
        foreach (array_reverse($aces) as $idx => $ace) {
            // If no mask is given, we might as well not insert the ACE
            if ($ace['mask'] === 0) {
                continue;
            }
            $acl->insertObjectAce($ace['sid'], $ace['mask']);
        }

        $this->aclProvider->updateAcl($acl);
    }

    /**
     * @param $class
     * @param array[] $aces
     * @throws \Exception
     */
    public function setClassACEs($class, $aces)
    {
        $acl = $this->getACL($class);
        $oldAces = $acl->getClassAces();

        // @TODO: This is a naive implementation, we should update where
        // possible instead of deleting all old ones and adding all new ones...

        // Delete old ACEs
        foreach (array_reverse(array_keys($oldAces)) as $idx) {
            $acl->deleteClassAce($idx);
        }
        $this->aclProvider->updateAcl($acl);
        // Add new ACEs
        foreach (array_reverse($aces) as $idx => $ace) {
            // If no mask is given, we might as well not insert the ACE
            if ($ace['mask'] === 0) {
                continue;
            }
            $acl->insertClassAce($ace['sid'], $ace['mask']);
        }

        $this->aclProvider->updateAcl($acl);
    }

    /**
     * Get ACL object manager.
     *
     * @param object|string $entity or class name
     * @param bool $create
     * @return MutableAclInterface|AclInterface|null
     * @throws \Exception
     * @throws \Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException
     */
    public function getACL($entity, $create = true)
    {
        if (is_object($entity)) {
            $oid = ObjectIdentity::fromDomainObject($entity);
        } else {
            $oid = new ObjectIdentity('class', $entity);
        }
        try {
            return $this->aclProvider->findAcl($oid);
        } catch (NotAllAclsFoundException $e) {
            return $e->getPartialResult();
        } catch (AclNotFoundException $e) {
            if ($create){
                return $this->aclProvider->createAcl($oid);
            } else {
                return null;
            }
        }
    }

    /**
     * @param ObjectIdentityInterface[]
     * @return \SplObjectStorage
     */
    public function getACLs(array $oids)
    {
        try {
            return $this->aclProvider->findAcls($oids);
        } catch (NotAllAclsFoundException $e) {
            return $e->getPartialResult();
       } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
            return new \SplObjectStorage();
        }
    }
}
