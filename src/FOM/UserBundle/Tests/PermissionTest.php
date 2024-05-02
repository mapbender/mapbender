<?php

namespace FOM\UserBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Security\Permission\PermissionManager;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use FOM\UserBundle\Security\Permission\SubjectDomainPublic;
use FOM\UserBundle\Security\Permission\SubjectDomainRegistered;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionTest extends KernelTestCase
{

    private PermissionManager $permissionManager;
    private EntityRepository $userRepo;
    private EntityManagerInterface $em;

    /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->permissionManager = $container->get(PermissionManager::class);
        $this->em = $container->get(EntityManagerInterface::class);
        $this->userRepo = $this->em->getRepository(User::class);
    }

    private function getTokenForUser(string $username): TokenInterface
    {
        $user = $this->userRepo->findOneBy(['username' => $username]);
        return new PreAuthenticatedToken($user, "default");
    }

    function testGrantAndRevoke(): void
    {
        $tokenUser1 = $this->getTokenForUser("user1");
        $tokenUser2 = $this->getTokenForUser("user2");
        $action = ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS;

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser1, null, [$action])
        );
        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser2, null, [$action])
        );

        $this->permissionManager->grant($tokenUser1->getUser(), null, $action);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED,
            $this->permissionManager->vote($tokenUser1, null, [$action])
        );
        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser2, null, [$action])
        );

        $this->permissionManager->revoke($tokenUser1->getUser(), null, $action);

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser1, null, [$action])
        );
    }

    function testPermissionOnApplication(): void
    {
        $tokenUser = $this->getTokenForUser("user1");
        $application = $this->em->getRepository(Application::class)->findOneBy(['slug' => 'mapbender_user_db']);
        $otherApplication = $this->em->getRepository(Application::class)->findOneBy(['slug' => 'mapbender_user_basic_db']);
        $action = ResourceDomainApplication::ACTION_EDIT;

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, $application, [$action])
        );
        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, $otherApplication, [$action])
        );


        $this->permissionManager->grant($tokenUser->getUser(), $application, $action);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED,
            $this->permissionManager->vote($tokenUser, $application, [$action])
        );

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, $otherApplication, [$action])
        );


        $this->permissionManager->revoke($tokenUser->getUser(), $application, $action);

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, $application, [$action])
        );

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, $otherApplication, [$action])
        );
    }

    function testGrantOnGroup(): void
    {
        $tokenUser = $this->getTokenForUser("user1");
        $group = $this->em->getRepository(Group::class)->findOneBy(['title' => 'group1']);
        $action = ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS;

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, null, [$action])
        );

        $this->permissionManager->grant($group, null, $action);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED,
            $this->permissionManager->vote($tokenUser, null, [$action])
        );

        $this->permissionManager->revoke($group, null, $action);

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, null, [$action])
        );
    }

    function testGrantPublicAccess(): void
    {
        $tokenUser = $this->getTokenForUser("user1");
        $action = ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS;

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, null, [$action])
        );

        $this->permissionManager->grant(SubjectDomainPublic::SLUG, null, $action);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED,
            $this->permissionManager->vote($tokenUser, null, [$action])
        );

        $this->permissionManager->revoke(SubjectDomainPublic::SLUG, null, $action);

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, null, [$action])
        );
    }

    function testGrantRegisteredUsers(): void
    {
        $tokenUser = $this->getTokenForUser("user1");
        $action = ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS;

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, null, [$action])
        );

        $this->permissionManager->grant(SubjectDomainRegistered::SLUG, null, $action);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED,
            $this->permissionManager->vote($tokenUser, null, [$action])
        );

        $this->permissionManager->revoke(SubjectDomainRegistered::SLUG, null, $action);

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $this->permissionManager->vote($tokenUser, null, [$action])
        );
    }


}
