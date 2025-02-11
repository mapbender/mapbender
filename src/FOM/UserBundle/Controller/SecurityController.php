<?php


namespace FOM\UserBundle\Controller;


use Doctrine\Persistence\ManagerRegistry;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class SecurityController
{
    public function __construct(protected ManagerRegistry               $managerRegistry,
                                protected AuthorizationCheckerInterface $authorizationChecker,
                                protected \Twig\Environment             $twig,
                                protected string                        $userEntityClass,
                                protected ResourceDomainInstallation    $installationPermissions,
    )
    {
    }

    #[Route(path: '/manager/security', methods: ['GET'])]
    public function indexAction(Request $request): Response
    {
        $grants = array(
            'users' => $this->authorizationChecker->isGranted(ResourceDomainInstallation::ACTION_VIEW_USERS),
            'groups' => $this->authorizationChecker->isGranted(ResourceDomainInstallation::ACTION_VIEW_GROUPS),
            'global_permissions' => $this->authorizationChecker->isGranted(ResourceDomainInstallation::ACTION_MANAGE_PERMISSION),
        );

        if (!array_filter($grants)) {
            throw new AccessDeniedException();
        }
        $vars = array(
            'grants' => $grants,
            'permission_categories' => $this->installationPermissions->getCategoryList(),
            'users' => $grants['users'] ? $this->managerRegistry->getRepository(User::class)->findAll() : [],
            'groups' => $grants['groups'] ? $this->managerRegistry->getRepository(Group::class)->findAll() : [],
        );

        return new Response($this->twig->render('@FOMUser/Security/index.html.twig', $vars));
    }
}
