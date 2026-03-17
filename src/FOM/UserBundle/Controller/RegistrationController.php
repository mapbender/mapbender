<?php

namespace FOM\UserBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Component\UserHelperService;
use FOM\UserBundle\Entity\Group;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Self registration controller.
 *
 * @author Christian Wygoda
 * @author Paul Schmidt
 */

class RegistrationController extends AbstractEmailProcessController
{
    public function __construct(MailerInterface $mailer,
                                TranslatorInterface $translator,
                                protected UserHelperService $userHelper,
                                ManagerRegistry $doctrine,
                                $userEntityClass,
                                $emailFromAddress,
                                $emailFromName,
                                protected $enableRegistration,
                                protected $maxTokenAge,
                                protected array $groupTitles,
                                $isDebug)
    {
        parent::__construct($mailer, $translator, $doctrine, $userEntityClass, $emailFromAddress, $emailFromName, $isDebug);
        if (!$this->enableRegistration) {
            $this->debug404("Registration disabled by configuration");
        }
    }

    /**
     * Registration step 3: Show instruction page that email has been sent
     */
    #[Route(path: '/user/registration/send', methods: ['GET'])]
    public function send(): Response
    {
        return $this->render('@FOMUser/Registration/send.html.twig');
    }

    /**
     * Registration step 1: Registration form
     *
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/user/registration', methods: ['GET', 'POST'])]
    public function form(Request $request)
    {
        $userClass = $this->userEntityClass;
        /** @var User $user */
        $user = new $userClass();
        $form = $this->createForm('FOM\UserBundle\Form\Type\UserRegistrationType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRegistrationToken(hash("sha1",rand()));
            $user->setRegistrationTime(new \DateTime());

            $groupRepository = $this->getEntityManager()->getRepository(Group::class);
            foreach ($this->groupTitles as $groupTitle) {
                /** @var Group|null $group */
                $group = $groupRepository->findOneBy(array(
                    'title' => $groupTitle,
                ));
                if ($group) {
                    $user->addGroup($group);
                } else {
                    throw new \RuntimeException("Self-registration group '{$groupTitle}' not found for user '{$user->getUserIdentifier()}'");
                }
            }

            $this->sendRegistrationMail($user);

            $em = $this->getEntityManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('fom_user_registration_send');
        }

        return $this->render('@FOMUser/Registration/form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Registration step 4: Activate account by token
     *
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/user/activate', methods: ['GET'])]
    public function confirm(Request $request)
    {
        $token = $request->query->get('token');
        $user = $this->getUserFromRegistrationToken($token);
        if (!$user) {
            return $this->render('@FOMUser/Login/error-notoken.html.twig', array(
                'site_email' => $this->emailFromAddress,
            ));
        }

        if(!$this->checkTimeInterval($user->getRegistrationTime(), $this->maxTokenAge)) {
            return $this->render('@FOMUser/Login/error-tokenexpired.html.twig', array(
                'url' => $this->generateUrl('fom_user_registration_reset', array(
                    'token' => $user->getRegistrationToken(),
                )),
            ));
        }

        // Unset token
        $em = $this->getEntityManager();
        $user->setRegistrationToken(null);
        $em->flush();

        // Forward to final page
        return $this->redirectToRoute('fom_user_registration_done');
    }

    /**
     * Registration step 4a: Reset token (if expired)
     *
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/user/registration/reset')]
    public function reset(Request $request)
    {
        $token = $request->query->get('token');
        $user = $this->getUserFromRegistrationToken($token);
        if(!$user) {
            return $this->render('@FOMUser/Login/error-notoken.html.twig', array(
                'site_email' => $this->emailFromAddress,
            ));
        }

        $user->setRegistrationToken(hash("sha1",rand()));
        $user->setRegistrationTime(new \DateTime());

        $this->sendRegistrationMail($user);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('fom_user_registration_send');
    }

    /**
     * Registration step 5: Welcome new user
     *
     * @return Response
     */
    #[Route(path: '/user/registration/done', methods: ['GET'])]
    public function done(): Response
    {
        return $this->render('@FOMUser/Registration/done.html.twig');
    }

    /**
     * @param User $user
     */
    protected function sendRegistrationMail($user)
    {
       $text = $this->renderView('@FOMUser/Registration/email-body.text.twig', array("user" => $user));
       $html = $this->renderView('@FOMUser/Registration/email-body.html.twig', array("user" => $user));
        $subject = $this->translator->trans('fom.user.registration.email_subject');
        $this->sendEmail($user->getEmail(), $subject, $text, $html);
    }

    /**
     * @param string $token
     * @return User|null
     */
    protected function getUserFromRegistrationToken($token)
    {
        if ($token) {
            /** @var User|null $user */
            $user = $this->getUserRepository()->findOneBy(array(
                'registrationToken' => $token,
            ));
            return $user;
        } else {
            return null;
        }
    }
}
