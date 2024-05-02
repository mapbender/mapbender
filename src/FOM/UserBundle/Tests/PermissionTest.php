<?php

namespace FOM\UserBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Security\Permission\PermissionManager;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionTest extends KernelTestCase
{

    private PermissionManager $permissionManager;
    private EntityRepository $userRepo;

    /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->permissionManager = $container->get(PermissionManager::class);
        $em = $container->get(EntityManagerInterface::class);
        $this->userRepo = $em->getRepository(User::class);
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


}
