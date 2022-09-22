<?php


namespace FOM\UserBundle\Controller;


use Doctrine\Persistence\ManagerRegistry;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class SecurityController
{
    /** @var ManagerRegistry */
    protected $managerRegistry;
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;
    /** @var \Twig\Environment */
    protected $twig;
    /** @var string */
    protected $userEntityClass;
    /** @var string[] */
    protected $aclClasses;

    public function __construct(ManagerRegistry $managerRegistry,
                                AuthorizationCheckerInterface $authorizationChecker,
                                \Twig\Environment $twig,
                                $userEntityClass,
                                array $aclClasses)
    {
        $this->managerRegistry = $managerRegistry;
        $this->authorizationChecker = $authorizationChecker;
        $this->twig = $twig;
        $this->userEntityClass = $userEntityClass;
        $this->aclClasses = $aclClasses;
    }

    /**
     * @Route("/manager/security", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $userOid = new ObjectIdentity('class', 'FOM\UserBundle\Entity\User');
        $groupOid = new ObjectIdentity('class', 'FOM\UserBundle\Entity\Group');
        $aclOid = new ObjectIdentity('class', 'Symfony\Component\Security\Acl\Domain\Acl');

        $grants = array(
            'users' => $this->authorizationChecker->isGranted('VIEW', $userOid),
            'groups' => $this->authorizationChecker->isGranted('VIEW', $groupOid),
            'acl' => $this->authorizationChecker->isGranted('EDIT', $aclOid),
        );

        if (!array_filter($grants)) {
            throw new AccessDeniedException();
        }
        $vars = array(
            'grants' => $grants,
            'oids' => array(
                'user' => $userOid,
                'group' => $groupOid,
            ),
            'users' => array(),
            'groups' => array(),
        );
        foreach ($this->managerRegistry->getRepository(User::class)->findAll() as $user) {
            if ($this->authorizationChecker->isGranted('VIEW', $user)) {
                $vars['users'][] = $user;
            }
        }
        if ($grants['groups']) {
            $vars['groups'] = $this->managerRegistry->getRepository(Group::class)->findAll();
        }
        if ($grants['acl']) {
            $vars['acl_classes'] = $this->aclClasses;
        }

        return new Response($this->twig->render('@FOMUser/Security/index.html.twig', $vars));
    }
}
