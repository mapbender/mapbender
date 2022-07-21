<?php


namespace FOM\UserBundle\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class SecurityController extends UserControllerBase
{
    protected $aclClasses;

    public function __construct($userEntityClass, array $aclClasses)
    {
        parent::__construct($userEntityClass);
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
            'users' => $this->isGranted('VIEW', $userOid),
            'groups' => $this->isGranted('VIEW', $groupOid),
            'acl' => $this->isGranted('EDIT', $aclOid),
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
        foreach ($this->getUserRepository()->findAll() as $user) {
            if ($this->isGranted('VIEW', $user)) {
                $vars['users'][] = $user;
            }
        }
        if ($grants['groups']) {
            $vars['groups'] = $this->getDoctrine()->getRepository('FOM\UserBundle\Entity\Group')->findAll();
        }
        if ($grants['acl']) {
            $vars['acl_classes'] = $this->aclClasses;
        }

        return $this->render('@FOMUser/Security/index.html.twig', $vars);
    }
}
