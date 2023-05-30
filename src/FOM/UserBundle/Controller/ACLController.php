<?php

namespace FOM\UserBundle\Controller;

use FOM\UserBundle\Component\AclManager;
use FOM\UserBundle\Component\AssignableSecurityIdentityFilter;
use FOM\ManagerBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class ACLController extends AbstractController
{
    /** @var AclManager */
    protected $aclManager;
    /** @var AssignableSecurityIdentityFilter */
    protected $sidFilter;
    protected $aclClasses;

    public function __construct(AclManager $aclManager,
                                AssignableSecurityIdentityFilter $sidFilter,
                                array $aclClasses)
    {
        $this->aclManager = $aclManager;
        $this->sidFilter = $sidFilter;
        $this->aclClasses = $aclClasses;
    }

    /**
     * @Route("/acl/edit", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     */
    public function editAction(Request $request)
    {
        // ACL access check
        $oid = new ObjectIdentity('class', 'Symfony\Component\Security\Acl\Domain\Acl');

        $this->denyAccessUnlessGranted('EDIT', $oid);

        $class = $request->query->get('class');

        if (!array_key_exists($class, $this->aclClasses)) {
            throw new NotFoundHttpException();
        }
        $form = $this->createForm('Symfony\Component\Form\Extension\Core\Type\FormType', null, array(
            'label' => false,
        ));

        $form->add('acl', 'FOM\ManagerBundle\Form\Type\ClassAclType', array(
            'class' => $class,
            'label' => false,
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->aclManager->setClassACEs($class, $form->get('acl')->getData());

            return $this->redirectToRoute('fom_user_security_index', array(
                '_fragment' => 'tabAcl',
            ));
        } elseif ($form->isSubmitted()) {
            $this->addFlash('error', 'Your form has errors, please review them below.');
        }


        return $this->render('@FOMUser/ACL/edit.html.twig', array(
            'class' => $class,
            'form' => $form->createView(),
            'acl_class' => $this->aclClasses[$class],
        ));
    }

    /**
     * @Route("/acl/overview", methods={"GET"})
     * @return Response
     */
    public function overviewAction()
    {
        return $this->render('@FOMUser/ACL/groups-and-users.html.twig', array(
            'groups' => $this->sidFilter->getAssignableGroups(),
            'users' => $this->sidFilter->getAssignableUsers(),
        ));
    }
}
