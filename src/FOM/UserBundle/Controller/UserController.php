<?php
namespace FOM\UserBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Component\AclManager;
use FOM\UserBundle\Component\UserHelperService;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Service\FixAceOrderService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User management controller
 *
 * @author Christian Wygoda
 */
class UserController extends UserControllerBase
{
    /** @var MutableAclProviderInterface */
    protected $aclProvider;
    /** @var UserHelperService */
    protected $userHelper;
    /** @var AclManager */
    protected $aclManager;

    protected $profileEntityClass;
    protected $profileTemplate;

    private FixAceOrderService $fixAceOrderService;

    public function __construct(MutableAclProviderInterface $aclProvider,
                                UserHelperService $userHelper,
                                AclManager $aclManager,
                                FixAceOrderService $fixAceOrderService,
                                $userEntityClass,
                                $profileEntityClass,
                                $profileTemplate)
    {
        parent::__construct($userEntityClass);
        $this->aclProvider = $aclProvider;
        $this->userHelper = $userHelper;
        $this->aclManager = $aclManager;
        $this->fixAceOrderService = $fixAceOrderService;
        $this->profileEntityClass = $profileEntityClass;
        $this->profileTemplate = $profileTemplate;
    }

    /**
     * @ManagerRoute("/user/new", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function createAction(Request $request)
    {
        $userClass = $this->userEntityClass;
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
        $profileClass = $this->profileEntityClass;
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
                $currentUser = $this->getUser();
                if ($currentUser && ($currentUser instanceof UserInterface)) {
                    $aclOptions['data'] = array(
                        array(
                            'sid' => UserSecurityIdentity::fromAccount($currentUser),
                            'mask' => MaskBuilder::MASK_OWNER,
                        ),
                    );
                }
            }

            $form->add('acl', 'FOM\UserBundle\Form\Type\ACLType', $aclOptions);
        }
        $securityIndexGranted = $this->isGranted('VIEW', new ObjectIdentity('class', 'FOM\UserBundle\Entity\User'));

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
                    $this->aclManager->setObjectACEs($user, $aces);
                }

                $em->flush();

                if ($isNew) {
                    // Make sure, the new user has VIEW & EDIT permissions
                    $this->userHelper->giveOwnRights($user);
                }

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
                throw $e;
            }
            $this->addFlash('success', 'The user has been saved.');

            // Do not redirect to security index if access will be denied
            if ($securityIndexGranted) {
                return $this->redirectToRoute('fom_user_security_index', array(
                    '_fragment' => 'tabUsers',
                ));
            }
        }
        return $this->render('@FOMUser/User/form.html.twig', array(
            'user'             => $user,
            'form'             => $form->createView(),
            'profile_template' => $this->profileTemplate,
            'title' => $isNew ? 'fom.user.user.form.new_user' : 'fom.user.user.form.edit_user',
            'return_url' => (!$securityIndexGranted) ? false : $this->generateUrl('fom_user_security_index', array(
                '_fragment' => 'tabUsers'
            )),
        ));
    }

    /**
     * @ManagerRoute("/user/{id}/delete", methods={"POST"})
     * @param string $id
     * @return Response
     */
    public function deleteAction(Request $request, $id)
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

        if (!$this->isCsrfTokenValid('user_delete', $request->request->get('token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return new Response();
        }

        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if (($this->aclProvider) instanceof MutableAclProvider) {
                $sid = UserSecurityIdentity::fromAccount($user);
                $this->aclProvider->deleteSecurityIdentity($sid);
            }
            $oid = ObjectIdentity::fromDomainObject($user);
            $this->aclProvider->deleteAcl($oid);

            $em->remove($user);
            if ($user->getProfile()) {
                $em->remove($user->getProfile());
            }
            $em->flush();
            $em->commit();
            $this->fixAceOrderService->fixAceOrder();
            $this->addFlash('success', 'The user has been deleted.');
        } catch (\Exception $e) {
            $em->rollback();
            $this->addFlash('error', "The user couldn't be deleted.");
        }

        return new Response();
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
}
