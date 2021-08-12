<?php

namespace FOM\UserBundle\Controller;

use FOM\UserBundle\Entity\Group;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOM\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Self registration controller.
 *
 * @author Christian Wygoda
 * @author Paul Schmidt
 */

class RegistrationController extends UserControllerBase
{
    protected $emailFromAddress;
    protected $emailFromName;
    protected $enableRegistration;
    protected $maxTokenAge;
    protected $groupTitles;
    protected $isDebug;

    public function __construct($userEntityClass,
                                $emailFromAddress,
                                $emailFromName,
                                $enableRegistration,
                                $maxTokenAge,
                                array $groupTitles,
                                $isDebug)
    {
        parent::__construct($userEntityClass);
        $this->emailFromAddress = $emailFromAddress;
        $this->emailFromName = $emailFromName ?: $emailFromAddress;
        $this->enableRegistration = $enableRegistration;
        $this->groupTitles = $groupTitles;
        $this->maxTokenAge = $maxTokenAge;
        $this->isDebug = $isDebug;
        if (!$this->emailFromAddress) {
            $this->debug404("Sender mail not configured. See UserBundle/CONFIGURATION.md");
        }
        if (!$this->enableRegistration) {
            $this->debug404("Registration disabled by configuration");
        }
    }

    /**
     * Throws a 404, displaying the given $message only in debug mode
     * @todo: fold copy&paste PasswordController vs RegistrationController
     *
     * @param string|null $message
     * @throws NotFoundHttpException
     */
    protected function debug404($message)
    {
        if ($this->isDebug && $message) {
            $message = $message . ' (this message is only display in debug mode)';
            throw new NotFoundHttpException($message);
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * Registration step 3: Show instruction page that email has been sent
     *
     * @Route("/user/registration/send", methods={"GET"})
     * @return Response
     */
    public function sendAction()
    {
        return $this->render('@FOMUser/Registration/send.html.twig');
    }

    /**
     * Registration step 1: Registration form
     *
     * @Route("/user/registration", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     */
    public function formAction(Request $request)
    {
        $userClass = $this->userEntityClass;
        /** @var User $user */
        $user = new $userClass();
        $form = $this->createForm('FOM\UserBundle\Form\Type\UserRegistrationType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $helperService = $this->getUserHelper();
            $helperService->setPassword($user, $user->getPassword());

            $user->setRegistrationToken(hash("sha1",rand()));
            $user->setRegistrationTime(new \DateTime());

            $groupRepository = $this->getDoctrine()->getRepository('FOMUserBundle:Group');
            foreach ($this->groupTitles as $groupTitle) {
                /** @var Group|null $group */
                $group = $groupRepository->findOneBy(array(
                    'title' => $groupTitle,
                ));
                if ($group) {
                    $user->addGroup($group);
                } else {
                    $msg = sprintf('Self-registration group "%s" not found for user "%s"',
                        $groupTitle,
                        $user->getUsername());
                    /** @var LoggerInterface $logger */
                    $logger = $this->get('logger');
                    $logger->error($msg);
                }

            }

            $this->sendEmail($user);

            $em = $this->getEntityManager();
            $em->persist($user);
            $em->flush();

            $helperService->giveOwnRights($user);

            return $this->redirectToRoute('fom_user_registration_send');
        }

        return $this->render('@FOMUser/Registration/form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Registration step 4: Activate account by token
     *
     * @Route("/user/activate", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function confirmAction(Request $request)
    {
        $user = $this->getUserFromRegistrationToken($request);
        if (!$user) {
            return $this->render('@FOMUser/Login/error-notoken.html.twig', array(
                'site_email' => $this->emailFromAddress,
            ));
        }

        if(!$this->checkTimeInterval($user->getRegistrationTime(), $this->maxTokenAge)) {
            return $this->tokenExpired($user);
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
     * @Route("/user/registration/reset", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function resetAction(Request $request)
    {
        $user = $this->getUserFromRegistrationToken($request);
        if(!$user) {
            return $this->render('@FOMUser/Login/error-notoken.html.twig', array(
                'site_email' => $this->emailFromAddress,
            ));
        }

        $user->setRegistrationToken(hash("sha1",rand()));
        $user->setRegistrationTime(new \DateTime());

        $this->sendEmail($user);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('fom_user_registration_send');
    }

    /**
     * Registration step 5: Welcome new user
     *
     * @Route("/user/registration/done", methods={"GET"})
     * @return Response
     */
    public function doneAction()
    {
        return $this->render('@FOMUser/Registration/done.html.twig');
    }

    /**
     * @param User $user
     */
    protected function sendEmail($user)
    {
       $mailFrom = array($this->emailFromAddress => $this->emailFromName);
       /** @var \Swift_Mailer $mailer */
       $mailer = $this->get('mailer');
       $text = $this->renderView('FOMUserBundle:Registration:email-body.text.twig', array("user" => $user));
       $html = $this->renderView('FOMUserBundle:Registration:email-body.html.twig', array("user" => $user));
       $message = new \Swift_Message();
       $message
           ->setSubject($this->renderView('FOMUserBundle:Registration:email-subject.text.twig'))
           ->setFrom($mailFrom)
           ->setTo($user->getEmail())
           ->setBody($text)
           ->addPart($html, 'text/html')
       ;

       $mailer->send($message);
    }

    /**
     * @param Request $request
     * @return User|null
     */
    protected function getUserFromRegistrationToken(Request $request)
    {
        $token = $request->get('token');
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
