<?php

namespace FOM\UserBundle\Controller;

use FOM\UserBundle\Component\AclManager;
use FOM\UserBundle\Component\AssignableSecurityIdentityFilter;
use FOM\ManagerBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Translation\TranslatorInterface;

class ACLController extends UserControllerBase
{
    protected $aclClasses;

    public function __construct($userEntityClass, array $aclClasses)
    {
        parent::__construct($userEntityClass);
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

        if(!array_key_exists($class, $this->aclClasses)) {
            throw $this->createNotFoundException('No manageable class given.');
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
            /** @var AclManager $aclManager */
            $aclManager = $this->get('fom.acl.manager');
            $aclManager->setClassACEs($class, $form->get('acl')->getData());

            return $this->redirectToRoute('fom_user_security_index', array(
                '_fragment' => 'tabAcl',
            ));
        } elseif ($form->isSubmitted()) {
            $this->addFlash('error', 'Your form has errors, please review them below.');
        }

        $translator = $this->getTranslator();
        return $this->render('@FOMUser/ACL/edit.html.twig', array(
            'class' => $class,
            'form' => $form->createView(),
            'title' => $translator->trans('fom.user.acl.edit.edit_class_acl', array(
                '%name%' => $translator->trans($this->aclClasses[$class]),
            ))
        ));
    }

    /**
     * @Route("/acl/overview", methods={"GET"})
     * @return Response
     */
    public function overviewAction()
    {
        /** @var AssignableSecurityIdentityFilter $filter */
        $filter = $this->get('fom.acl_assignment_filter');
        $users = $filter->getAssignableUsers();
        $groups = $filter->getAssignableGroups();

        return $this->render('@FOMUser/ACL/groups-and-users.html.twig', array(
            'groups' => $groups,
            'users' => $users,
        ));
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        return $translator;
    }

}
