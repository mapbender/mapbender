<?php

namespace FOM\UserBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Component\UserHelperService;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * User management controller
 *
 * @author Christian Wygoda
 */
class UserController extends UserControllerBase
{
    public function __construct(protected UserHelperService           $userHelper,
                                protected ManagerRegistry $doctrine,
                                ?string                               $userEntityClass,
                                protected ?string                     $profileEntityClass,
                                protected ?string                     $profileTemplate)
    {
        parent::__construct($userEntityClass, $doctrine);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[ManagerRoute('/user/new', methods: ['GET', 'POST'])]
    public function create(Request $request)
    {
        $userClass = $this->userEntityClass;
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_CREATE_USERS);

        /** @var User $user */
        $user = new $userClass();
        return $this->createOrEditUser($request, $user);
    }

    #[ManagerRoute('/user/{id}/edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, $id)
    {
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_EDIT_USERS)) {
            $loggedInUser = $this->getUser();
            if (!($loggedInUser instanceof User) || !($loggedInUser->getId() === intval($id))) {
                throw $this->createAccessDeniedException();
            }
        }

        /** @var User|null $user */
        $user = $this->getUserRepository()->find($id);
        if ($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        return $this->createOrEditUser($request, $user);
    }

    /**
     * @param Request $request
     * @param User $user
     * @return Response
     * @throws \Exception
     */
    protected function createOrEditUser(Request $request, User $user)
    {
        $isNew = !$user->getId();
        $profileClass = $this->profileEntityClass;
        if ($profileClass && $isNew) {
            $profile = new $profileClass();
            $user->setProfile($profile);
        }

        $groupPermission = $this->isGranted(ResourceDomainInstallation::ACTION_EDIT_GROUPS);

        $form = $this->createForm('FOM\UserBundle\Form\Type\UserType', $user, array(
            'group_permission' => $groupPermission,
        ));


        $securityIndexGranted = $this->isGranted(ResourceDomainInstallation::ACTION_VIEW_USERS);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $user->setRegistrationTime(new \DateTime());
            }
            $em = $this->getEntityManager();
            $em->beginTransaction();

            try {
                $this->persistUser($em, $user);
                $em->flush();

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

        $view = $form->createView();
        /*
         * Display groups sorted by label;
         * sorting takes place here because it is not easy to implement in twig
         */
        if (array_key_exists('groups', $view->children)) {
            $groups = $view->children['groups'];
            usort($groups->children, function ($a, $b) {
                return strcasecmp($a->vars['label'], $b->vars['label']);
            });
        }
        return $this->render('@FOMUser/User/form.html.twig', array(
            'user' => $user,
            'form' => $view,
            'profile_template' => $this->profileTemplate,
            'title' => $isNew ? 'fom.user.user.form.new_user' : 'fom.user.user.form.edit_user',
            'return_url' => (!$securityIndexGranted) ? false : $this->generateUrl('fom_user_security_index', array(
                '_fragment' => 'tabUsers'
            )),
        ));
    }

    /**
     * @param string $id
     * @return Response
     */
    #[ManagerRoute('/user/{id}/delete', methods: ['POST'])]
    public function delete(Request $request, $id)
    {
        $user = $this->getUserRepository()->find($id);

        if ($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }
        /** @var User $user */
        if ($user->getId() === 1) {
            throw new NotFoundHttpException('The root user can not be deleted');
        }

        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_DELETE_USERS);

        if (!$this->isCsrfTokenValid('user_delete', $request->request->get('token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return new Response();
        }

        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
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
