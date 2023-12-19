<?php

namespace FOM\UserBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Service\FixAceOrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOM\ManagerBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Group management controller
 *
 * @author Christian Wygoda
 */
class GroupController extends AbstractController
{
    /** @var MutableAclProviderInterface */
    protected $aclProvider;

    private FixAceOrderService $fixAceOrderService;

    public function __construct(
        MutableAclProviderInterface $aclProvider,
        FixAceOrderService $fixAceOrderService
    )
    {
        $this->aclProvider = $aclProvider;
        $this->fixAceOrderService = $fixAceOrderService;
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
            $em = $this->getDoctrine()->getManager();
            $em->persist($group);

            // See method documentation for Doctrine weirdness
            foreach($group->getUsers() as $user) {
                $user->addGroup($group);
            }

            $em->flush();

            $objectIdentity = ObjectIdentity::fromDomainObject($group);
            $acl = $this->aclProvider->createAcl($objectIdentity);

            // retrieving the security identity of the currently logged-in user
            $securityIdentity = UserSecurityIdentity::fromAccount($this->getUser());

            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $this->aclProvider->updateAcl($acl);

            $this->addFlash('success', 'The group has been saved.');

            return $this->redirectToRoute('fom_user_security_index', array(
                '_fragment' => 'tabGroups',
            ));
        }

        return $this->render('@FOMUser/Group/form.html.twig', array(
            'group' => $group,
            'form' => $form->createView(),
            'title' => 'fom.user.group.form.new_group',
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
        /** @var Group|null $group */
        $group = $this->getDoctrine()->getRepository(Group::class)->find($id);
        if (!$group) {
            throw new NotFoundHttpException('The group does not exist');
        }
        $this->denyAccessUnlessGranted('EDIT', $group);

        /** @var EntityManagerInterface $em $em */
        $em = $this->getDoctrine()->getManagerForClass(Group::class);

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
            return $this->redirectToRoute('fom_user_security_index', array(
                '_fragment' => 'tabGroups',
            ));
        }

        return $this->render('@FOMUser/Group/form.html.twig', array(
            'group' => $group,
            'form' => $form->createView(),
            'title' => 'fom.user.group.form.edit_group',
        ));
    }

    /**
     * @Route("/group/{id}/delete", methods={"POST"})
     * @param string $id
     * @return Response
     */
    public function deleteAction(Request  $request, $id)
    {
        /** @var Group|null $group */
        $group = $this->getDoctrine()->getRepository(Group::class)->find($id);

        if($group === null) {
            throw new NotFoundHttpException('The group does not exist');
        }
        // ACL access check
        $this->denyAccessUnlessGranted('DELETE', $group);

        if (!$this->isCsrfTokenValid('group_delete', $request->request->get('token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return new Response();
        }


        /** @var EntityManagerInterface $em $em */
        $em = $this->getDoctrine()->getManagerForClass(Group::class);
        $em->beginTransaction();

        try {
            if (($this->aclProvider) instanceof MutableAclProvider) {
                $sid = new RoleSecurityIdentity($group->getRole());
                $this->aclProvider->deleteSecurityIdentity($sid);
            }

            $em->remove($group);

            $oid = ObjectIdentity::fromDomainObject($group);
            $this->aclProvider->deleteAcl($oid);

            $em->flush();
            $em->commit();
            $this->fixAceOrderService->fixAceOrder();
        } catch(\Exception $e) {
            $em->rollback();
            $this->addFlash('error', "The group couldn't be deleted.");
        }
        return new Response();
    }
}
