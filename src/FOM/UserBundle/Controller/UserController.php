<?php
namespace FOM\UserBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * User management controller
 *
 * @author Christian Wygoda
 */
class UserController extends UserControllerBase
{
    /**
     * Renders user list.
     *
     * @ManagerRoute("/user", methods={"GET"})
     * @return Response
     */
    public function indexAction()
    {
        $users = $this->getUserRepository()->findAll();
        $allowed_users = array();

        // Bulk-prefetch ACLs for all User entities into AclProvider's internal cache
        $oids = array();
        foreach ($users as $index => $user) {
            $oids[] = ObjectIdentity::fromDomainObject($user);
        }
        $this->getAclManager()->getACLs($oids);

        foreach ($users as $index => $user) {
            if ($this->isGranted('VIEW', $user)) {
                $allowed_users[] = $user;
            }
        }

        $oid = new ObjectIdentity('class', 'FOM\UserBundle\Entity\User');

        return $this->render('@FOMUser/User/index.html.twig', array(
            'users'             => $allowed_users,
            'oid' => $oid,
            // @todo: remove create_permission template variable
            'group_oid' => new ObjectIdentity('class', 'FOM\UserBundle\Entity\Group'),
            'create_permission' => $this->isGranted('CREATE', $oid),
            'title' => $this->translate('fom.user.user.index.title'),
        ));
    }

    /**
     * @ManagerRoute("/user/new", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function createAction(Request $request)
    {
        $userClass = $this->getUserEntityClass();
        $oid = new ObjectIdentity('class', $userClass);
        $this->denyAccessUnlessGranted('CREATE', $oid);

        /** @var User $user */
        $user = new $userClass();
        return $this->userActionCommon($request, $user);
    }

    /**
     * @ManagerRoute("/user/{id}/edit", methods={"GET", "POST"})
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function editAction(Request $request, $id)
    {
        /** @var User|null $user */
        $user = $this->getUserRepository()->find($id);
        if ($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        $this->denyAccessUnlessGranted('EDIT', $user);
        return $this->userActionCommon($request, $user);
    }

    /**
     * @param Request $request
     * @param User $user
     * @return Response
     * @throws \Exception
     */
    protected function userActionCommon(Request $request, User $user)
    {
        $isNew = !$user->getId();
        $profileClass = $this->getProfileEntityClass();
        if ($profileClass) {
            if ($isNew) {
                $profile = new $profileClass();
                $user->setProfile($profile);
            }
        }

        $oid = new ObjectIdentity('class', get_class($user));
        $ownerGranted = $this->isGranted('OWNER', $isNew ? $oid : $user);
        $groupPermission =
            $this->isGranted('EDIT', new ObjectIdentity('class', 'FOM\UserBundle\Entity\Group'))
            || $ownerGranted;

        $form = $this->createForm('FOM\UserBundle\Form\Type\UserType', $user, array(
            'group_permission' => $groupPermission,
        ));
        if ($ownerGranted) {
            $aclOptions = array();
            if ($user->getId()) {
                $aclOptions['object_identity'] = ObjectIdentity::fromDomainObject($user);
            } else {
                $aclOptions['data'] = array(
                    array(
                        'sid' => UserSecurityIdentity::fromToken($this->getUserToken()),
                        'mask' => MaskBuilder::MASK_OWNER,
                    ),
                );
            }

            $form->add('acl', 'FOM\UserBundle\Form\Type\ACLType', $aclOptions);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $user->setRegistrationTime(new \DateTime());
            }
            $em = $this->getEntityManager();
            $em->beginTransaction();

            try {
                $this->persistUser($em, $user);

                if ($form->has('acl')) {
                    if (!$user->getId()) {
                        // Flush to assign PK
                        // This is necessary for users with no profile entity
                        // (persistUser already flushed once in this case)
                        $em->flush();
                    }
                    $aces = $form->get('acl')->getData();
                    $this->getAclManager()->setObjectACEs($user, $aces);
                }

                $em->flush();

                if ($isNew) {
                    // Make sure, the new user has VIEW & EDIT permissions
                    $this->getUserHelper()->giveOwnRights($user);
                }

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
                throw $e;
            }
            $this->addFlash('success', 'The user has been saved.');

            return $this->redirectToRoute('fom_user_security_index', array(
                '_fragment' => 'tabUsers',
            ));
        }
        return $this->render('@FOMUser/User/form.html.twig', array(
            'user'             => $user,
            'form'             => $form->createView(),
            'profile_template' => $this->getProfileTemplate(),
            'title' => $this->translate($isNew ? 'fom.user.user.form.new_user' : 'fom.user.user.form.edit_user'),
        ));
    }

    /**
     * @ManagerRoute("/user/{id}/delete", methods={"POST"})
     * @param string $id
     * @return Response
     *
     * @todo : Delete ACEs for given user
     */
    public function deleteAction($id)
    {
        $user = $this->getUserRepository()->find($id);

        if ($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }
        /** @var User $user */
        if ($user->getId() === 1) {
            throw new NotFoundHttpException('The root user can not be deleted');
        }

        $this->denyAccessUnlessGranted('DELETE', $user);
        $aclProvider = $this->getAclProvider();

        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if ($aclProvider instanceof MutableAclProvider) {
                $sid = UserSecurityIdentity::fromAccount($user);
                $aclProvider->deleteSecurityIdentity($sid);
            }
            $oid = ObjectIdentity::fromDomainObject($user);
            $aclProvider->deleteAcl($oid);

            $em->remove($user);
            if ($user->getProfile()) {
                $em->remove($user->getProfile());
            }
            $em->flush();
            $em->commit();
            $this->addFlash('success', 'The user has been deleted.');
        } catch (\Exception $e) {
            $em->rollback();
            $this->addFlash('error', "The user couldn't be deleted.");
        }

        return new Response();
    }

    /**
     * @return string|null
     */
    protected function getProfileEntityClass()
    {
        return $this->getParameter('fom_user.profile_entity');
    }

    /**
     * @return string
     */
    protected function getProfileTemplate()
    {
        return $this->getParameter('fom_user.profile_template');
    }

    /**
     * @param EntityManagerInterface $em
     * @param User $user
     * @internal
     */
    protected function persistUser(EntityManagerInterface $em, User $user)
    {
        if (($profile = $user->getProfile()) && !$user->getId()) {
            // flush user without profile to generate user pk first, then restore profile
            // @todo: invert bad relation direction user => profile (currently the profile owns the user)
            $user->setProfile(null);
            $em->persist($user);
            $em->flush();
            $user->setProfile($profile);
            $em->persist($profile);
        }
        $em->persist($user);
    }

    /**
     * @return TokenInterface|null
     */
    protected function getUserToken()
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->get('security.token_storage');
        return $tokenStorage->getToken();
    }
}
