<?php

namespace FOM\UserBundle\Controller;

use FOM\UserBundle\Entity\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOM\ManagerBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Group management controller
 *
 * @author Christian Wygoda
 */
class GroupController extends UserControllerBase
{
    /**
     * Renders group list.
     *
     * @Route("/group", methods={"GET"})
     * @return Response
     */
    public function indexAction()
    {
        $oid = new ObjectIdentity('class', 'FOM\UserBundle\Entity\Group');
        $this->denyAccessUnlessGranted('VIEW', $oid);
        $repository = $this->getEntityManager()->getRepository('FOM\UserBundle\Entity\Group');

        return $this->render('@FOMUser/Group/index.html.twig', array(
            'groups' => $repository->findAll(),
            'create_permission' => $this->isGranted('CREATE', $oid),
            'title' => $this->translate('fom.user.group.index.groups'),
        ));
    }

    /**
     * @Route("/group/new", methods={"GET", "POST"})
     *
     * There is one weirdness when storing groups: In Doctrine Many-to-Many
     * associations, updates are only written, when the owning side changes.
     * For the User-Group association, the user is the owner part.
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function createAction(Request $request)
    {
        $group = new Group();

        // ACL access check
        $oid = new ObjectIdentity('class', get_class($group));

        $this->denyAccessUnlessGranted('CREATE', $oid);

        $form = $this->createForm('FOM\UserBundle\Form\Type\GroupType', $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getEntityManager();
            $em->persist($group);

            // See method documentation for Doctrine weirdness
            foreach($group->getUsers() as $user) {
                $user->addGroup($group);
            }

            $em->flush();

            // creating the ACL
            $aclProvider = $this->getAclProvider();
            $objectIdentity = ObjectIdentity::fromDomainObject($group);
            $acl = $aclProvider->createAcl($objectIdentity);

            // retrieving the security identity of the currently logged-in user
            $securityIdentity = UserSecurityIdentity::fromAccount($this->getUser());

            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);

            $this->addFlash('success', 'The group has been saved.');

            return $this->redirectToRoute('fom_user_group_index');
        }

        return $this->render('@FOMUser/Group/form.html.twig', array(
            'group' => $group,
            'form' => $form->createView(),
            'title' => $this->translate('fom.user.group.form.new_group'),
        ));
    }

    /**
     * @Route("/group/{id}/edit", methods={"GET", "POST"})
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getEntityManager();
        /** @var Group|null $group */
        $group = $em->getRepository('FOMUserBundle:Group')->find($id);
        if (!$group) {
            throw new NotFoundHttpException('The group does not exist');
        }
        $this->denyAccessUnlessGranted('EDIT', $group);

        $form = $this->createForm('FOM\UserBundle\Form\Type\GroupType', $group);

        // see https://afilina.com/doctrine-not-saving-manytomany
        foreach ($group->getUsers() as $previousUser) {
            $previousUser->getGroups()->removeElement($group);
            $em->persist($previousUser);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($group->getUsers() as $currentUser) {
                $em->persist($currentUser);
                $currentUser->getGroups()->add($group);
            }
            $em->flush();

            $this->addFlash('success', 'The group has been updated.');
            return $this->redirectToRoute('fom_user_group_index');
        }

        return $this->render('@FOMUser/Group/form.html.twig', array(
            'group' => $group,
            'form' => $form->createView(),
            'title' => $this->translate('fom.user.group.form.edit_group'),
        ));
    }

    /**
     * @Route("/group/{id}/delete", methods={"POST"})
     * @param string $id
     * @return Response
     */
    public function deleteAction($id)
    {
        /** @var Group|null $group */
        $group = $this->getDoctrine()->getRepository('FOMUserBundle:Group')
            ->find($id);

        if($group === null) {
            throw new NotFoundHttpException('The group does not exist');
        }
        // ACL access check
        $this->denyAccessUnlessGranted('DELETE', $group);
        $aclProvider = $this->getAclProvider();
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if ($aclProvider instanceof MutableAclProvider) {
                $sid = new RoleSecurityIdentity($group->getRole());
                $aclProvider->deleteSecurityIdentity($sid);
            }

            $em->remove($group);

            $oid = ObjectIdentity::fromDomainObject($group);
            $aclProvider->deleteAcl($oid);

            $em->flush();
            $em->commit();
        } catch(\Exception $e) {
            $em->rollback();
            $this->addFlash('error', "The group couldn't be deleted.");
        }
        return new Response();
    }
}
